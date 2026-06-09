<?php

namespace App\Filters;

use App\Filters\Primitives\ExactFilter;

class LogFilters extends QueryFilters
{
    /**
     * @return array<int, Filter>
     */
    protected function filters(): array
    {
        return [
            new ExactFilter(column: 'event'),
            new ExactFilter(column: 'auditable_type', key: 'type', like: true),
        ];
    }
}
