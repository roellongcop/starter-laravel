<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Shared resource plumbing — keyset() ordering and the routing/url config()
 * descriptor — in a trait so BaseModel and User (an Authenticatable, not a
 * BaseModel) behave identically. Keyset pagination:
 * docs/decisions/0002-keyset-cursor-pagination.md; resource shape:
 * docs/conventions/backend.md.
 */
trait IsResource
{
    /**
     * Order newest-first for cursor pagination: created_at DESC, id DESC.
     */
    public function scopeKeyset(Builder $query): Builder
    {
        return $query
            ->orderBy($this->getTable().'.created_at', 'desc')
            ->orderBy($this->getTable().'.'.$this->getKeyName(), 'desc');
    }

    /**
     * Keyset ordering whose tiebreaker is the public `token` instead of `id`.
     * Use this for Inertia::scroll() payloads: the cursor is encoded from the
     * (transformed) row arrays, so the keyset columns (created_at + token) must
     * be present in the payload — and token, unlike id, is safe to expose.
     */
    public function scopeKeysetByToken(Builder $query): Builder
    {
        return $query
            ->orderBy($this->getTable().'.created_at', 'desc')
            ->orderBy($this->getTable().'.'.$this->getRouteKeyName(), 'desc');
    }

    /**
     * Routing/url descriptor used by controllers and url helpers.
     *
     * @return array{controllerId: string, mainAttribute: string, paramName: string, dateAttribute: string}
     */
    public function config(): array
    {
        return [
            'controllerId' => Str::kebab(Str::pluralStudly(class_basename($this))),
            'mainAttribute' => 'name',
            'paramName' => Str::snake(class_basename($this)),
            'dateAttribute' => 'created_at',
        ];
    }
}
