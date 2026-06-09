<?php

namespace App\Filters;

use App\Filters\Primitives\ExactFilter;
use App\Filters\Primitives\SearchFilter;

class LoginHistoryFilters extends QueryFilters
{
    /**
     * @return array<int, Filter>
     */
    protected function filters(): array
    {
        return [
            new SearchFilter(
                columns: ['ip_address'],
                relations: ['user' => ['name', 'email']],
            ),
            new ExactFilter(column: 'event', allowed: ['login', 'logout']),
        ];
    }
}
