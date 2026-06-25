<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A team member: a user's membership in a team. Joins to the user (no name is
 * copied) and carries the organization role inherited from the team, kept
 * separate from the user's auth roles. Internal roster row managed through
 * TeamController — it has no module/route of its own.
 *
 * @property string $token
 * @property int $team_id
 * @property int $user_id
 * @property int $organization_role_id
 * @property int $organization_id
 * @property RecordStatus $record_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read User $user
 * @property-read OrganizationRole $role
 * @property-read Organization $organization
 */
class Person extends BaseModel
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    protected $fillable = ['team_id', 'user_id', 'organization_role_id', 'organization_id'];

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<OrganizationRole, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(OrganizationRole::class, 'organization_role_id');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
