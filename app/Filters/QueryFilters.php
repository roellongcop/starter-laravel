<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;

/**
 * Per-resource filter set. A subclass declares filters() once; the controller
 * calls apply() to narrow its query and echoBack() to hydrate the Inertia
 * `filters` prop. Filters run through Laravel's Pipeline so adding a new one is
 * a single array entry, never another ->when() chain in the controller.
 * See docs/conventions/backend.md.
 */
abstract class QueryFilters
{
    /** @var array<int, Filter>|null request-bound filter instances (resolved once) */
    protected ?array $resolved = null;

    public function __construct(
        protected Request $request,
        protected Pipeline $pipeline,
    ) {}

    /**
     * Declare this resource's filter set.
     *
     * @return array<int, Filter>
     */
    abstract protected function filters(): array;

    /**
     * Run the active filters through the pipeline. Inactive filters (param
     * absent / blank) are skipped, so the SQL matches the prior
     * ->when($x !== '', ...) behavior exactly.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function apply(Builder $query): Builder
    {
        if ($rules = $this->rules()) {
            $this->request->validate($rules);
        }

        $pipes = array_values(array_filter(
            $this->resolve(),
            fn (Filter $filter): bool => $filter->isActive(),
        ));

        /** @var Builder<TModel> $filtered */
        $filtered = $this->pipeline
            ->send($query)
            ->through($pipes)
            ->thenReturn();

        return $filtered;
    }

    /**
     * The flat `filters` prop the frontend hydrates from. Merges every filter's
     * owned keys (active or not) into a single array.
     *
     * @return array<string, mixed>
     */
    public function echoBack(): array
    {
        return array_reduce(
            $this->resolve(),
            fn (array $carry, Filter $filter): array => [...$carry, ...$filter->applied()],
            [],
        );
    }

    /**
     * Optional validation for constrained params (dates, enums). Default: none.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * Bind the request to each declared filter once.
     *
     * @return array<int, Filter>
     */
    protected function resolve(): array
    {
        return $this->resolved ??= array_map(
            fn (Filter $filter): Filter => $filter->forRequest($this->request),
            $this->filters(),
        );
    }
}
