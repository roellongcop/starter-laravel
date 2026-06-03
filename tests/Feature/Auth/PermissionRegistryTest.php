<?php

use App\Support\Permissions;
use Spatie\Permission\Models\Permission;

it('syncs every declared permission and is idempotent', function (): void {
    $expected = count(Permissions::all());

    $this->artisan('permissions:sync')->assertExitCode(0);
    expect(Permission::count())->toBe($expected);

    // Running again creates nothing new.
    $this->artisan('permissions:sync')->assertExitCode(0);
    expect(Permission::count())->toBe($expected);
});

it('builds permission names from the registry', function (): void {
    expect(Permissions::all())
        ->toContain('users.index')
        ->toContain('users.delete')
        ->toContain('logs.show')
        ->toContain('queue.manage')
        ->toContain('view-inactive')
        ->not->toContain('logs.delete');
});

it('prunes permissions no longer declared', function (): void {
    Permission::findOrCreate('legacy.ability', 'web');

    $this->artisan('permissions:sync --prune')->assertExitCode(0);

    expect(Permission::where('name', 'legacy.ability')->exists())->toBeFalse();
});
