<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\TeamCategory;
use Illuminate\Database\Seeder;

class TeamCategorySeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrFail();

        foreach (['Engineering', 'Operations', 'Sales'] as $name) {
            TeamCategory::firstOrCreate(
                ['organization_id' => $organization->id, 'name' => $name],
                ['description' => "{$name} teams"],
            );
        }
    }
}
