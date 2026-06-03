<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Database\Factories\VisitorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A tracked anonymous/known visitor (cookie-based), populated by TrackVisitor.
 *
 * @property string $cookie_id
 * @property int $visit_count
 * @property Carbon|null $last_visit_at
 * @property Carbon|null $expires_at
 * @property RecordStatus $record_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Visitor extends BaseModel
{
    /** @use HasFactory<VisitorFactory> */
    use HasFactory;

    protected $fillable = [
        'cookie_id',
        'ip_address',
        'browser',
        'os',
        'device',
        'session_id',
        'visit_count',
        'last_visit_at',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'last_visit_at' => 'datetime',
            'expires_at' => 'datetime',
        ]);
    }

    /**
     * @return HasMany<VisitLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(VisitLog::class);
    }
}
