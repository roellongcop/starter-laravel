<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\ReferenceFile;
use Illuminate\Database\Seeder;

class ReferenceFileSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrFail();

        ReferenceFile::firstOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Company Handbook'],
            ['description' => 'A reference document for the organization.'],
        );
    }
}
