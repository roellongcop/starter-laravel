<?php

use App\Enums\SystemRole;
use App\Settings\SystemSettings;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

function setAutoLogout(int $seconds): void
{
    $system = app(SystemSettings::class);
    $system->auto_logout_seconds = $seconds;
    $system->save();
}

it('logs out an authenticated session that has been idle past the timeout', function (): void {
    setAutoLogout(60);
    actingAsRole(SystemRole::Developer);

    $this->withSession([
        'idle.last_activity' => now()->subSeconds(120)->getTimestamp(),
    ])
        ->get(route('profile.edit'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    $this->assertGuest();
});

it('keeps an authenticated session that is still within the window', function (): void {
    setAutoLogout(60);
    actingAsRole(SystemRole::Developer);

    $this->withSession([
        'idle.last_activity' => now()->subSeconds(5)->getTimestamp(),
    ])
        ->get(route('profile.edit'))
        ->assertOk();

    $this->assertAuthenticated();
});

it('never enforces idle logout when the timeout is off (0)', function (): void {
    setAutoLogout(0);
    actingAsRole(SystemRole::Developer);

    $this->withSession([
        'idle.last_activity' => now()->subSeconds(99999)->getTimestamp(),
    ])
        ->get(route('profile.edit'))
        ->assertOk();
});

it('refreshes the idle clock on a genuine (non-partial) request', function (): void {
    setAutoLogout(60);
    actingAsRole(SystemRole::Developer);
    $stale = now()->subSeconds(5)->getTimestamp();

    $this->withSession(['idle.last_activity' => $stale])
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSessionHas('idle.last_activity', fn ($value) => $value > $stale);
});

it('does not let a background partial reload refresh the idle clock', function (): void {
    setAutoLogout(60);
    actingAsRole(SystemRole::Developer);
    $stale = now()->subSeconds(5)->getTimestamp();

    // Mimic useStatusPoll's `router.reload({ only: [...] })` partial visit.
    $this->withSession(['idle.last_activity' => $stale])
        ->get(route('profile.edit'), [
            'X-Inertia' => 'true',
            'X-Inertia-Partial-Component' => 'Profile/Edit',
            'X-Inertia-Partial-Data' => 'documents',
        ])
        ->assertSessionHas('idle.last_activity', $stale);
});

it('still logs out a walked-away session even via a background poll', function (): void {
    setAutoLogout(60);
    actingAsRole(SystemRole::Developer);

    $this->withSession([
        'idle.last_activity' => now()->subSeconds(120)->getTimestamp(),
    ])
        ->get(route('profile.edit'), [
            'X-Inertia' => 'true',
            'X-Inertia-Partial-Component' => 'Profile/Edit',
            'X-Inertia-Partial-Data' => 'documents',
        ])
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('refreshes the idle clock via the heartbeat endpoint', function (): void {
    setAutoLogout(60);
    actingAsRole(SystemRole::Developer);
    $stale = now()->subSeconds(5)->getTimestamp();

    $this->withSession(['idle.last_activity' => $stale])
        ->post(route('session.heartbeat'))
        ->assertNoContent()
        ->assertSessionHas('idle.last_activity', fn ($value) => $value > $stale);

    $this->assertAuthenticated();
});

it('requires authentication for the heartbeat endpoint', function (): void {
    $this->post(route('session.heartbeat'))->assertRedirect(route('login'));
});
