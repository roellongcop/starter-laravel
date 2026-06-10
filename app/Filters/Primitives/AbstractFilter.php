<?php

namespace App\Filters\Primitives;

use App\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Http\Request;

/**
 * Shared request-binding + param-reading plumbing for the concrete filter
 * primitives, so each one only implements its query logic.
 */
abstract class AbstractFilter implements Filter
{
    protected Request $request;

    public function forRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    /** Trimmed string value of a flat URL param ('' when absent). */
    protected function param(string $key): string
    {
        return trim((string) $this->request->string($key));
    }

    /**
     * Build a '%term%' pattern with LIKE metacharacters in the term escaped so
     * user input matches literally, not as wildcards.
     */
    protected function likePattern(string $value): string
    {
        return '%'.escape_like($value).'%';
    }

    /**
     * Add a wildcard-escaped, case-insensitive LIKE condition. The pattern is
     * bound as a param; the operator is inlined because Postgres LIKE is
     * case-sensitive (use ILIKE) while SQLite — used by the test suite — matches
     * LIKE case-insensitively. The backslash escape char is '\' on both.
     *
     * @param  Builder<Model>  $query
     */
    protected function whereLike(Builder $query, string $column, string $pattern, string $boolean = 'and'): void
    {
        $grammar = $query->getQuery()->getGrammar();
        $wrapped = $grammar->wrap($column);
        $operator = $grammar instanceof PostgresGrammar ? 'ilike' : 'like';

        $query->whereRaw("{$wrapped} {$operator} ? escape '\\'", [$pattern], $boolean);
    }
}
