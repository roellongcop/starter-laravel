<?php

namespace Database\Factories;

use App\Enums\BackupStatus;
use App\Models\Backup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Backup>
 */
class BackupFactory extends Factory
{
    protected $model = Backup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'filename' => 'backups/'.fake()->date('Y-m-d').'-'.fake()->time('His').'.zip',
            'disk' => 'backups',
            'size' => fake()->numberBetween(1024, 1024 * 1024),
            'status' => BackupStatus::Generated,
        ];
    }
}
