<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Workflow status for a task card — its progress through review/approval.
 * Distinct from RecordStatus (the Active/Inactive business toggle) and
 * ProjectStatus (the project/asset pipeline).
 */
enum TaskStatus: string
{
    use HasOptions;

    case Pending = 'Pending';
    case InProgress = 'In Progress';
    case Submitted = 'Submitted';
    case Approved = 'Approved';
    case Rejected = 'Rejected';
    case Cancelled = 'Cancelled';
}
