<?php

namespace Database\Factories;

use App\Enums\UserExportStatus;
use App\Models\User;
use App\Models\UserExport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserExport>
 */
class UserExportFactory extends Factory
{
    protected $model = UserExport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => Str::random(48),
            'format' => fake()->randomElement(['csv', 'xlsx', 'pdf']),
            'resource' => 'users',
            'filters' => [],
            'row_count' => fake()->numberBetween(0, 100),
            'filename' => 'exports/users-'.Str::random(8).'.csv',
            'status' => UserExportStatus::Done,
            'error_message' => null,
        ];
    }
}
