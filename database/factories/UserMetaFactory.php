<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserMeta>
 */
class UserMetaFactory extends Factory
{
    protected $model = UserMeta::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'key' => fake()->unique()->word(),
            'value' => fake()->sentence(3),
        ];
    }
}
