<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\RecordStatus;
use App\Models\Concerns\HasDataTags;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * A project belonging to an organization, optionally marked private.
 *
 * @property string $token
 * @property string $name
 * @property string|null $description
 * @property bool $private
 * @property ProjectStatus $status
 * @property int $organization_id
 * @property RecordStatus $record_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read Collection<int, DataTag> $tags
 * @property-read Collection<int, Asset> $assets
 */
class Project extends BaseModel
{
    use HasDataTags;

    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'private', 'status', 'organization_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'private' => 'boolean',
            'status' => ProjectStatus::class,
        ]);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Top-level org assets bound to this project by reference (no copied
     * attributes) through the project_assets pivot.
     *
     * @return BelongsToMany<Asset, $this>
     */
    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'project_assets')
            ->withPivot('status');
    }
}
