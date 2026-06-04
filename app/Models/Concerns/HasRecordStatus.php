<?php

namespace App\Models\Concerns;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Business Active/Inactive lifecycle (NOT deletion — the app avoids SoftDeletes).
 *
 * Queries default to Active rows via a global scope; `withInactive()` lifts it and
 * `onlyInactive()` inverts it. `bulkAction()` is the dispatcher list endpoints call;
 * models may override it to register extra processes (e.g. Ip's `white_list`) while
 * delegating the defaults back here via parent::bulkAction().
 */
trait HasRecordStatus
{
    public static function bootHasRecordStatus(): void
    {
        static::addGlobalScope('active', function (Builder $builder): void {
            $builder->where(
                $builder->getModel()->getTable().'.record_status',
                RecordStatus::Active->value,
            );
        });
    }

    public function scopeWithInactive(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active');
    }

    /**
     * Resolve route-model bindings without the Active scope, so admin CRUD on an
     * inactive (record_status = 0) row works — otherwise show/edit/update/destroy
     * would 404 on a row the list surfaced via onlyInactive(). Access stays gated
     * by the resource policies + the index's view-inactive permission.
     *
     * @param  Builder<covariant static>  $query
     * @param  mixed  $value
     * @param  string|null  $field
     * @return Builder<covariant static>
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)
            ->withoutGlobalScope('active');
    }

    public function scopeOnlyInactive(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active')->where(
            $this->getTable().'.record_status',
            RecordStatus::Inactive->value,
        );
    }

    public function activate(): bool
    {
        return $this->forceFill(['record_status' => RecordStatus::Active])->save();
    }

    public function inactivate(): bool
    {
        return $this->forceFill(['record_status' => RecordStatus::Inactive])->save();
    }

    /**
     * Apply a bulk process to the given route-key tokens. Returns the number of
     * affected rows. Selection is keyed on the public token (getRouteKeyName),
     * never the internal id, so the frontend never handles ids.
     *
     * @param  array<int, int|string>  $tokens
     */
    public static function bulkAction(string $process, array $tokens): int
    {
        $query = static::query()->withInactive();
        $model = $query->getModel();
        $query->whereIn($model->qualifyColumn($model->getRouteKeyName()), $tokens);

        return match ($process) {
            'active' => $query->update([
                'record_status' => RecordStatus::Active->value,
                'updated_by' => Auth::id(),
                'updated_at' => now(),
            ]),
            'in_active' => $query->update([
                'record_status' => RecordStatus::Inactive->value,
                'updated_by' => Auth::id(),
                'updated_at' => now(),
            ]),
            'delete' => $query->delete(),
            default => throw new \InvalidArgumentException("Unknown bulk process: {$process}"),
        };
    }
}
