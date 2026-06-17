<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $contact = User::query()->where('email', 'admin@admin.com')->value('id');

        Organization::firstOrCreate(
            ['name' => 'Acme Corporation'],
            ['description' => 'Primary demo organization.', 'point_of_contact_id' => $contact],
        );

        Organization::firstOrCreate(
            ['name' => 'Globex Industries'],
            ['description' => 'Secondary demo organization.', 'point_of_contact_id' => null],
        );
    }
}
