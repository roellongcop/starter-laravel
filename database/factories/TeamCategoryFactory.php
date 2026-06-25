<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\TeamCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamCategory>
 */
class TeamCategoryFactory extends Factory
{
    protected $model = TeamCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucwords(fake()->unique()->words(2, true)),
            'description' => fake()->sentence(),
            'organization_id' => Organization::factory(),
        ];
    }
}
