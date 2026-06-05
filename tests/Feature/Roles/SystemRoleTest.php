<?php

use App\Enums\SystemRole;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

it('seeds a role for every SystemRole enum case', function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);

    $seeded = Role::query()->pluck('name')->all();

    foreach (SystemRole::values() as $name) {
        expect($seeded)->toContain($name);
    }
});
