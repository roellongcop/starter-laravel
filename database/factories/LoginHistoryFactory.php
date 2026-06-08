<?php

namespace Database\Factories;

use App\Enums\AuthEvent;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoginHistory>
 */
class LoginHistoryFactory extends Factory
{
    protected $model = LoginHistory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event' => fake()->randomElement(AuthEvent::cases()),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function login(): static
    {
        return $this->state(fn () => ['event' => AuthEvent::Login]);
    }

    public function logout(): static
    {
        return $this->state(fn () => ['event' => AuthEvent::Logout]);
    }
}
