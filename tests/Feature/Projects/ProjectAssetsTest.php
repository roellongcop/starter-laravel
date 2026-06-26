<?php

use App\Enums\SystemRole;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('shows the assets bound to a project on the show page', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $asset = Asset::factory()->create(['organization_id' => $organization->id]);
    $project->assets()->attach($asset->id);

    $this->get(route('projects.show', $project))
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->has('projectAssets.data', 1)
            ->where('projectAssets.data.0.token', $asset->token)
            ->where('projectAssets.data.0.name', $asset->name)
            ->where('assetsTotal', 1)
            ->has('assetOptions', 1)
            ->where('selectedAssetTokens', [$asset->token]));
});

it('cursor-paginates the project assets', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $pageSize = (int) config('keen.pagination_size');
    $assets = Asset::factory()->count($pageSize + 1)->create(['organization_id' => $organization->id]);
    $project->assets()->attach($assets->pluck('id')->all());

    // The list returns one page; the total reflects every bound asset.
    $this->get(route('projects.show', $project))
        ->assertInertia(fn ($page) => $page
            ->has('projectAssets.data', $pageSize)
            ->where('assetsTotal', $pageSize + 1));
});

it('searches the project assets server-side', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $match = Asset::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Searchable Crane',
    ]);
    $other = Asset::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Unrelated Truck',
    ]);
    $project->assets()->attach([$match->id, $other->id]);

    $this->get(route('projects.show', ['project' => $project, 'search' => 'Crane']))
        ->assertInertia(fn ($page) => $page
            ->has('projectAssets.data', 1)
            ->where('projectAssets.data.0.token', $match->token)
            ->where('filters.search', 'Crane'));
});

it('attaches selected assets to a project', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $assets = Asset::factory()->count(2)->create(['organization_id' => $organization->id]);

    $this->put(route('projects.assets.update', $project), [
        'assets' => $assets->pluck('token')->all(),
    ])->assertRedirect();

    expect($project->fresh()->assets)->toHaveCount(2);
});

it('detaches assets removed from the selection without deleting them', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    [$keep, $drop] = Asset::factory()->count(2)->create(['organization_id' => $organization->id])->all();
    $project->assets()->attach([$keep->id, $drop->id]);

    $this->put(route('projects.assets.update', $project), [
        'assets' => [$keep->token],
    ])->assertRedirect();

    expect($project->fresh()->assets->pluck('id')->all())->toBe([$keep->id]);
    // Detaching only removes the pivot row, never the asset.
    expect(Asset::find($drop->id))->not->toBeNull();
});

it('detaches every asset when an empty selection is submitted', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $asset = Asset::factory()->create(['organization_id' => $organization->id]);
    $project->assets()->attach($asset->id);

    $this->put(route('projects.assets.update', $project), ['assets' => []])
        ->assertRedirect();

    expect($project->fresh()->assets)->toHaveCount(0)
        ->and(Asset::find($asset->id))->not->toBeNull();
});

it('ignores assets that belong to another organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $other = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $foreign = Asset::factory()->create(['organization_id' => $other->id]);

    $this->put(route('projects.assets.update', $project), [
        'assets' => [$foreign->token],
    ])->assertRedirect();

    expect($project->fresh()->assets)->toHaveCount(0);
});

it('reflects a renamed asset without copying attributes', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $asset = Asset::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Original',
    ]);
    $project->assets()->attach($asset->id);

    $asset->update(['name' => 'Renamed']);

    $this->get(route('projects.show', $project))
        ->assertInertia(fn ($page) => $page
            ->where('projectAssets.data.0.name', 'Renamed'));
});

it('only offers attachable assets from the project organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $other = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    Asset::factory()->count(2)->create(['organization_id' => $organization->id]);
    Asset::factory()->create(['organization_id' => $other->id]);

    $this->get(route('projects.show', $project))
        ->assertInertia(fn ($page) => $page->has('assetOptions', 2));
});

it('forbids managing project assets without update permission', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $asset = Asset::factory()->create(['organization_id' => $organization->id]);

    $noRole = User::factory()->create();
    $this->actingAs($noRole)
        ->put(route('projects.assets.update', $project), ['assets' => [$asset->token]])
        ->assertForbidden();

    expect($project->fresh()->assets)->toHaveCount(0);
});
