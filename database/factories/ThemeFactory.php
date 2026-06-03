<?php

namespace Database\Factories;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Theme>
 */
class ThemeFactory extends Factory
{
    protected $model = Theme::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hue = fake()->numberBetween(0, 360);

        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'preview_image' => null,
            'tokens' => [
                'light' => ['--primary' => "{$hue} 47% 11%", '--background' => '0 0% 100%'],
                'dark' => ['--primary' => "{$hue} 40% 98%", '--background' => '222 84% 5%'],
            ],
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }
}
