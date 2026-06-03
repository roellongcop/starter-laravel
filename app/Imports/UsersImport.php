<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Marker import used with Excel::toCollection() so rows are keyed by their
 * header row. The actual validation/upsert/counting happens in ProcessImportJob
 * for full control over the success/failure report.
 */
class UsersImport implements WithHeadingRow {}
