<?php

namespace Database\Factories;

use App\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Visitor>
 */
class VisitorFactory extends Factory
{
    protected $model = Visitor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cookie_id' => (string) Str::uuid(),
            'ip_address' => fake()->ipv4(),
            'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'os' => fake()->randomElement(['Windows', 'macOS', 'Linux', 'Android', 'iOS']),
            'device' => fake()->randomElement(['Desktop', 'Mobile', 'Tablet']),
            'session_id' => (string) Str::random(40),
            'visit_count' => fake()->numberBetween(1, 50),
            'last_visit_at' => now()->subMinutes(fake()->numberBetween(0, 10000)),
            'expires_at' => now()->addYear(),
        ];
    }
}
