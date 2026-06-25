<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\ReferenceFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferenceFile>
 */
class ReferenceFileFactory extends Factory
{
    protected $model = ReferenceFile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucwords(fake()->unique()->words(2, true)),
            'description' => fake()->sentence(),
            'organization_id' => Organization::factory(),
            'file_id' => null,
        ];
    }
}
