<?php

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<File>
 *
 * Metadata-only — does not create real media. Use the controller (or addMedia in
 * a test) when a backing file is required.
 */
class FileFactory extends Factory
{
    protected $model = File::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->slug(2);

        return [
            'original_name' => "{$name}.png",
            'extension' => 'png',
            'mime' => 'image/png',
            'size' => fake()->numberBetween(1024, 1024 * 500),
            'disk' => 'uploads',
            'path' => "uploads/{$name}.png",
            'owner_id' => null,
            'tag' => fake()->randomElement([null, 'avatar', 'document', 'banner']),
        ];
    }
}
