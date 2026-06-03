<?php

use App\Models\User;
use App\Support\RestoreSentinel;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    RestoreSentinel::clear();
});

afterEach(fn () => RestoreSentinel::clear());

it('the sentinel put/clear round-trips', function (): void {
    expect(RestoreSentinel::active())->toBeFalse();
    RestoreSentinel::put(42);
    expect(RestoreSentinel::active())->toBeTrue()
        ->and(RestoreSentinel::operatorId())->toBe(42);
    RestoreSentinel::clear();
    expect(RestoreSentinel::active())->toBeFalse();
});

it('blocks non-operators with 503 while a restore is active', function (): void {
    $operator = User::factory()->create();
    $operator->assignRole('admin');
    $other = User::factory()->create();
    $other->assignRole('admin');

    RestoreSentinel::put($operator->id);

    $this->actingAs($other)->get(route('dashboard'))->assertStatus(503);
});

it('lets the operator through during a restore', function (): void {
    $operator = User::factory()->create();
    $operator->assignRole('admin');

    RestoreSentinel::put($operator->id);

    $this->actingAs($operator)->get(route('dashboard'))->assertOk();
});

it('lets a developer through during a restore', function (): void {
    $dev = User::factory()->create();
    $dev->assignRole('developer');
    RestoreSentinel::put(999); // someone else is the operator

    $this->actingAs($dev)->get(route('dashboard'))->assertOk();
});

it('keeps the login route reachable during a restore', function (): void {
    RestoreSentinel::put(1);

    $this->get(route('login'))->assertOk();
});

it('restores access once the sentinel clears', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');
    RestoreSentinel::put(999);
    $this->actingAs($user)->get(route('dashboard'))->assertStatus(503);

    RestoreSentinel::clear();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});
