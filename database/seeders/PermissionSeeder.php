<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Single source of truth: reconcile from config/permissions.php.
        Artisan::call('permissions:sync');
    }
}
