<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Database\Factories\OrganizationRoleFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A role defined within an organization, used by teams and their members. This
 * is a separate concept from the spatie auth roles attached to users.
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
 * @property-read Collection<int, Person> $people
 * @property-read int|null $people_count
 */
class OrganizationRole extends BaseModel
{
    /** @use HasFactory<OrganizationRoleFactory> */
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
        return $this->hasMany(Team::class, 'organization_role_id');
    }

    /**
     * @return HasMany<Person, $this>
     */
    public function people(): HasMany
    {
        return $this->hasMany(Person::class, 'organization_role_id');
    }
}
