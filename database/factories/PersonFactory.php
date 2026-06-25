<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            // Inherit the team's org-role + organization.
            'organization_role_id' => fn (array $attributes) => Team::findOrFail($attributes['team_id'])->organization_role_id,
            'organization_id' => fn (array $attributes) => Team::findOrFail($attributes['team_id'])->organization_id,
        ];
    }
}
