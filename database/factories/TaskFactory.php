<?php

namespace Database\Factories;

use App\Models\Milestone;
use App\Models\Organization;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucwords(fake()->unique()->words(3, true)),
            'description' => fake()->optional()->paragraph(),
            'milestone_id' => Milestone::factory(),
            'organization_id' => Organization::factory(),
            'private' => false,
            'position' => 0,
        ];
    }
}
