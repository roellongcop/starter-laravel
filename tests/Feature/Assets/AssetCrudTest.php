<?php

use App\Enums\SystemRole;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('creates an asset and resolves the organization token to an id', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $this->post(route('assets.store'), [
        'name' => 'HQ Building',
        'id_code' => 'AST-0001',
        'address' => '123 Market Street',
        'organization' => $organization->token,
    ])->assertRedirect(route('assets.index'));

    $asset = Asset::where('name', 'HQ Building')->first();
    expect($asset)->not->toBeNull()
        ->and($asset->id_code)->toBe('AST-0001')
        ->and($asset->address)->toBe('123 Market Street')
        ->and($asset->organization_id)->toBe($organization->id);
});

it('requires a valid organization token', function (): void {
    actingAsRole(SystemRole::Developer);

    $this->post(route('assets.store'), [
        'name' => 'Orphan',
        'id_code' => 'AST-9999',
        'address' => 'Nowhere',
        'organization' => 'not-a-real-token',
    ])->assertSessionHasErrors('organization');

    expect(Asset::where('name', 'Orphan')->exists())->toBeFalse();
});

it('requires name, id_code and address', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $this->post(route('assets.store'), [
        'organization' => $organization->token,
    ])->assertSessionHasErrors(['name', 'id_code', 'address']);
});

it('rejects a duplicate asset name within the same organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    Asset::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Apollo Tower',
    ]);

    $this->post(route('assets.store'), [
        'name' => 'Apollo Tower',
        'id_code' => 'AST-1234',
        'address' => '1 Apollo Way',
        'organization' => $organization->token,
    ])->assertSessionHasErrors('name');

    expect(Asset::where('name', 'Apollo Tower')->count())->toBe(1);
});

it('allows the same asset name across different organizations', function (): void {
    actingAsRole(SystemRole::Developer);
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    Asset::factory()->create([
        'organization_id' => $orgA->id,
        'name' => 'Shared Name',
    ]);

    $this->post(route('assets.store'), [
        'name' => 'Shared Name',
        'id_code' => 'AST-5678',
        'address' => '2 Shared Blvd',
        'organization' => $orgB->token,
    ])->assertRedirect(route('assets.index'));

    expect(Asset::where('name', 'Shared Name')->count())->toBe(2);
});

it('allows a duplicate id_code within the same organization (id_code is not unique)', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    Asset::factory()->create([
        'organization_id' => $organization->id,
        'id_code' => 'AST-DUP',
    ]);

    $this->post(route('assets.store'), [
        'name' => 'Second Asset',
        'id_code' => 'AST-DUP',
        'address' => '3 Duplicate Rd',
        'organization' => $organization->token,
    ])->assertRedirect(route('assets.index'));

    expect(Asset::where('id_code', 'AST-DUP')->count())->toBe(2);
});

it('updates an asset', function (): void {
    actingAsRole(SystemRole::Developer);
    $asset = Asset::factory()->create(['name' => 'Old', 'id_code' => 'AST-OLD']);

    $this->patch(route('assets.update', $asset), [
        'name' => 'Updated',
        'id_code' => 'AST-NEW',
        'address' => 'New Address',
        'organization' => $asset->organization->token,
    ])->assertRedirect();

    expect($asset->fresh())
        ->name->toBe('Updated')
        ->id_code->toBe('AST-NEW')
        ->address->toBe('New Address');
});

it('deletes an asset', function (): void {
    actingAsRole(SystemRole::Developer);
    $asset = Asset::factory()->create();

    $this->delete(route('assets.destroy', $asset))->assertRedirect();
    expect(Asset::withInactive()->find($asset->id))->toBeNull();
});

it('renders assets on the index page (scroll prop loads on first paint)', function (): void {
    actingAsRole(SystemRole::Developer);
    Asset::factory()->count(3)->create();

    $this->get(route('assets.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Assets/Index')
            ->has('assets.data', 3));
});

it('never leaks the organization id to the frontend', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $asset = Asset::factory()->create(['organization_id' => $organization->id]);

    $this->get(route('assets.show', $asset))
        ->assertInertia(fn ($page) => $page
            ->where('asset.organization', $organization->token)
            ->where('asset.organization_name', $organization->name)
            ->missing('asset.organization_id'));
});

it('deletes an asset from its organization page and redirects back to the org', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $asset = Asset::factory()->create(['organization_id' => $organization->id]);

    $this->delete(route('organizations.assets.destroy', [$organization, $asset]))
        ->assertRedirect(route('organizations.show', $organization->token));

    expect(Asset::withInactive()->find($asset->id))->toBeNull();
});

it('404s deleting a nested asset that does not belong to the organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $asset = Asset::factory()->create(); // different org

    $this->delete(route('organizations.assets.destroy', [$organization, $asset]))
        ->assertNotFound();

    expect(Asset::find($asset->id))->not->toBeNull();
});

it('shows an asset nested under its organization with an org-rooted trail', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $asset = Asset::factory()->create(['organization_id' => $organization->id]);

    $this->get(route('organizations.assets.show', [$organization, $asset]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Assets/Show')
            ->where('parentOrganization.token', $organization->token)
            ->where('parentOrganization.name', $organization->name));
});

it('404s a nested asset that does not belong to the organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $asset = Asset::factory()->create(); // belongs to a different org

    $this->get(route('organizations.assets.show', [$organization, $asset]))
        ->assertNotFound();
});

it('forbids asset access without permission', function (): void {
    $this->get(route('assets.index'))->assertRedirect(route('login'));

    $noRole = User::factory()->create();
    $this->actingAs($noRole)->get(route('assets.index'))->assertForbidden();
});
