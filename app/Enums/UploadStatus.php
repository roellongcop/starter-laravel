<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Lifecycle of a resumable UploadSession: Pending until the first part lands,
 * Uploading while chunks stream in, Assembling during finalize, then a terminal
 * Done / Failed / Aborted.
 */
enum UploadStatus: string
{
    use HasOptions;

    case Pending = 'Pending';
    case Uploading = 'Uploading';
    case Assembling = 'Assembling';
    case Done = 'Done';
    case Failed = 'Failed';
    case Aborted = 'Aborted';
}
