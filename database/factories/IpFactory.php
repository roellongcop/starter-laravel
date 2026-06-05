<?php

namespace Database\Factories;

use App\Enums\IpListType;
use App\Models\Ip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ip>
 */
class IpFactory extends Factory
{
    protected $model = Ip::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ip_address' => fake()->unique()->ipv4(),
            'list_type' => fake()->randomElement(IpListType::cases()),
            'description' => fake()->sentence(3),
        ];
    }

    public function whitelist(): static
    {
        return $this->state(fn () => ['list_type' => IpListType::Whitelist]);
    }

    public function blacklist(): static
    {
        return $this->state(fn () => ['list_type' => IpListType::Blacklist]);
    }
}
