<?php

namespace App\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * A single, composable query filter. Each filter is a pipe in the QueryFilters
 * pipeline: it reads its own param(s) off the bound request, narrows the query,
 * and hands off to the next pipe. See docs/conventions/backend.md.
 */
interface Filter
{
    /**
     * Apply this filter to the query, then continue the pipeline.
     *
     * @param  Builder<Model>  $query
     * @param  Closure(Builder<Model>): Builder<Model>  $next
     * @return Builder<Model>
     */
    public function handle(Builder $query, Closure $next): Builder;

    /**
     * Bind the current request so the filter can read its param(s). Returns
     * $this for fluent registration inside QueryFilters::filters().
     */
    public function forRequest(Request $request): static;

    /**
     * Whether this filter's param is present (and non-blank) on the request.
     * When false the pipe is skipped entirely — no SQL is emitted.
     */
    public function isActive(): bool;

    /**
     * The value(s) this filter contributes to the `filters` Inertia prop, keyed
     * by URL param name. Returns every key the filter owns (active or not) so
     * the frontend hydrates inputs with '' / false defaults.
     *
     * @return array<string, mixed>
     */
    public function applied(): array;
}
