<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Gives a model a unique, unguessable `token` (UUID v4) that is used as the
 * route-model-binding key in place of the auto-increment `id`. The numeric id
 * stays the internal primary key / foreign-key target, but URLs and Inertia
 * payloads expose the token instead, so resource paths can't be enumerated.
 *
 * Laravel's HasUuids/HasUlids traits replace the primary key, which we don't
 * want — so this fills a separate column on `creating` and overrides the route
 * key name. The `creating` hook composes with Blameable's (order-independent:
 * each only sets attributes that are still empty).
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
