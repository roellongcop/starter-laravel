<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Database\Factories\MilestoneFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A board column scoped to one project's view of an asset, holding ordered tasks.
 *
 * @property string $token
 * @property string $name
 * @property string|null $description
 * @property int $project_id
 * @property int $asset_id
 * @property int $organization_id
 * @property int $position
 * @property RecordStatus $record_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 * @property-read Asset $asset
 * @property-read Organization $organization
 * @property-read Collection<int, Task> $tasks
 */
class Milestone extends BaseModel
{
    /** @use HasFactory<MilestoneFactory> */
    use HasFactory;

    /** The default milestone seeded onto every project–asset board. */
    public const DEFAULT_NAME = 'Misc';

    protected $fillable = ['name', 'description', 'project_id', 'asset_id', 'organization_id', 'position'];

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position');
    }

    /**
     * Ensure a project's view of an asset has its default "Misc" milestone, so a
     * board is never empty when a binding is first created. Idempotent — re-binding
     * an already-bound pair won't create a second one.
     */
    public static function ensureDefaultFor(Project $project, int $assetId): void
    {
        static::firstOrCreate(
            ['project_id' => $project->getKey(), 'asset_id' => $assetId, 'name' => self::DEFAULT_NAME],
            ['organization_id' => $project->organization_id, 'position' => 0],
        );
    }
}
