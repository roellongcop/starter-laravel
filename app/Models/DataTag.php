<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Database\Factories\DataTagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A coloured tag belonging to an organization, chosen from a fixed palette.
 *
 * @property string $token
 * @property string $name
 * @property string|null $description
 * @property string $color
 * @property int $organization_id
 * @property RecordStatus $record_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 */
class DataTag extends BaseModel
{
    /** @use HasFactory<DataTagFactory> */
    use HasFactory;

    /**
     * The selectable tag colours (hex). The single source of truth shared with
     * the frontend swatch picker and the validation allowlist.
     *
     * @var list<string>
     */
    public const COLORS = [
        '#64748b', // slate
        '#ef4444', // red
        '#f97316', // orange
        '#f59e0b', // amber
        '#22c55e', // green
        '#14b8a6', // teal
        '#3b82f6', // blue
        '#6366f1', // indigo
        '#8b5cf6', // violet
        '#ec4899', // pink
    ];

    protected $fillable = ['name', 'description', 'color', 'organization_id'];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
