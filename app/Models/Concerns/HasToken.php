<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Fills an unguessable UUID `token` on `creating` and binds routes by it instead
 * of the enumerable `id`. See docs/decisions/0004-uuid-token-route-binding.md.
 */
trait HasToken
{
    public static function bootHasToken(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->getAttribute('token'))) {
                $model->setAttribute('token', (string) Str::uuid());
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }
}
