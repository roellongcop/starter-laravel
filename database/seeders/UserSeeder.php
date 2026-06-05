<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Demo logins — password equals the email address. Idempotent.
        $demo = [
            ['name' => 'Developer', 'email' => 'developer@developer.com', 'role' => SystemRole::Developer->value],
            ['name' => 'Super Admin', 'email' => 'superadmin@superadmin.com', 'role' => SystemRole::Superadmin->value],
            ['name' => 'Admin', 'email' => 'admin@admin.com', 'role' => SystemRole::Admin->value],
        ];

        foreach ($demo as $row) {
            $user = User::firstOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => $row['email'],
                    'password_hint' => 'Your password is your email address.',
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$row['role']]);
        }

        // Sample data so the list pages have content (only on an empty-ish table).
        if (User::query()->count() <= count($demo)) {
            User::factory()
                ->count(25)
                ->create()
                ->each(function (User $user): void {
                    $user->assignRole(SystemRole::Admin->value);
                    $user->meta()->createMany([
                        ['key' => 'department', 'value' => fake()->randomElement(['Sales', 'Support', 'Engineering'])],
                        ['key' => 'phone', 'value' => fake()->phoneNumber()],
                    ]);
                });
        }
    }
}
