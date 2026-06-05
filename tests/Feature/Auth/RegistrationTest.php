<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register and default to the read-only User role', function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'test@example.com')->first();
    expect($user->hasRole('User'))->toBeTrue()
        // read-only: can view the dashboard, cannot create users.
        ->and($user->can('dashboard.index'))->toBeTrue()
        ->and($user->can('users.index'))->toBeTrue()
        ->and($user->can('users.create'))->toBeFalse();
});
