<?php

namespace Database\Seeders;

use App\Models\DataTag;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class DataTagSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrFail();

        foreach (['Priority' => '#ef4444', 'Archived' => '#64748b'] as $name => $color) {
            DataTag::firstOrCreate(
                ['organization_id' => $organization->id, 'name' => $name],
                ['description' => "{$name} tag", 'color' => $color],
            );
        }
    }
}
