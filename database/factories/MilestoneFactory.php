<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Milestone;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Milestone>
 */
class MilestoneFactory extends Factory
{
    protected $model = Milestone::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucwords(fake()->unique()->words(2, true)),
            'description' => fake()->optional()->sentence(),
            'project_id' => Project::factory(),
            'asset_id' => Asset::factory(),
            'organization_id' => Organization::factory(),
            'position' => 0,
        ];
    }
}
