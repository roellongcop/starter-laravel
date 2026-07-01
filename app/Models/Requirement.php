<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Enums\TaskStatus;
use App\Models\Concerns\HasDataTags;
use Database\Factories\RequirementFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A deliverable attached to a task. project_id/milestone_id/organization_id are
 * denormalized from the owning task so tagging (HasDataTags::syncDataTags) can
 * org-scope tags without joining up the chain. It carries its own workflow
 * status (the same TaskStatus enum as the task) and bounds how many files
 * satisfy it via minimum_files/maximum_files.
 *
 * @property string $token
 * @property string $name
 * @property string|null $description
 * @property int $organization_id
 * @property int $project_id
 * @property int $milestone_id
 * @property int $task_id
 * @property int|null $minimum_files
 * @property int|null $maximum_files
 * @property int|null $reference_file_id
 * @property int|null $form_id
 * @property TaskStatus $status
 * @property int $position
 * @property RecordStatus $record_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read Project $project
 * @property-read Milestone $milestone
 * @property-read Task $task
 * @property-read ReferenceFile|null $referenceFile
 * @property-read Form|null $form
 * @property-read Collection<int, DataTag> $tags
 */
class Requirement extends BaseModel
{
    use HasDataTags;

    /** @use HasFactory<RequirementFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'organization_id',
        'project_id',
        'milestone_id',
        'task_id',
        'minimum_files',
        'maximum_files',
        'reference_file_id',
        'form_id',
        'status',
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
            'minimum_files' => 'integer',
            'maximum_files' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Milestone, $this>
     */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @return BelongsTo<ReferenceFile, $this>
     */
    public function referenceFile(): BelongsTo
    {
        return $this->belongsTo(ReferenceFile::class);
    }

    /**
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
