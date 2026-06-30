<?php

use App\Enums\SystemRole;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('returns organizations matching the search term, keyed by token', function (): void {
    actingAsRole(SystemRole::Developer);
    $acme = Organization::factory()->create(['name' => 'Acme Corporation']);
    Organization::factory()->create(['name' => 'Beta Industries']);

    $this->getJson(route('organizations.options', ['q' => 'acme']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0', ['value' => $acme->token, 'label' => 'Acme Corporation'])
        ->assertJsonPath('has_more', false);
});

it('caps a page at the configured options limit and flags more', function (): void {
    actingAsRole(SystemRole::Developer);
    Organization::factory()->count(config('keen.options_limit') + 5)->create();

    $this->getJson(route('organizations.options'))
        ->assertOk()
        ->assertJsonCount(config('keen.options_limit'), 'data')
        ->assertJsonPath('has_more', true);
});

it('returns organizations ordered by name when no query is given', function (): void {
    actingAsRole(SystemRole::Developer);
    Organization::factory()->create(['name' => 'Zebra Co']);
    Organization::factory()->create(['name' => 'Alpha Co']);

    $data = $this->getJson(route('organizations.options'))->assertOk()->json('data');

    expect(array_column($data, 'label'))->toBe(['Alpha Co', 'Zebra Co']);
});

it('paginates options with a cursor for load-on-scroll', function (): void {
    actingAsRole(SystemRole::Developer);
    $limit = (int) config('keen.options_limit');
    Organization::factory()->count($limit + 5)->create();

    $page1 = $this->getJson(route('organizations.options'))
        ->assertOk()
        ->assertJsonCount($limit, 'data')
        ->assertJsonPath('has_more', true)
        ->json();

    expect($page1['next_cursor'])->not->toBeNull();

    $page2 = $this->getJson(route('organizations.options', ['cursor' => $page1['next_cursor']]))
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('has_more', false)
        ->json();

    // The two pages must not overlap.
    $tokens1 = array_column($page1['data'], 'value');
    $tokens2 = array_column($page2['data'], 'value');
    expect(array_intersect($tokens1, $tokens2))->toBeEmpty();
});

it('hydrates specific organizations by token, bypassing pagination', function (): void {
    actingAsRole(SystemRole::Developer);
    $organizations = Organization::factory()->count(3)->create();
    $wanted = $organizations->take(2);

    $response = $this->getJson(route('organizations.options', [
        'tokens' => $wanted->pluck('token')->all(),
    ]))->assertOk()->assertJsonCount(2, 'data');

    foreach ($wanted as $organization) {
        $response->assertJsonFragment(['value' => $organization->token, 'label' => $organization->name]);
    }
});

it('escapes LIKE wildcards in the search term', function (): void {
    actingAsRole(SystemRole::Developer);
    Organization::factory()->create(['name' => 'Acme']);

    // A bare '%' must match literally (no rows), not act as a wildcard.
    $this->getJson(route('organizations.options', ['q' => '%']))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('requires authentication', function (): void {
    Organization::factory()->create();

    $this->getJson(route('organizations.options'))->assertUnauthorized();
});

it('forbids unverified users', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->getJson(route('organizations.options'))->assertForbidden();
});
