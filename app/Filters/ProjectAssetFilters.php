<?php

namespace App\Filters;

use App\Filters\Primitives\SearchFilter;

/**
 * Search over a project's bound assets (name / id code / address). Mirrors
 * AssetFilters' search so the project page filters the same fields server-side.
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }
}
