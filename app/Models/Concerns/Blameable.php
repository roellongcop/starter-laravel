<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Stamps created_by / updated_by from the authenticated user id. On the console
 * or when unauthenticated the id is null (= system). This is data attribution
 * only — never authorization.
 */
trait Blameable
{
    public static function bootBlameable(): void
    {
        static::creating(function (Model $model): void {
            $id = Auth::id();

            if ($model->getAttribute('created_by') === null) {
                $model->setAttribute('created_by', $id);
            }

            if ($model->getAttribute('updated_by') === null) {
                $model->setAttribute('updated_by', $id);
            }
        });

        static::updating(function (Model $model): void {
            $model->setAttribute('updated_by', Auth::id());
        });
    }
}
