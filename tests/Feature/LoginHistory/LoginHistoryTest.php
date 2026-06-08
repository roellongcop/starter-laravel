<?php

use App\Enums\SystemRole;
use App\Models\LoginHistory;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('lists login history for an authorised role', function (): void {
    actingAsRole(SystemRole::Developer); // actingAs() does not fire a Login event

    LoginHistory::factory()->count(3)->create();

    $this->get(route('login-history.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LoginHistory/Index')
            ->has('history.data', 3)
            ->has('history.data.0.event')
            ->has('history.data.0.browser'));
});

it('forbids a user without the login-history permission', function (): void {
    $user = User::factory()->create(); // no roles → no abilities

    $this->actingAs($user)
        ->get(route('login-history.index'))
        ->assertForbidden();
});

it('redirects guests to login', function (): void {
    $this->get(route('login-history.index'))->assertRedirect(route('login'));
});

it('records a login event when a user signs in via the web', function (): void {
    $user = User::factory()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect();

    $this->assertDatabaseHas('login_history', [
        'user_id' => $user->id,
        'event' => 'login',
    ]);
});

it('records a logout event when a user signs out via the web', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect();

    $this->assertDatabaseHas('login_history', [
        'user_id' => $user->id,
        'event' => 'logout',
    ]);
});

it('records a login event for the stateless mobile API', function (): void {
    $user = User::factory()->create();

    $this->postJson(route('api.v1.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'pixel-8',
    ])->assertOk();

    $this->assertDatabaseHas('login_history', [
        'user_id' => $user->id,
        'event' => 'login',
    ]);
});
