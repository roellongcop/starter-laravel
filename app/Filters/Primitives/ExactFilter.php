<?php

namespace App\Filters\Primitives;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Match a column exactly, or (in `like` mode) by a contains LIKE. An optional
 * allow-list makes the filter a no-op for values outside the set.
 */
class ExactFilter extends AbstractFilter
{
    protected string $key;

    /**
     * @param  array<int, string>|null  $allowed  when set, the filter is inactive unless the value is in this list
     */
    public function __construct(
        protected string $column,
        ?string $key = null,
        protected ?array $allowed = null,
        protected bool $like = false,
    ) {
        $this->key = $key ?? $column;
    }

    public function handle(Builder $query, Closure $next): Builder
    {
        $value = $this->param($this->key);

        if ($this->like) {
            $this->whereLike($query, $this->column, $this->likePattern($value));
        } else {
            $query->where($this->column, $value);
        }

        return $next($query);
    }

    public function isActive(): bool
    {
        $value = $this->param($this->key);

        if ($value === '') {
            return false;
        }

        return $this->allowed === null || in_array($value, $this->allowed, true);
    }

    /**
     * @return array<string, string>
     */
    public function applied(): array
    {
        return [$this->key => $this->param($this->key)];
    }
}
