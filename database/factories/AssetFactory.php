<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucwords(fake()->unique()->words(3, true)),
            'id_code' => fake()->unique()->bothify('AST-####'),
            'address' => fake()->address(),
            'organization_id' => Organization::factory(),
        ];
    }
}
