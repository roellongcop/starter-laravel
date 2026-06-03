<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns the cursor envelope for an authenticated token', function (): void {
    User::factory()->count(3)->create();
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $response = $this->getJson('/api/v1/users/available');

    $response->assertOk()
        ->assertJsonStructure(['data', 'next_cursor', 'prev_cursor', 'has_more'])
        ->assertJsonStructure(['data' => [['id', 'name', 'email']]]);
});

it('rejects unauthenticated requests', function (): void {
    $this->getJson('/api/v1/users/available')->assertUnauthorized();
});

it('paginates with cursors', function (): void {
    User::factory()->count(30)->create();
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $first = $this->getJson('/api/v1/users/available?per_page=10')->json();
    expect($first['data'])->toHaveCount(10)
        ->and($first['has_more'])->toBeTrue()
        ->and($first['next_cursor'])->toBeString();

    $second = $this->getJson('/api/v1/users/available?per_page=10&cursor='.$first['next_cursor'])->json();
    expect($second['data'])->toHaveCount(10);
});
