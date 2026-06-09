<?php

namespace App\Filters;

use App\Filters\Primitives\DateRangeFilter;
use App\Filters\Primitives\InactiveFilter;
use App\Filters\Primitives\SearchFilter;

class UserFilters extends QueryFilters
{
    /**
     * @return array<int, Filter>
     */
    protected function filters(): array
    {
        return [
            new SearchFilter(columns: ['name', 'email', 'username']),
            new InactiveFilter,
            new DateRangeFilter(column: 'users.created_at'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'inactive' => ['nullable', 'boolean'],
        ];
    }
}
