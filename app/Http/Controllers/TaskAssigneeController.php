<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Person;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Search-as-you-type options for a task's assignee/approver/observer pickers: the
 * Teams and People inside a given organization, merged into one type-tagged list.
 * Mirrors the <CursorPager> envelope used by the other option endpoints, but its
 * "cursor" is a plain offset since the list spans two tables (a per-org set is
 * small, so an in-memory merge + slice is fine). `?tokens[]=` rehydrates selected
 * values regardless of org; org names are already exposed via module filters, so
 * any authenticated user may resolve them.
 */
class TaskAssigneeController extends Controller
{
    public function options(Request $request): JsonResponse
    {
        $tokens = array_values(array_filter((array) $request->input('tokens', [])));

        if ($tokens !== []) {
            return $this->envelope($this->resolveTokens($tokens), false, null);
        }

        $organizationId = Organization::where('token', $request->string('organization'))->value('id');

        if ($organizationId === null) {
            return $this->envelope(collect(), false, null);
        }

        $term = trim((string) $request->string('q'));

        $teams = Team::query()
            ->where('organization_id', $organizationId)
            ->when($term !== '', function (Builder $q) use ($term): void {
                $this->whereNameLike($q, $term);
            })
            ->orderBy('name')
            ->get(['token', 'name'])
            ->map(fn (Team $team): array => $this->teamOption($team));

        $people = Person::query()
            ->where('organization_id', $organizationId)
            ->with('user:id,name')
            ->whereHas('user', function (Builder $q) use ($term): void {
                if ($term !== '') {
                    $this->whereNameLike($q, $term);
                }
            })
            ->get()
            ->map(fn (Person $person): array => $this->personOption($person));

        $all = $teams->concat($people)->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)->values();

        $offset = max(0, (int) $request->input('cursor', 0));
        $limit = (int) config('keen.options_limit');
        $page = $all->slice($offset, $limit)->values();
        $hasMore = $all->count() > $offset + $limit;

        return $this->envelope($page, $hasMore, $hasMore ? (string) ($offset + $limit) : null);
    }

    /**
     * Look up specific selected values (Team or Person) by token, for rehydration.
     *
     * @param  array<int, string>  $tokens
     * @return Collection<int, array{value: string, label: string, type: string}>
     */
    protected function resolveTokens(array $tokens): Collection
    {
        $teams = Team::query()
            ->whereIn('token', $tokens)
            ->get(['token', 'name'])
            ->map(fn (Team $team): array => $this->teamOption($team));

        $people = Person::query()
            ->whereIn('token', $tokens)
            ->with('user:id,name')
            ->get()
            ->map(fn (Person $person): array => $this->personOption($person));

        return $teams->concat($people)->values();
    }

    /**
     * @return array{value: string, label: string, type: string}
     */
    protected function teamOption(Team $team): array
    {
        return ['value' => $team->token, 'label' => $team->name.' (Team)', 'type' => 'team'];
    }

    /**
     * @return array{value: string, label: string, type: string}
     */
    protected function personOption(Person $person): array
    {
        return ['value' => $person->token, 'label' => $person->user->name.' (Person)', 'type' => 'person'];
    }

    /**
     * Case-insensitive, wildcard-escaped match on the `name` column (cross-db).
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    protected function whereNameLike(Builder $query, string $term): void
    {
        $query->whereRaw('name '.like_operator()." ? escape '\\'", ['%'.escape_like($term).'%']);
    }

    /**
     * @param  Collection<int, array{value: string, label: string, type: string}>  $data
     */
    protected function envelope(Collection $data, bool $hasMore, ?string $nextCursor): JsonResponse
    {
        return response()->json([
            'data' => $data->all(),
            'next_cursor' => $nextCursor,
            'prev_cursor' => null,
            'has_more' => $hasMore,
        ]);
    }
}
