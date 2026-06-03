<?php

use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

function seedJobs(): void
{
    DB::table('jobs')->insert([
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Jobs\\Demo']),
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => now()->timestamp,
        'created_at' => now()->timestamp,
    ]);

    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Jobs\\Broken']),
        'exception' => 'Boom',
        'failed_at' => now(),
    ]);
}

it('shows pending and failed counts', function (): void {
    actingAsRole('developer');
    seedJobs();

    $this->get(route('queue.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Queue/Index')
            ->where('stats.pending', 1)
            ->where('stats.failed', 1));
});

it('clears pending jobs for a manager', function (): void {
    actingAsRole('developer');
    seedJobs();

    $this->post(route('queue.clear-pending'))->assertRedirect();
    expect(DB::table('jobs')->count())->toBe(0);
});

it('forbids queue management without queue.manage', function (): void {
    // admin has queue.index but not queue.manage.
    actingAsRole('admin');

    $this->get(route('queue.index'))->assertOk();
    $this->post(route('queue.clear-failed'))->assertForbidden();
});
