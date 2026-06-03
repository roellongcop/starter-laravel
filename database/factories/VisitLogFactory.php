<?php

namespace Database\Factories;

use App\Enums\VisitLogAction;
use App\Models\VisitLog;
use App\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisitLog>
 */
class VisitLogFactory extends Factory
{
    protected $model = VisitLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visitor_id' => Visitor::factory(),
            'url' => '/'.fake()->randomElement(['dashboard', 'users', 'files', 'settings']),
            'action' => fake()->randomElement(VisitLogAction::cases()),
            'data' => null,
        ];
    }
}
