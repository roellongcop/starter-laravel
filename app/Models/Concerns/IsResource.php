<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Shared resource plumbing for domain models: the `tbl_` table prefix, keyset
 * (cursor) ordering, and the routing/url config() descriptor. Lives in a trait
 * so both BaseModel and User (which must extend Authenticatable, not BaseModel)
 * behave identically.
 */
trait IsResource
{
    /**
     * Prefix the configured domain prefix onto the snake-plural class name
     * unless an explicit $table is set (framework tables like `users` opt out).
     */
    public function getTable(): string
    {
        return $this->table ??= config('keen.table_prefix', 'tbl_')
            .Str::snake(Str::pluralStudly(class_basename($this)));
    }

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
