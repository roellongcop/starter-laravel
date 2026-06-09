<?php

namespace App\Filters;

use App\Filters\Primitives\SearchFilter;

class FileFilters extends QueryFilters
{
    /**
     * @return array<int, Filter>
     */
    protected function filters(): array
    {
        return [
            new SearchFilter(columns: ['original_name', 'tag']),
        ];
    }
}
