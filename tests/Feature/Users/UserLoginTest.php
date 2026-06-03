<?php

use App\Enums\RecordStatus;
use App\Models\User;

it('an active user can still log in after the model refactor', function (): void {
    $user = User::factory()->create([
        'email' => 'active@example.com',
        'password' => 'password123',
    ]);

    $this->post('/login', [
        'email' => 'active@example.com',
        'password' => 'password123',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

it('an inactive user is hidden by the global scope and cannot authenticate', function (): void {
    User::factory()->create([
        'email' => 'inactive@example.com',
        'password' => 'password123',
        'record_status' => RecordStatus::Inactive,
    ]);

    $this->post('/login', [
        'email' => 'inactive@example.com',
        'password' => 'password123',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
