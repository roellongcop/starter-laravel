<?php

namespace App\Filters;

use App\Enums\ProjectStatus;
use App\Filters\Primitives\ExactFilter;
use App\Filters\Primitives\InactiveFilter;
use App\Filters\Primitives\OrganizationFilter;
use App\Filters\Primitives\SearchFilter;
use Illuminate\Validation\Rule;

class ProjectFilters extends QueryFilters
{
    /**
     * @return array<int, Filter>
     */
    protected function filters(): array
    {
        return [
            new SearchFilter(columns: ['name', 'description']),
            new OrganizationFilter,
            new ExactFilter(column: 'status', allowed: ProjectStatus::values()),
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
            'status' => ['nullable', Rule::enum(ProjectStatus::class)],
            'inactive' => ['nullable', 'boolean'],
        ];
    }
}
