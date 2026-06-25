<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Database\Factories\TeamCategoryFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A category used to classify teams, scoped to an organization.
 *
 * @property string $token
 * @property string $name
 * @property string|null $description
 * @property int $organization_id
 * @property RecordStatus $record_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read Collection<int, Team> $teams
 * @property-read int|null $teams_count
 */
class TeamCategory extends BaseModel
{
    /** @use HasFactory<TeamCategoryFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'organization_id'];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class, 'team_category_id');
    }
}
