<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Workflow status shared by a project and each of its bound assets (the
 * project_assets pivot). Distinct from RecordStatus (the Active/Inactive
 * business toggle) — this tracks progress, not visibility.
 */
enum ProjectStatus: string
{
    use HasOptions;

    case Pending = 'Pending';
    case InProgress = 'In Progress';
    case Approved = 'Approved';
    case Cancelled = 'Cancelled';
}
