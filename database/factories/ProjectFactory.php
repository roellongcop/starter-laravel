<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucwords(fake()->unique()->words(3, true)),
            'description' => fake()->sentence(),
            'private' => fake()->boolean(),
            'organization_id' => Organization::factory(),
        ];
    }

    public function private(): static
    {
        return $this->state(fn () => ['private' => true]);
    }
}
