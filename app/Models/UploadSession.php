<?php

namespace App\Models;

use App\Enums\UploadStatus;
use Database\Factories\UploadSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A resumable chunked upload in progress. Each delivered chunk is recorded in
 * upload_session_parts so a flaky-network client can resume from the gap; on
 * finalize the assembled object becomes a File. Route-bound by token.
 *
 * @property int $id
 * @property string $token
 * @property string $original_name
 * @property string|null $extension
 * @property string|null $mime
 * @property int $size
 * @property int $chunk_size
 * @property int $total_chunks
 * @property string $driver
 * @property string|null $s3_upload_id
 * @property string $object_key
 * @property UploadStatus $status
 * @property string|null $error_message
 * @property int|null $owner_id
 * @property string|null $tag
 * @property int|null $file_id
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class UploadSession extends BaseModel
{
    /** @use HasFactory<UploadSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'token',
        'original_name',
        'extension',
        'mime',
        'size',
        'chunk_size',
        'total_chunks',
        'driver',
        's3_upload_id',
        'object_key',
        'status',
        'error_message',
        'owner_id',
        'tag',
        'file_id',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status' => UploadStatus::class,
            'expires_at' => 'datetime',
        ]);
    }

    /**
     * @return HasMany<UploadSessionPart, $this>
     */
    public function parts(): HasMany
    {
        return $this->hasMany(UploadSessionPart::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Part numbers already durably stored, ascending. The resume gap the client
     * must still upload is the complement of this within 1..total_chunks.
     *
     * @return array<int, int>
     */
    public function receivedPartNumbers(): array
    {
        return $this->parts()->orderBy('part_number')->pluck('part_number')->all();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
