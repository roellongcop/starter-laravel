<?php

namespace App\Filters;

use App\Filters\Primitives\InactiveFilter;
use App\Filters\Primitives\OrganizationFilter;
use App\Filters\Primitives\SearchFilter;

class TeamCategoryFilters extends QueryFilters
{
    /**
     * @return array<int, Filter>
     */
    protected function filters(): array
    {
        return [
            new SearchFilter(columns: ['name', 'description']),
            new OrganizationFilter,
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
            'organization' => ['nullable', 'string', 'exists:organizations,token'],
            'inactive' => ['nullable', 'boolean'],
        ];
    }
}
