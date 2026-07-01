<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Milestone;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Requirement>
 */
class RequirementFactory extends Factory
{
    protected $model = Requirement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'name' => ucwords(fake()->unique()->words(3, true)),
            'description' => fake()->optional()->paragraph(),
            'organization_id' => $organization,
            'project_id' => Project::factory(),
            'milestone_id' => Milestone::factory(),
            'task_id' => Task::factory(),
            'minimum_files' => fake()->optional()->numberBetween(0, 3),
            'maximum_files' => fake()->optional()->numberBetween(3, 10),
            'status' => TaskStatus::Pending,
            'position' => 0,
        ];
    }
}
