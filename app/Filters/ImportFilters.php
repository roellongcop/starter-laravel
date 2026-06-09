<?php

namespace App\Filters;

use App\Filters\Primitives\OwnedByUserFilter;
use App\Filters\Primitives\SearchFilter;

class ImportFilters extends QueryFilters
{
    /**
     * @return array<int, Filter>
     */
    protected function filters(): array
    {
        return [
            new OwnedByUserFilter,
            new SearchFilter(columns: ['resource', 'filename']),
        ];
    }
}
