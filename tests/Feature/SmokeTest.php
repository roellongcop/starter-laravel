<?php

use App\Enums\SystemRole;
use App\Models\Asset;
use App\Models\File;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\Ip;
use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\Person;
use App\Models\Project;
use App\Models\ReferenceFile;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamCategory;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    // developer gets god-mode via Gate::before, so authorization never blocks
    // the smoke sweep — we only care that each page renders.
    actingAsRole(SystemRole::Developer);
});

/**
 * Breadth-first smoke test: every GET route that renders an Inertia page should
 * return 200. Complements the per-feature tests that assert components/props.
 *
 * Intentionally NOT covered (not GET Inertia pages): imports.preview (parses a
 * real uploaded file), imports.errors + *.download/*.preview/media.img/avatar
 * (streamed responses), logs.show (needs a real audit row), and every
 * POST/PATCH/DELETE route.
 */
it('renders every page route with a 200', function (): void {
    $user = User::factory()->create();
    $theme = Theme::factory()->create();
    $file = File::factory()->create(['owner_id' => auth()->id()]);
    $ip = Ip::factory()->create();
    // Seed past the keyset page size (config('keen.pagination_size')) so the
    // Inertia::scroll() cursor pages — organizations.index, projects.index and
    // assets.index — actually compute a next cursor and exercise that code path
    // (a single page never does).
    $pageSize = (int) config('keen.pagination_size');
    $organization = Organization::factory()->create();
    Organization::factory()->count($pageSize)->create();
    Project::factory()->count($pageSize + 1)->create(['organization_id' => $organization->id]);
    $project = $organization->projects()->firstOrFail();
    Asset::factory()->count($pageSize + 1)->create(['organization_id' => $organization->id]);
    $asset = $organization->assets()->firstOrFail();
    $form = Form::factory()->create(['organization_id' => $organization->id]);
    $response = FormResponse::factory()->forForm($form)->create();
    $role = Role::query()->firstOrFail();
    $teamCategory = TeamCategory::factory()->create(['organization_id' => $organization->id]);
    $organizationRole = OrganizationRole::factory()->create(['organization_id' => $organization->id]);
    $team = Team::factory()->create([
        'organization_id' => $organization->id,
        'team_category_id' => $teamCategory->id,
        'organization_role_id' => $organizationRole->id,
    ]);
    Person::factory()->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'organization_role_id' => $organizationRole->id,
        'organization_id' => $organization->id,
    ]);
    $referenceFile = ReferenceFile::factory()->create(['organization_id' => $organization->id]);

    /** @var array<int, array{0: string, 1: array<int, mixed>}> $pages */
    $pages = [
        ['/', []],
        ['contact', []],
        ['dashboard', []],
        ['profile.edit', []],
        ['settings.index', []],
        ['notifications.index', []],
        ['sessions.index', []],
        ['logs.index', []],
        ['login-history.index', []],
        ['queue.index', []],
        ['backups.index', []],
        ['exports.index', []],
        ['exports.create', []],
        ['imports.index', []],
        ['imports.create', []],
        ['users.index', []],
        ['users.create', []],
        ['users.show', [$user]],
        ['users.edit', [$user]],
        ['roles.index', []],
        ['roles.create', []],
        ['roles.show', [$role]],
        ['roles.edit', [$role]],
        ['themes.index', []],
        ['themes.create', []],
        ['themes.show', [$theme]],
        ['themes.edit', [$theme]],
        ['files.index', []],
        ['files.create', []],
        ['files.show', [$file]],
        ['ips.index', []],
        ['ips.show', [$ip]],
        ['organizations.index', []],
        ['organizations.show', [$organization]],
        ['projects.index', []],
        ['projects.show', [$project]],
        ['assets.index', []],
        ['assets.show', [$asset]],
        ['teams.index', []],
        ['teams.show', [$team]],
        ['team-categories.index', []],
        ['team-categories.show', [$teamCategory]],
        ['organization-roles.index', []],
        ['organization-roles.show', [$organizationRole]],
        ['people.index', []],
        ['reference-files.index', []],
        ['reference-files.show', [$referenceFile]],
        ['forms.index', []],
        ['forms.create', []],
        ['forms.show', [$form]],
        ['forms.edit', [$form]],
        ['forms.respond', [$form]],
        ['forms.responses.index', [$form]],
        ['responses.show', [$response]],
    ];

    foreach ($pages as [$name, $params]) {
        $url = $name === '/' ? '/' : route($name, $params);
        $status = $this->get($url)->getStatusCode();

        $this->assertSame(200, $status, "Route [{$name}] ({$url}) returned {$status}, expected 200.");
    }
});
