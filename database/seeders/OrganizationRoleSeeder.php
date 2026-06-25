<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\OrganizationRole;
use Illuminate\Database\Seeder;

class OrganizationRoleSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrFail();

        foreach (['Lead', 'Member', 'Coordinator'] as $name) {
            OrganizationRole::firstOrCreate(
                ['organization_id' => $organization->id, 'name' => $name],
                ['description' => "{$name} role"],
            );
        }
    }
}
