<?php

namespace App\Filters\Primitives;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Inclusive date range on a single column. Two URL params (from/to) live in one
 * filter so they hydrate together; either bound may be supplied independently.
 * The column should be table-qualified by the caller (e.g. 'users.created_at').
 */
class DateRangeFilter extends AbstractFilter
{
    public function __construct(
        protected string $column,
        protected string $fromKey = 'date_from',
        protected string $toKey = 'date_to',
    ) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        $from = $this->param($this->fromKey);
        $to = $this->param($this->toKey);

        $query
            ->when($from !== '', fn (Builder $q): Builder => $q->whereDate($this->column, '>=', $from))
            ->when($to !== '', fn (Builder $q): Builder => $q->whereDate($this->column, '<=', $to));

        return $next($query);
    }

    public function isActive(): bool
    {
        return $this->param($this->fromKey) !== '' || $this->param($this->toKey) !== '';
    }

    /**
     * @return array<string, string>
     */
    public function applied(): array
    {
        return [
            $this->fromKey => $this->param($this->fromKey),
            $this->toKey => $this->param($this->toKey),
        ];
    }
}
