<?php

namespace App\Filters;

use App\Filters\Primitives\SearchFilter;

class BackupFilters extends QueryFilters
{
    /**
     * @return array<int, Filter>
     */
    protected function filters(): array
    {
        return [
            new SearchFilter(columns: ['filename']),
        ];
    }
}
