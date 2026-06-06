<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

/**
 * Bounded sample reader for the import preview page. WithLimit caps the reader at
 * the first few data rows so a 100k-row upload is never fully parsed just to show
 * a sample — the real row count is tallied later in the background DispatchImportJob.
 */
class UsersPreview implements WithHeadingRow, WithLimit
{
    public function limit(): int
    {
        return 10;
    }
}
