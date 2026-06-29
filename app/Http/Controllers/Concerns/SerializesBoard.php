<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Milestone;
use App\Models\Task;
use App\Models\User;

/**
 * Serializes a milestone/task board into the frontend row shapes. Only tokens and
 * enum values cross the wire (never ids). Relations (assignee/approver/observer/
 * referenceFile/tags) must be eager-loaded by the caller — accessing them here on
 * an unloaded model would trip preventLazyLoading.
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
            'assigned_to' => $this->userChip($task->assignee),
            'approver' => $this->userChip($task->approver),
            'observer' => $this->userChip($task->observer),
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
     * @return array{token: string, name: string}|null
     */
    protected function userChip(?User $user): ?array
    {
        return $user ? ['token' => $user->token, 'name' => $user->name] : null;
    }
}
