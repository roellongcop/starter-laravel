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

it('shows an audit with old/new values', function (): void {
    actingAsRole(SystemRole::Developer);
    $audit = makeAudit();

    $this->get(route('logs.show', $audit->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Logs/Show')
            ->where('log.new_values.name', 'New'));
});

it('redirects guests away from logs', function (): void {
    $this->get(route('logs.index'))->assertRedirect(route('login'));
});
