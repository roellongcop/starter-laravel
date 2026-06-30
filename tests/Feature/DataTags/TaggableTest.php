<?php

use App\Enums\SystemRole;
use App\Models\Asset;
use App\Models\BaseModel;
use App\Models\DataTag;
use App\Models\Form;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ReferenceFile;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

/**
 * Each taggable resource: its model, store route, and a payload builder (sans
 * tags) for a valid create. Tags are added per test against a fresh org.
 */
dataset('taggable entities', [
    'asset' => [
        Asset::class,
        'assets.store',
        fn (Organization $org): array => [
            'name' => 'Tagged Asset',
            'id_code' => 'AST-1',
            'address' => '1 Market Street',
            'organization' => $org->token,
        ],
    ],
    'project' => [
        Project::class,
        'projects.store',
        fn (Organization $org): array => [
            'name' => 'Tagged Project',
            'description' => 'A project',
            'private' => false,
            'organization' => $org->token,
        ],
    ],
    'form' => [
        Form::class,
        'forms.store',
        fn (Organization $org): array => [
            'title' => 'Tagged Form',
            'organization' => $org->token,
            'form_fields' => [],
        ],
    ],
    'reference file' => [
        ReferenceFile::class,
        'reference-files.store',
        fn (Organization $org): array => [
            'name' => 'Tagged Reference',
            'organization' => $org->token,
        ],
    ],
]);

it('attaches organization tags on create', function (string $modelClass, string $route, Closure $payload): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $tagA = DataTag::factory()->create(['organization_id' => $organization->id]);
    $tagB = DataTag::factory()->create(['organization_id' => $organization->id]);

    $this->post(route($route), [
        ...$payload($organization),
        'tags' => [$tagA->token, $tagB->token],
    ])->assertRedirect();

    /** @var BaseModel $model */
    $model = $modelClass::query()->latest('id')->firstOrFail();

    expect($model->tags->pluck('token')->all())
        ->toContain($tagA->token, $tagB->token);
})->with('taggable entities');

it('ignores tags belonging to a different organization', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    $ownTag = DataTag::factory()->create(['organization_id' => $organization->id]);
    $foreignTag = DataTag::factory()->create(['organization_id' => $otherOrg->id]);

    $this->post(route('assets.store'), [
        'name' => 'Cross Org',
        'id_code' => 'AST-2',
        'address' => '2 Market Street',
        'organization' => $organization->token,
        'tags' => [$ownTag->token, $foreignTag->token],
    ])->assertRedirect(route('assets.index'));

    $asset = Asset::where('name', 'Cross Org')->firstOrFail();

    expect($asset->tags->pluck('token')->all())
        ->toContain($ownTag->token)
        ->not->toContain($foreignTag->token);
});

it('serializes attached tags as token/name/color without leaking ids', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $asset = Asset::factory()->create(['organization_id' => $organization->id]);
    $tag = DataTag::factory()->create(['organization_id' => $organization->id]);
    $asset->tags()->attach($tag->id);

    $this->get(route('assets.show', $asset))
        ->assertInertia(fn ($page) => $page
            ->where('asset.tags.0.token', $tag->token)
            ->where('asset.tags.0.name', $tag->name)
            ->where('asset.tags.0.color', $tag->color)
            ->missing('asset.tags.0.id'));
});

it('exposes the org-scoped tag picker options via the options endpoint', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $tag = DataTag::factory()->create(['organization_id' => $organization->id]);
    DataTag::factory()->create(); // a tag in another org, must not appear

    $this->getJson(route('data-tags.options', ['organization' => $organization->token]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', $tag->token)
        ->assertJsonPath('data.0.label', $tag->name);
});

it('replaces tags on update', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $asset = Asset::factory()->create(['organization_id' => $organization->id]);
    $tagA = DataTag::factory()->create(['organization_id' => $organization->id]);
    $tagB = DataTag::factory()->create(['organization_id' => $organization->id]);
    $asset->tags()->attach($tagA->id);

    $this->patch(route('assets.update', $asset), [
        'name' => $asset->name,
        'id_code' => $asset->id_code,
        'address' => $asset->address,
        'organization' => $organization->token,
        'tags' => [$tagB->token],
    ])->assertRedirect();

    expect($asset->fresh()->tags->pluck('token')->all())->toBe([$tagB->token]);
});

it('detaches a tagged model when it is deleted', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $asset = Asset::factory()->create(['organization_id' => $organization->id]);
    $tag = DataTag::factory()->create(['organization_id' => $organization->id]);
    $asset->tags()->attach($tag->id);

    expect(DB::table('taggables')->count())->toBe(1);

    $this->delete(route('assets.destroy', $asset))->assertRedirect();

    expect(DB::table('taggables')->count())->toBe(0)
        // The tag itself survives — only the attachment is removed.
        ->and(DataTag::find($tag->id))->not->toBeNull();
});

it('cascades attachments when a tag is deleted', function (): void {
    actingAsRole(SystemRole::Developer);
    $organization = Organization::factory()->create();
    $asset = Asset::factory()->create(['organization_id' => $organization->id]);
    $tag = DataTag::factory()->create(['organization_id' => $organization->id]);
    $asset->tags()->attach($tag->id);

    $this->delete(route('data-tags.destroy', $tag))->assertRedirect();

    expect(DB::table('taggables')->count())->toBe(0);
});
