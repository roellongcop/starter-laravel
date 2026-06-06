<?php

use App\Enums\RecordStatus;
use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\seed;

it('issues a bearer token for valid credentials', function (): void {
    $user = User::factory()->create(['password' => 'secret-password']);

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'secret-password',
        'device_name' => 'pest',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'email', 'roles', 'permissions']])
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.email', $user->email);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
    expect($user->tokens()->count())->toBe(1);
});

it('rejects invalid credentials without issuing a token', function (): void {
    $user = User::factory()->create(['password' => 'secret-password']);

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');

    expect($user->tokens()->count())->toBe(0);
});

it('rejects an inactive user', function (): void {
    $user = User::factory()->create([
        'password' => 'secret-password',
        'record_status' => RecordStatus::Inactive,
    ]);

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'secret-password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('rate limits login after five failed attempts', function (): void {
    $user = User::factory()->create(['password' => 'secret-password']);

    foreach (range(1, 5) as $ignored) {
        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertStatus(422);
    }

    $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'secret-password'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('returns the current user for an authenticated token', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonStructure(['user' => ['id', 'name', 'email', 'roles', 'permissions']]);
});

it('rejects me without a token', function (): void {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});

it('revokes the token on logout', function (): void {
    $user = User::factory()->create(['password' => 'secret-password']);

    $token = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'secret-password',
    ])->json('token');

    $auth = ['Authorization' => "Bearer {$token}"];

    $this->getJson('/api/v1/me', $auth)->assertOk();
    $this->postJson('/api/v1/logout', [], $auth)->assertOk();

    // The token is revoked: its row is gone, so the bearer no longer resolves.
    // (We assert DB truth rather than re-calling /me, because the auth guard caches
    // the resolved user across sub-requests within a single test.)
    expect($user->tokens()->count())->toBe(0);
    expect(PersonalAccessToken::findToken($token))->toBeNull();
});

it('registers a new user, assigns the read-only role, and returns a token', function (): void {
    seed([PermissionSeeder::class, RoleSeeder::class]);
    Event::fake([Registered::class]);

    $response = $this->postJson('/api/v1/register', [
        'name' => 'New Person',
        'email' => 'new.person@example.com',
        'password' => 'Sup3r-Secret!pw',
        'password_confirmation' => 'Sup3r-Secret!pw',
        'device_name' => 'pest',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'email', 'roles', 'permissions']])
        ->assertJsonPath('user.email', 'new.person@example.com')
        ->assertJsonPath('user.roles', [SystemRole::User->value]);

    $user = User::where('email', 'new.person@example.com')->firstOrFail();
    expect(Hash::check('Sup3r-Secret!pw', $user->password))->toBeTrue();
    expect($user->tokens()->count())->toBe(1);
    Event::assertDispatched(Registered::class);
});

it('rejects registration with a duplicate email', function (): void {
    seed([PermissionSeeder::class, RoleSeeder::class]);
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/v1/register', [
        'name' => 'Dupe',
        'email' => 'taken@example.com',
        'password' => 'Sup3r-Secret!pw',
        'password_confirmation' => 'Sup3r-Secret!pw',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('rejects registration when the password is not confirmed', function (): void {
    seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->postJson('/api/v1/register', [
        'name' => 'Mismatch',
        'email' => 'mismatch@example.com',
        'password' => 'Sup3r-Secret!pw',
        'password_confirmation' => 'different',
    ])->assertStatus(422)->assertJsonValidationErrors('password');

    expect(User::where('email', 'mismatch@example.com')->exists())->toBeFalse();
});

it('revokes every token on logout-all', function (): void {
    $user = User::factory()->create();
    $user->createToken('phone');
    $user->createToken('tablet');
    Sanctum::actingAs($user, ['*']);

    expect($user->tokens()->count())->toBe(2);

    $this->postJson('/api/v1/logout-all')->assertOk();

    expect($user->fresh()->tokens()->count())->toBe(0);
});
