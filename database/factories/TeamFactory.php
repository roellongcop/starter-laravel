<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\Team;
use App\Models\TeamCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucwords(fake()->unique()->words(2, true)),
            'description' => fake()->sentence(),
            'organization_id' => Organization::factory(),
            // The category and role must live in the same organization as the team.
            'team_category_id' => fn (array $attributes) => TeamCategory::factory()
                ->create(['organization_id' => $attributes['organization_id']])->id,
            'organization_role_id' => fn (array $attributes) => OrganizationRole::factory()
                ->create(['organization_id' => $attributes['organization_id']])->id,
        ];
    }
}
