<?php

namespace App\Models;

use App\Enums\BackupStatus;
use Database\Factories\BackupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Tracks a spatie/laravel-backup archive + its lifecycle status.
 *
 * @property string $token
 * @property BackupStatus $status
 * @property int|null $size
 * @property string|null $error_message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Backup extends BaseModel
{
    /** @use HasFactory<BackupFactory> */
    use HasFactory;

    protected $fillable = ['filename', 'disk', 'size', 'status', 'error_message'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status' => BackupStatus::class,
        ]);
    }

    /**
     * Delete the archive from its disk (if any) and then the row. Shared by the
     * admin delete action and the scheduled prune so both stay in lockstep.
     */
    public function deleteWithFile(): void
    {
        if ($this->filename) {
            Storage::disk($this->disk)->delete($this->filename);
        }

        $this->delete();
    }
}
