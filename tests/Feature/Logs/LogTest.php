<?php

use App\Enums\SystemRole;
use App\Models\Audit;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

function makeAudit(): Audit
{
    return Audit::create([
        'event' => 'updated',
        'auditable_type' => User::class,
        'auditable_id' => 1,
        'old_values' => ['name' => 'Old'],
        'new_values' => ['name' => 'New'],
        'url' => 'http://localhost/users/1',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36',
        'tags' => null,
    ]);
}

it('lists audit logs with parsed browser/os', function (): void {
    actingAsRole(SystemRole::Developer);
    makeAudit();

    $this->get(route('logs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Logs/Index')
            ->has('logs.data', 1)
            ->where('logs.data.0.event', 'updated')
            ->where('logs.data.0.auditable_type', 'User')
            ->where('logs.data.0.browser', 'Chrome'));
});

it('shows an audit with old/new values, bound by token not id', function (): void {
    actingAsRole(SystemRole::Developer);
    $audit = makeAudit();

    expect($audit->token)->not->toBeEmpty();

    // route() uses the model's route key (token), and the numeric id must not resolve.
    $this->get(route('logs.show', $audit))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Logs/Show')
            ->where('log.new_values.name', 'New'));

    $this->get('/logs/'.$audit->id)->assertNotFound();
});

it('captures the originating page (referrer) on audited writes', function (): void {
    // owen-it skips auditing under `php artisan test` unless console auditing is on.
    config(['audit.console' => true]);
    actingAsRole(SystemRole::Developer);

    $user = User::factory()->create();
    $page = 'http://localhost:8080/users/'.$user->token.'/edit';

    // The write hits /users/{token} (recorded as `url`) but originates from the edit
    // page (recorded as `referrer`) — mirroring an upload fired from a form.
    $this->withHeader('referer', $page)
        ->patch(route('users.update', $user), [
            'name' => 'Renamed',
            'email' => $user->email,
            'user_status' => 'Active',
            'roles' => [],
            'meta' => [],
        ])->assertRedirect(route('users.show', $user));

    $audit = Audit::where('auditable_type', User::class)
        ->where('auditable_id', $user->id)
        ->where('event', 'updated')
        ->latest('id')->first();

    expect($audit->referrer)->toBe($page);
});

it('redirects guests away from logs', function (): void {
    $this->get(route('logs.index'))->assertRedirect(route('login'));
});
