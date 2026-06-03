<?php

it('renders the contact page', function (): void {
    $this->get('/contact')->assertOk();
});

it('accepts a valid contact submission', function (): void {
    $response = $this->post('/contact', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'message' => 'Hello there.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

it('validates the contact submission', function (): void {
    $this->post('/contact', ['name' => '', 'email' => 'nope', 'message' => ''])
        ->assertSessionHasErrors(['name', 'email', 'message']);
});
