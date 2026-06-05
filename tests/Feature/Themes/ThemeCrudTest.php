<?php

use App\Enums\SystemRole;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('creates a theme with tokens', function (): void {
    actingAsRole(SystemRole::Developer);

    $this->post(route('themes.store'), [
        'name' => 'Midnight',
        'description' => 'Dark palette',
        'is_default' => false,
        'tokens' => [
            'light' => ['--primary' => '0 0% 10%'],
            'dark' => ['--primary' => '0 0% 90%'],
        ],
    ])->assertRedirect();

    $theme = Theme::where('name', 'Midnight')->first();
    expect($theme)->not->toBeNull()
        ->and($theme->tokens['light']['--primary'])->toBe('0 0% 10%')
        ->and($theme->tokens['dark']['--primary'])->toBe('0 0% 90%');
});

it('enforces a single default theme', function (): void {
    actingAsRole(SystemRole::Developer);
    $existing = Theme::factory()->default()->create();

    $this->post(route('themes.store'), [
        'name' => 'New Default',
        'is_default' => true,
        'tokens' => ['light' => [], 'dark' => []],
    ])->assertRedirect();

    expect($existing->fresh()->is_default)->toBeFalse()
        ->and(Theme::where('is_default', true)->count())->toBe(1)
        ->and(Theme::where('is_default', true)->value('name'))->toBe('New Default');
});

it('updates a theme', function (): void {
    actingAsRole(SystemRole::Developer);
    $theme = Theme::factory()->create(['name' => 'Old']);

    $this->patch(route('themes.update', $theme), [
        'name' => 'Updated',
        'is_default' => false,
        'tokens' => ['light' => [], 'dark' => []],
    ])->assertRedirect();

    expect($theme->fresh()->name)->toBe('Updated');
});

it('deletes a theme', function (): void {
    actingAsRole(SystemRole::Developer);
    $theme = Theme::factory()->create();

    $this->delete(route('themes.destroy', $theme))->assertRedirect();
    expect(Theme::withInactive()->find($theme->id))->toBeNull();
});

it('forbids theme access without permission', function (): void {
    $this->get(route('themes.index'))->assertRedirect(route('login'));

    $noRole = User::factory()->create();
    $this->actingAs($noRole)->get(route('themes.index'))->assertForbidden();
});

it('shares the default theme tokens with the frontend', function (): void {
    $theme = Theme::factory()->default()->create([
        'tokens' => ['light' => ['--primary' => '1 2% 3%'], 'dark' => []],
    ]);
    actingAsRole(SystemRole::Developer);

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('theme.light.--primary', '1 2% 3%'));
});
