<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // NB: model events must fire during seeding — domain rows depend on the
    // HasToken `creating` hook to populate their (NOT NULL, unique) token. Do
    // not add WithoutModelEvents here or seeded rows fail the token constraint.

    /**
     * Seed the application's database. Order matters: permissions, then roles
     * (which reference permissions), then users (which reference roles). All
     * idempotent so `make setup`/`db:seed` can be re-run safely.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            ThemeSeeder::class,
            IpSeeder::class,
            OrganizationSeeder::class,
            ProjectSeeder::class,
            AssetSeeder::class,
            FormSeeder::class,
            MonitoringSeeder::class,
        ]);
    }
}
