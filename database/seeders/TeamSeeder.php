<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\Person;
use App\Models\Team;
use App\Models\TeamCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrFail();
        $category = TeamCategory::query()->where('organization_id', $organization->id)->firstOrFail();
        $role = OrganizationRole::query()->where('organization_id', $organization->id)->firstOrFail();

        $team = Team::firstOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Core Team'],
            [
                'description' => 'The primary team for the organization.',
                'team_category_id' => $category->id,
                'organization_role_id' => $role->id,
            ],
        );

        // Seed a couple of members, each inheriting the team's role + organization.
        User::query()->take(2)->get()->each(function (User $user) use ($team): void {
            Person::firstOrCreate(
                ['team_id' => $team->id, 'user_id' => $user->id],
                [
                    'organization_role_id' => $team->organization_role_id,
                    'organization_id' => $team->organization_id,
                ],
            );
        });
    }
}
