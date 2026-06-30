<?php

namespace App\Filters;

use App\Enums\ProjectStatus;
use App\Filters\Primitives\ExactFilter;
use App\Filters\Primitives\SearchFilter;
use Illuminate\Validation\Rule;

/**
 * Search over a project's bound assets (name / id code / address) and filter by
 * the per-project pivot status. Mirrors AssetFilters' search so the project page
 * filters the same fields server-side; `status` lives on the project_assets
 * pivot (joined by the belongsToMany relation), so it's qualified to avoid an
 * ambiguous column.
 */
class ProjectAssetFilters extends QueryFilters
{
    /**
     * @return array<int, Filter>
     */
    protected function filters(): array
    {
        return [
            new SearchFilter(columns: ['name', 'id_code', 'address']),
            new ExactFilter(column: 'project_assets.status', key: 'status', allowed: ProjectStatus::values()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(ProjectStatus::class)],
        ];
    }
}
