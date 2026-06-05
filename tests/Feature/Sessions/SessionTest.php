<?php

use App\Enums\SystemRole;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('lists sessions', function (): void {
    actingAsRole(SystemRole::Developer);
    DB::table('sessions')->insert([
        'id' => 'sess-abc',
        'ip_address' => '10.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'x',
        'last_activity' => now()->timestamp,
    ]);

    $this->get(route('sessions.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Sessions/Index')->has('sessions.data'));
});

it('revokes a session', function (): void {
    actingAsRole(SystemRole::Developer);
    DB::table('sessions')->insert([
        'id' => 'sess-xyz',
        'ip_address' => '10.0.0.2',
        'user_agent' => 'UA',
        'payload' => 'x',
        'last_activity' => now()->timestamp,
    ]);

    $this->delete(route('sessions.destroy', 'sess-xyz'))->assertRedirect();
    expect(DB::table('sessions')->where('id', 'sess-xyz')->exists())->toBeFalse();
});

it('redirects guests away from sessions', function (): void {
    $this->get(route('sessions.index'))->assertRedirect(route('login'));
});
