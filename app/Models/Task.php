<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Models\Concerns\HasDataTags;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A card inside a milestone — optionally assigned to users, tagged, and linked to
 * a reference file. organization_id is denormalized from the project so tagging
 * (HasDataTags::syncDataTags) can scope tags without joining up to the asset.
 *
 * @property string $token
 * @property string $name
 * @property string|null $description
 * @property int $milestone_id
 * @property int $organization_id
 * @property int|null $assigned_to_id
 * @property int|null $approver_id
 * @property int|null $observer_id
 * @property bool $private
 * @property Carbon|null $due_date
 * @property int|null $reference_file_id
 * @property int $position
 * @property RecordStatus $record_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Milestone $milestone
 * @property-read Organization $organization
 * @property-read User|null $assignee
 * @property-read User|null $approver
 * @property-read User|null $observer
 * @property-read ReferenceFile|null $referenceFile
 * @property-read Collection<int, DataTag> $tags
 */
class Task extends BaseModel
{
    use HasDataTags;

    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'milestone_id',
        'organization_id',
        'assigned_to_id',
        'approver_id',
        'observer_id',
        'private',
        'due_date',
        'reference_file_id',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'private' => 'boolean',
            'due_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Milestone, $this>
     */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function observer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'observer_id');
    }

    /**
     * @return BelongsTo<ReferenceFile, $this>
     */
    public function referenceFile(): BelongsTo
    {
        return $this->belongsTo(ReferenceFile::class);
    }
}
