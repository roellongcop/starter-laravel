<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrFail();

        Asset::firstOrCreate(
            ['name' => 'HQ Building'],
            [
                'id_code' => 'AST-0001',
                'address' => '123 Market Street, Springfield',
                'organization_id' => $organization->id,
            ],
        );

        Asset::firstOrCreate(
            ['name' => 'Delivery Van'],
            [
                'id_code' => 'AST-0002',
                'address' => '456 Industrial Ave, Springfield',
                'organization_id' => $organization->id,
            ],
        );
    }
}
