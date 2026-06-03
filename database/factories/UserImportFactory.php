<?php

namespace Database\Factories;

use App\Enums\UserImportStatus;
use App\Models\User;
use App\Models\UserImport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserImport>
 */
class UserImportFactory extends Factory
{
    protected $model = UserImport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => Str::random(48),
            'resource' => 'users',
            'filename' => 'imports/users-'.Str::random(8).'.csv',
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'error_report_path' => null,
            'status' => UserImportStatus::Pending,
        ];
    }
}
