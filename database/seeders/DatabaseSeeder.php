<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

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
            MonitoringSeeder::class,
        ]);
    }
}
