<?php

namespace App\Filters\Primitives;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Multi-column OR'd LIKE search, with optional relationship columns matched via
 * whereHas. LIKE metacharacters in the term are escaped so they match literally.
 */
class SearchFilter extends AbstractFilter
{
    /**
     * @param  array<int, string>  $columns  own-table columns, e.g. ['name', 'email']
     * @param  array<string, array<int, string>>  $relations  e.g. ['user' => ['name', 'email']]
     * @param  string  $key  URL param name
     */
    public function __construct(
        protected array $columns,
        protected array $relations = [],
        protected string $key = 'search',
    ) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        $pattern = $this->likePattern($this->param($this->key));

        $query->where(function (Builder $w) use ($pattern): void {
            foreach ($this->columns as $column) {
                $this->whereLike($w, $column, $pattern, 'or');
            }

            foreach ($this->relations as $relation => $columns) {
                $w->orWhereHas($relation, function (Builder $r) use ($columns, $pattern): void {
                    $r->where(function (Builder $rw) use ($columns, $pattern): void {
                        foreach ($columns as $column) {
                            $this->whereLike($rw, $column, $pattern, 'or');
                        }
                    });
                });
            }
        });

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
