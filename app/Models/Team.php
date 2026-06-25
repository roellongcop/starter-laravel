<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A team belonging to an organization, classified by a category and assigned an
 * organization role. Members are users linked through the `people` roster, each
 * inheriting the team's organization role.
 *
 * @property string $token
 * @property string $name
 * @property string|null $description
 * @property int $team_category_id
 * @property int $organization_role_id
 * @property int $organization_id
 * @property RecordStatus $record_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read TeamCategory $category
 * @property-read OrganizationRole $role
 * @property-read Collection<int, Person> $people
 * @property-read int|null $people_count
 */
class Team extends BaseModel
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'team_category_id', 'organization_role_id', 'organization_id'];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<TeamCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TeamCategory::class, 'team_category_id');
    }

    /**
     * @return BelongsTo<OrganizationRole, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(OrganizationRole::class, 'organization_role_id');
    }

    /**
     * @return HasMany<Person, $this>
     */
    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }
}
