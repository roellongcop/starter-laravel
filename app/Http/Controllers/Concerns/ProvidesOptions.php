<?php

namespace App\Http\Controllers\Concerns;

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
 * so the payload never grows with the table. See UserController@options and
 * docs/decisions/0002-keyset-cursor-pagination.md.
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
     * @param  array<int, string>  $columns  selected columns (must cover token, name + anything $toOption reads)
     */
    protected function optionsResponse(
        Request $request,
        string $model,
        callable $toOption,
        array $columns = ['token', 'name'],
    ): JsonResponse {
        // Rehydrating specific selected values: a small, fixed set, queried
        // by token (tokens are unique) so an edited record's chips resolve.
        $tokens = array_values(array_filter((array) $request->input('tokens', [])));

        if ($tokens !== []) {
            return response()->json([
                'data' => $model::query()
                    ->whereIn('token', $tokens)
                    ->orderBy('name')
                    ->get($columns)
                    ->map($toOption)
                    ->all(),
                'next_cursor' => null,
                'prev_cursor' => null,
                'has_more' => false,
            ]);
        }

        $query = $model::query();

        $term = trim((string) $request->string('q'));

        if ($term !== '') {
            $this->applyNameSearch($query, $term);
        }

        // Name tiebreaks on the (unique) token so the keyset cursor is stable.
        $paginator = $query
            ->orderBy('name')
            ->orderBy('token')
            ->cursorPaginate((int) config('keen.options_limit'), $columns);

        return response()->json(cursorResponse($paginator, $toOption));
    }

    /**
     * Case-insensitive, wildcard-escaped name match, cross-db (Postgres ILIKE /
     * SQLite LIKE) — mirrors App\Filters\Primitives\AbstractFilter so the picker
     * search behaves like the list-page search filters.
     *
     * @param  Builder<Model>  $query
     */
    protected function applyNameSearch(Builder $query, string $term): void
    {
        $query->whereRaw('name '.like_operator()." ? escape '\\'", ['%'.escape_like($term).'%']);
    }
}
