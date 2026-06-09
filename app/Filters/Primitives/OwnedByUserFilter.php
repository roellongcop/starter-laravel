<?php

namespace App\Filters\Primitives;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Always-on scoping constraint: restrict rows to the authenticated user. It is
 * not user-toggled, so it is always active and contributes nothing to the
 * `filters` echo-back.
 */
class OwnedByUserFilter extends AbstractFilter
{
    public function __construct(
        protected string $column = 'user_id',
    ) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        $query->where($this->column, $this->request->user()?->id);

        return $next($query);
    }

    public function isActive(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function applied(): array
    {
        return [];
    }
}
