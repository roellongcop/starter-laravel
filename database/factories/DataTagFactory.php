<?php

namespace Database\Factories;

use App\Models\DataTag;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataTag>
 */
class DataTagFactory extends Factory
{
    protected $model = DataTag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucwords(fake()->unique()->words(2, true)),
            'description' => fake()->sentence(),
            'color' => fake()->randomElement(DataTag::COLORS),
            'organization_id' => Organization::factory(),
        ];
    }
}
