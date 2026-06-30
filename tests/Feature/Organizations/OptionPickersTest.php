<?php

use App\Enums\SystemRole;
use App\Models\Asset;
use App\Models\DataTag;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('returns users globally for the users picker', function (): void {
    actingAsRole(SystemRole::Developer);
    $user = User::factory()->create(['name' => 'Zoe Picker']);

    $this->getJson(route('users.options', ['q' => 'zoe picker']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', $user->token)
        ->assertJsonPath('data.0.label', 'Zoe Picker');
});

it('scopes data-tag options to the requested organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $tag = DataTag::factory()->create(['organization_id' => $orgA->id]);
    DataTag::factory()->create(['organization_id' => $orgB->id]);

    $this->getJson(route('data-tags.options', ['organization' => $orgA->token]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', $tag->token);
});

it('returns no scoped options without an organization (cascade requires one)', function (): void {
    actingAsRole(SystemRole::Developer);
    DataTag::factory()->create();

    $this->getJson(route('data-tags.options'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('scopes asset options to the requested organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $asset = Asset::factory()->create(['organization_id' => $orgA->id]);
    Asset::factory()->create(['organization_id' => $orgB->id]);

    $this->getJson(route('assets.options', ['organization' => $orgA->token]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', $asset->token);
});

it('requires authentication for option pickers', function (): void {
    $this->getJson(route('users.options'))->assertUnauthorized();
    $this->getJson(route('data-tags.options'))->assertUnauthorized();
    $this->getJson(route('assets.options'))->assertUnauthorized();
});
