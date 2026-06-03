<?php

use App\Models\User;

it('flashes the stored password hint for a known email', function (): void {
    User::factory()->create([
        'email' => 'hinted@example.com',
        'password_hint' => 'the usual one',
    ]);

    $response = $this->post('/password-hint', ['email' => 'hinted@example.com']);

    $response->assertRedirect();
    $response->assertSessionHas('hint', 'the usual one');
});

it('flashes a generic message for an unknown email', function (): void {
    $response = $this->post('/password-hint', ['email' => 'nobody@example.com']);

    $response->assertSessionHas('hint', 'No password hint is available for that email address.');
});

it('validates the email field', function (): void {
    $this->post('/password-hint', ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');
});
