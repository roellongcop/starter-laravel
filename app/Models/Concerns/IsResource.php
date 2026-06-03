<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Shared resource plumbing for domain models: keyset (cursor) ordering and the
 * routing/url config() descriptor. Lives in a trait so both BaseModel and User
 * (which must extend Authenticatable, not BaseModel) behave identically.
 *
 * Table names follow the Laravel convention (snake_case plural of the class);
 * a model sets $table only to override that (e.g. uncountable nouns).
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
