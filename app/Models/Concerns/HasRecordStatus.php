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
     * Apply a bulk process to the given ids. Returns the number of affected rows.
     *
     * @param  array<int, int|string>  $ids
     */
    public static function bulkAction(string $process, array $ids): int
    {
        $query = static::query()->withInactive();
        $query->whereIn($query->getModel()->getQualifiedKeyName(), $ids);

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
