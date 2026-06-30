<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Milestone;
use App\Models\Person;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;

/**
 * Serializes a milestone/task board into the frontend row shapes. Only tokens and
 * enum values cross the wire (never ids). Relations (assignee/approver/observer/
 * referenceFile/tags) must be eager-loaded by the caller — accessing them here on
 * an unloaded model would trip preventLazyLoading. Assignees are polymorphic, so
 * the morph target (and, for a Person, its user) must be eager-loaded too.
 */
trait SerializesBoard
{
    use ResolvesDataTags;

    /**
     * @return array<string, mixed>
     */
    protected function milestoneRow(Milestone $milestone): array
    {
        return [
            'token' => $milestone->token,
            'name' => $milestone->name,
            'description' => $milestone->description,
            'position' => $milestone->position,
            'is_default' => $milestone->is_default,
            'record_status' => $milestone->record_status->value,
            'created_at' => $milestone->created_at?->toIso8601String(),
            'tasks' => $milestone->tasks
                ->map(fn (Task $task): array => $this->taskRow($task, $milestone->token))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function taskRow(Task $task, string $milestoneToken): array
    {
        return [
            'token' => $task->token,
            'name' => $task->name,
            'description' => $task->description,
            'milestone' => $milestoneToken,
            'status' => $task->status->value,
            'assigned_to' => $this->assigneeChip($task->assignee),
            'approver' => $this->assigneeChip($task->approver),
            'observer' => $this->assigneeChip($task->observer),
            'private' => $task->private,
            'due_date' => $task->due_date?->toDateString(),
            'reference_file' => $task->referenceFile
                ? ['token' => $task->referenceFile->token, 'name' => $task->referenceFile->name]
                : null,
            'tags' => $this->serializeTags($task->tags),
            'position' => $task->position,
            'record_status' => $task->record_status->value,
            'created_at' => $task->created_at?->toIso8601String(),
        ];
    }

    /**
     * Serialize a polymorphic assignee (Team or Person) into a type-tagged chip.
     * A Person's name comes from its linked user (no name column of its own).
     *
     * @return array{type: string, token: string, name: string}|null
     */
    protected function assigneeChip(?Model $entity): ?array
    {
        if ($entity instanceof Team) {
            return ['type' => 'team', 'token' => $entity->token, 'name' => $entity->name];
        }

        if ($entity instanceof Person) {
            return ['type' => 'person', 'token' => $entity->token, 'name' => $entity->user->name];
        }

        return null;
    }
}
