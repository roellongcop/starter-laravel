<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationRole>
 */
class OrganizationRoleFactory extends Factory
{
    protected $model = OrganizationRole::class;

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
