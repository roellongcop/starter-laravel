<?php

namespace App\Models;

use App\Enums\VisitLogAction;
use Database\Factories\VisitLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single visitor action/page view.
 *
 * @property VisitLogAction $action
 * @property array<string, mixed>|null $data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class VisitLog extends BaseModel
{
    /** @use HasFactory<VisitLogFactory> */
    use HasFactory;

    protected $fillable = ['visitor_id', 'url', 'action', 'data'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'action' => VisitLogAction::class,
            'data' => 'array',
        ]);
    }

    /**
     * @return BelongsTo<Visitor, $this>
     */
    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }
}
