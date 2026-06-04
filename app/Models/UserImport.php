<?php

namespace App\Models;

use App\Enums\UserImportStatus;
use Database\Factories\UserImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A queued data import owned by a user (upload → preview → process).
 *
 * @property string $token
 * @property UserImportStatus $status
 * @property int $total
 * @property int $success
 * @property int $failed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class UserImport extends BaseModel
{
    /** @use HasFactory<UserImportFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'resource',
        'filename',
        'total',
        'success',
        'failed',
        'error_report_path',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status' => UserImportStatus::class,
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
