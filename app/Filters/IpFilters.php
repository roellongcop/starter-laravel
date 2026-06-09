<?php

namespace App\Filters;

use App\Filters\Primitives\InactiveFilter;
use App\Filters\Primitives\SearchFilter;

class IpFilters extends QueryFilters
{
    /**
     * @return array<int, Filter>
     */
    protected function filters(): array
    {
        return [
            new SearchFilter(columns: ['ip_address', 'description']),
            new InactiveFilter,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'inactive' => ['nullable', 'boolean'],
        ];
    }
}
