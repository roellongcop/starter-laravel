<?php

namespace App\Filters;

use App\Filters\Primitives\InactiveFilter;
use App\Filters\Primitives\SearchFilter;

class AssetFilters extends QueryFilters
{
    /**
     * The search URL param. Defaults to `search` for the standalone index;
     * the organization Show page reassigns it (usingSearchKey('asset_search'))
     * so its asset search box never collides with the projects one.
     */
    protected string $searchKey = 'search';

    public function usingSearchKey(string $key): static
    {
        $this->searchKey = $key;

        return $this;
    }

    /**
     * @return array<int, Filter>
     */
    protected function filters(): array
    {
        return [
            new SearchFilter(columns: ['name', 'id_code', 'address'], key: $this->searchKey),
            new InactiveFilter,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            $this->searchKey => ['nullable', 'string', 'max:255'],
            'inactive' => ['nullable', 'boolean'],
        ];
    }
}
