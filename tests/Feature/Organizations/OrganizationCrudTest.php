<?php

use App\Enums\SystemRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('creates an organization and resolves the point-of-contact token to an id', function (): void {
    actingAsRole(SystemRole::Developer);
    $contact = User::factory()->create();

    $this->post(route('organizations.store'), [
        'name' => 'Acme Corporation',
        'description' => 'A demo org',
        'point_of_contact' => $contact->token,
    ])->assertRedirect(route('organizations.index'));

    $organization = Organization::where('name', 'Acme Corporation')->first();
    expect($organization)->not->toBeNull()
        ->and($organization->description)->toBe('A demo org')
        ->and($organization->point_of_contact_id)->toBe($contact->id);
});

it('creates an organization without a point of contact', function (): void {
    actingAsRole(SystemRole::Developer);

    $this->post(route('organizations.store'), [
        'name' => 'No Contact Co',
        'point_of_contact' => '',
    ])->assertRedirect();

    expect(Organization::where('name', 'No Contact Co')->value('point_of_contact_id'))->toBeNull();
});

it('rejects an unknown point-of-contact token', function (): void {
    actingAsRole(SystemRole::Developer);

    $this->post(route('organizations.store'), [
        'name' => 'Bad Contact',
        'point_of_contact' => 'not-a-real-token',
    ])->assertSessionHasErrors('point_of_contact');

    expect(Organization::where('name', 'Bad Contact')->exists())->toBeFalse();
});

it('updates an organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create(['name' => 'Old']);

    $this->patch(route('organizations.update', $organization), [
        'name' => 'Updated',
        'point_of_contact' => '',
    ])->assertRedirect();

    expect($organization->fresh()->name)->toBe('Updated');
});

it('deletes an organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();

    $this->delete(route('organizations.destroy', $organization))->assertRedirect();
    expect(Organization::withInactive()->find($organization->id))->toBeNull();
});

it('never leaks the point-of-contact id to the frontend', function (): void {
    actingAsRole(SystemRole::Developer);
    $contact = User::factory()->create();
    $organization = Organization::factory()->create(['point_of_contact_id' => $contact->id]);

    $this->get(route('organizations.show', $organization))
        ->assertInertia(fn ($page) => $page
            ->where('organization.point_of_contact', $contact->token)
            ->where('organization.point_of_contact_name', $contact->name)
            ->missing('organization.point_of_contact_id'));
});

it('renders organizations on the index page (scroll prop loads on first paint)', function (): void {
    actingAsRole(SystemRole::Developer);
    Organization::factory()->count(3)->create();

    $this->get(route('organizations.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Organizations/Index')
            ->has('organizations.data', 3));
});

it('lists the organization projects on its show page', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    Project::factory()->count(2)->create(['organization_id' => $organization->id]);
    Project::factory()->create(); // belongs to a different org

    $this->get(route('organizations.show', $organization))
        ->assertInertia(fn ($page) => $page
            ->component('Organizations/Show')
            ->has('projects.data', 2)
            ->where('projectsTotal', 2));
});

it('searches the projects on the organization show page', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    Project::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Apollo Launch',
    ]);
    Project::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Zephyr Build',
    ]);

    $this->get(route('organizations.show', [$organization, 'search' => 'Apollo']))
        ->assertInertia(fn ($page) => $page
            ->has('projects.data', 1)
            ->where('projects.data.0.name', 'Apollo Launch')
            ->where('projectsTotal', 1)
            ->where('projectFilters.search', 'Apollo'));
});

it('forbids organization access without permission', function (): void {
    $this->get(route('organizations.index'))->assertRedirect(route('login'));

    $noRole = User::factory()->create();
    $this->actingAs($noRole)->get(route('organizations.index'))->assertForbidden();
});
