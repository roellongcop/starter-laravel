<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Enums\TaskStatus;
use App\Models\Concerns\HasDataTags;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * A card inside a milestone. The assignee/approver/observer are polymorphic —
 * each is a Team or a Person inside the task's organization (never a top-level
 * user). organization_id is denormalized from the project so tagging
 * (HasDataTags::syncDataTags) can scope tags without joining up to the asset.
 *
 * @property string $token
 * @property string $name
 * @property string|null $description
 * @property int $milestone_id
 * @property int $organization_id
 * @property TaskStatus $status
 * @property string|null $assignee_type
 * @property int|null $assignee_id
 * @property string|null $approver_type
 * @property int|null $approver_id
 * @property string|null $observer_type
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
 * @property-read Model|null $assignee
 * @property-read Model|null $approver
 * @property-read Model|null $observer
 * @property-read ReferenceFile|null $referenceFile
 * @property-read Collection<int, DataTag> $tags
 * @property-read Collection<int, Requirement> $requirements
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
        'status',
        'assignee_type',
        'assignee_id',
        'approver_type',
        'approver_id',
        'observer_type',
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
            'status' => TaskStatus::class,
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
     * The assigned Team or Person (org-scoped).
     *
     * @return MorphTo<Model, $this>
     */
    public function assignee(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function approver(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function observer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<ReferenceFile, $this>
     */
    public function referenceFile(): BelongsTo
    {
        return $this->belongsTo(ReferenceFile::class);
    }

    /**
     * The deliverables attached to this task, in stable creation order.
     *
     * @return HasMany<Requirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(Requirement::class)->orderBy('position')->orderBy('id');
    }
}
