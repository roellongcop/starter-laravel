<?php

namespace App\Filters\Primitives;

use App\Models\Organization;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Narrow a resource to a single organization, selected by its public token
 * (never the internal id). An unknown token resolves to null → no rows, which
 * is the safe default for a bad/forged param.
 */
class OrganizationFilter extends AbstractFilter
{
    public function __construct(
        protected string $column = 'organization_id',
        protected string $key = 'organization',
    ) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        $id = Organization::where('token', $this->param($this->key))->value('id');
        $query->where($this->column, $id);

        return $next($query);
    }

    public function isActive(): bool
    {
        return $this->param($this->key) !== '';
    }

    /**
     * @return array<string, string>
     */
    public function applied(): array
    {
        return [$this->key => $this->param($this->key)];
    }
}
