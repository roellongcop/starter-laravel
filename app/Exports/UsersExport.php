<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Exports users (csv/xls/xlsx) applying the stored filters. Also reused by the
 * PDF path to build the row collection. Sharded exports pass an inclusive id
 * window ([low, high]) so each shard job emits just its slice of the result set.
 */
class UsersExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{0: int, 1: int}|null  $window  inclusive [low id, high id] bound for one shard
     */
    public function __construct(public array $filters = [], public ?array $window = null) {}

    /**
     * @return Builder<User>
     */
    public function query(): Builder
    {
        $search = $this->filters['search'] ?? null;

        return User::query()
            ->with('roles:id,name')
            ->when($search, fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($this->window, fn (Builder $q) => $q->whereBetween('id', $this->window))
            ->orderBy('id');
    }

    /**
     * Real users-table column names so a spreadsheet export round-trips straight
     * back through the import (which keys rows by these snake_case headers).
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['id', 'name', 'email', 'username', 'user_status', 'password', 'password_hint', 'created_at', 'updated_at'];
    }

    /**
     * @param  User  $user
     * @return array<int, mixed>
     */
    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->username,
            $user->user_status->value,
            $user->password,        // bcrypt hash; the import preserves it (no re-hash)
            $user->password_hint,
            $user->created_at?->toDateTimeString(),
            $user->updated_at?->toDateTimeString(),
        ];
    }
}
