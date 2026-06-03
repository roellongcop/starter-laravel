<?php

use App\Models\User;

test('updating the profile flashes a success message', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => 'Renamed',
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile')
        ->assertSessionHas('success');

    expect($user->refresh()->name)->toBe('Renamed');
});
