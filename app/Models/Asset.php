<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Models\Concerns\HasDataTags;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * An asset belonging to an organization, identified by a free-form id code.
 *
 * @property string $token
 * @property string $name
 * @property string $id_code
 * @property string $address
 * @property int $organization_id
 * @property RecordStatus $record_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read Collection<int, DataTag> $tags
 * @property-read Collection<int, Project> $projects
 */
class Asset extends BaseModel
{
    use HasDataTags;

    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    protected $fillable = ['name', 'id_code', 'address', 'organization_id'];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Projects this asset is bound to through the project_assets pivot.
     *
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_assets')
            ->withPivot('status');
    }
}
