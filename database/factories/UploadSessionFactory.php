<?php

namespace Database\Factories;

use App\Enums\UploadStatus;
use App\Models\UploadSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UploadSession>
 *
 * Metadata-only — does not begin a real multipart upload or store chunks. Drive
 * the controller endpoints when a backing object is required.
 */
class UploadSessionFactory extends Factory
{
    protected $model = UploadSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->slug(2);
        $chunk = 8 * 1024 * 1024;
        $size = fake()->numberBetween($chunk + 1, $chunk * 5);

        return [
            'original_name' => "{$name}.pdf",
            'extension' => 'pdf',
            'mime' => 'application/pdf',
            'size' => $size,
            'chunk_size' => $chunk,
            'total_chunks' => (int) ceil($size / $chunk),
            'driver' => 'local',
            's3_upload_id' => null,
            'object_key' => now()->format('Y/m').'/'.fake()->sha1().'.pdf',
            'status' => UploadStatus::Uploading,
            'owner_id' => null,
            'tag' => null,
            'file_id' => null,
            'expires_at' => now()->addHours(24),
        ];
    }
}
