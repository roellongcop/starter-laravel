<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An organization with a name, description, and a point-of-contact user.
 *
 * @property string $token
 * @property string $name
 * @property string|null $description
 * @property int|null $point_of_contact_id
 * @property RecordStatus $record_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $pointOfContact
 * @property-read Collection<int, Project> $projects
 * @property-read Collection<int, Asset> $assets
 */
class Organization extends BaseModel
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'point_of_contact_id'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function pointOfContact(): BelongsTo
    {
        return $this->belongsTo(User::class, 'point_of_contact_id');
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<Asset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
