<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Shared backend for the async option pickers (search-as-you-type selects).
 * Builds the <CursorPager>-style envelope ({ data, next_cursor, prev_cursor,
 * has_more }) so every picker endpoint behaves identically: `?q=` searches by
 * name, `?cursor=` pages more rows in on scroll, `?tokens[]=` rehydrates the
 * labels of already-selected values. Each page is capped at keen.options_limit
 * so the payload never grows with the table. See OrganizationController@options
 * and docs/decisions/0002-keyset-cursor-pagination.md.
 */
trait ProvidesOptions
{
    /**
     * Build the cursor-paginated options envelope for a model.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @param  callable(TModel): array{value: string, label: string}  $toOption
     * @param  array<int, string>  $columns  selected columns (must cover token, the search column + anything $toOption reads)
     * @param  bool  $organizationScoped  cascade pickers: constrain to `?organization=`'s rows (no/unknown org → none)
     * @param  string  $searchColumn  the label column to search + order by (e.g. 'title' for Forms)
     */
    protected function optionsResponse(
        Request $request,
        string $model,
        callable $toOption,
        array $columns = ['token', 'name'],
        bool $organizationScoped = false,
        string $searchColumn = 'name',
    ): JsonResponse {
        // Rehydrating specific selected values: a small, fixed set, queried
        // unscoped (tokens are unique) so an edited record's chips resolve even
        // if they fall outside the current scope.
        $tokens = array_values(array_filter((array) $request->input('tokens', [])));

        if ($tokens !== []) {
            return response()->json([
                'data' => $model::query()
                    ->whereIn('token', $tokens)
                    ->orderBy($searchColumn)
                    ->get($columns)
                    ->map($toOption)
                    ->all(),
                'next_cursor' => null,
                'prev_cursor' => null,
                'has_more' => false,
            ]);
        }

        $query = $model::query();

        if ($organizationScoped) {
            // Cascade selects live under one organization; an absent/unknown org
            // token yields no rows rather than the whole table.
            $organizationId = Organization::where('token', $request->string('organization'))->value('id');

            if ($organizationId === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('organization_id', $organizationId);
            }
        }

        $term = trim((string) $request->string('q'));

        if ($term !== '') {
            $this->applyNameSearch($query, $term, $searchColumn);
        }

        // The label column tiebreaks on the (unique) token so the keyset cursor is stable.
        $paginator = $query
            ->orderBy($searchColumn)
            ->orderBy('token')
            ->cursorPaginate((int) config('keen.options_limit'), $columns);

        return response()->json(cursorResponse($paginator, $toOption));
    }

    /**
     * Case-insensitive, wildcard-escaped label match, cross-db (Postgres ILIKE /
     * SQLite LIKE) — mirrors App\Filters\Primitives\AbstractFilter so the picker
     * search behaves like the list-page search filters. The column is a fixed
     * caller-supplied identifier (never user input), so it's safe to interpolate.
     *
     * @param  Builder<Model>  $query
     */
    protected function applyNameSearch(Builder $query, string $term, string $column = 'name'): void
    {
        $query->whereRaw($column.' '.like_operator()." ? escape '\\'", ['%'.escape_like($term).'%']);
    }
}
