<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrFail();

        Project::firstOrCreate(
            ['name' => 'Website Redesign'],
            [
                'description' => 'Public marketing site refresh.',
                'private' => false,
                'organization_id' => $organization->id,
            ],
        );

        Project::firstOrCreate(
            ['name' => 'Internal Tooling'],
            [
                'description' => 'Private back-office utilities.',
                'private' => true,
                'organization_id' => $organization->id,
            ],
        );
    }
}
