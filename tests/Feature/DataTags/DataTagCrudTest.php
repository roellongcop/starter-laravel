<?php

use App\Enums\SystemRole;
use App\Models\DataTag;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('creates a tag and resolves the organization token to an id', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $this->post(route('data-tags.store'), [
        'name' => 'Priority',
        'description' => 'High priority',
        'organization' => $organization->token,
        'color' => DataTag::COLORS[0],
    ])->assertRedirect(route('data-tags.index'));

    $tag = DataTag::where('name', 'Priority')->first();
    expect($tag)->not->toBeNull()
        ->and($tag->organization_id)->toBe($organization->id)
        ->and($tag->color)->toBe(DataTag::COLORS[0]);
});

it('requires a name, a valid organization and a color', function (): void {
    actingAsRole(SystemRole::Developer);

    $this->post(route('data-tags.store'), [
        'organization' => 'not-a-real-token',
    ])->assertSessionHasErrors(['name', 'organization', 'color']);
});

it('rejects a color outside the palette', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $this->post(route('data-tags.store'), [
        'name' => 'Bad Color',
        'organization' => $organization->token,
        'color' => '#123456',
    ])->assertSessionHasErrors('color');

    expect(DataTag::where('name', 'Bad Color')->exists())->toBeFalse();
});

it('rejects a duplicate tag name within the same organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    DataTag::factory()->create(['organization_id' => $organization->id, 'name' => 'Archived']);

    $this->post(route('data-tags.store'), [
        'name' => 'Archived',
        'organization' => $organization->token,
        'color' => DataTag::COLORS[1],
    ])->assertSessionHasErrors('name');

    expect(DataTag::where('name', 'Archived')->count())->toBe(1);
});

it('allows the same tag name across different organizations', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    DataTag::factory()->create(['organization_id' => $orgA->id, 'name' => 'Shared']);

    $this->post(route('data-tags.store'), [
        'name' => 'Shared',
        'organization' => $orgB->token,
        'color' => DataTag::COLORS[2],
    ])->assertRedirect(route('data-tags.index'));

    expect(DataTag::where('name', 'Shared')->count())->toBe(2);
});

it('updates a tag', function (): void {
    actingAsRole(SystemRole::Developer);
    $tag = DataTag::factory()->create(['name' => 'Old', 'color' => DataTag::COLORS[0]]);

    $this->patch(route('data-tags.update', $tag), [
        'name' => 'New',
        'organization' => $tag->organization->token,
        'color' => DataTag::COLORS[3],
    ])->assertRedirect();

    expect($tag->fresh())->name->toBe('New')->color->toBe(DataTag::COLORS[3]);
});

it('deletes a tag', function (): void {
    actingAsRole(SystemRole::Developer);
    $tag = DataTag::factory()->create();

    $this->delete(route('data-tags.destroy', $tag))->assertRedirect();

    expect(DataTag::withInactive()->find($tag->id))->toBeNull();
});

it('never leaks the organization id to the frontend', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $tag = DataTag::factory()->create(['organization_id' => $organization->id]);

    $this->get(route('data-tags.show', $tag))
        ->assertInertia(fn ($page) => $page
            ->where('dataTag.organization', $organization->token)
            ->where('dataTag.color', $tag->color)
            ->missing('dataTag.organization_id'));
});

it('filters the index by organization token', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    DataTag::factory()->count(2)->create(['organization_id' => $orgA->id]);
    DataTag::factory()->create(['organization_id' => $orgB->id]);

    $this->get(route('data-tags.index', ['organization' => $orgA->token]))
        ->assertInertia(fn ($page) => $page
            ->component('DataTags/Index')
            ->has('dataTags.data', 2)
            ->where('filters.organization', $orgA->token));
});

it('forbids tag access without permission', function (): void {
    $this->get(route('data-tags.index'))->assertRedirect(route('login'));

    $noRole = User::factory()->create();
    $this->actingAs($noRole)->get(route('data-tags.index'))->assertForbidden();
});
