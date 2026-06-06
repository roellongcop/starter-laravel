<?php

namespace App\Models;

use App\Enums\UserExportStatus;
use Database\Factories\UserExportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A queued data export owned by a user, downloadable via an unguessable token.
 *
 * @property string $token
 * @property UserExportStatus $status
 * @property array<string, mixed>|null $filters
 * @property int|null $row_count
 * @property int|null $total_rows
 * @property int $processed_rows
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class UserExport extends BaseModel
{
    /** @use HasFactory<UserExportFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'format',
        'resource',
        'filters',
        'row_count',
        'total_rows',
        'processed_rows',
        'filename',
        'status',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status' => UserExportStatus::class,
            'filters' => 'array',
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
