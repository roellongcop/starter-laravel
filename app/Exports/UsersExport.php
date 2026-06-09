<?php

namespace App\Exports;

use App\Filters\UserFilters;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
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
     * @param  User|null  $owner  export owner; gates the permission-bound filters (e.g. inactive)
     */
    public function __construct(
        public array $filters = [],
        public ?array $window = null,
        public ?User $owner = null,
    ) {}

    /**
     * @return Builder<User>
     */
    public function query(): Builder
    {
        // Reuse the index's UserFilters so the export honors the same filters
        $request = Request::create('/', 'GET', $this->filters);
        $request->setUserResolver(fn () => $this->owner);

        $filters = new UserFilters($request, app(Pipeline::class));

        return $filters->apply(User::query())
            ->with('roles:id,name')
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
        return ['id', 'name', 'email', 'username', 'user_status', 'roles', 'password', 'password_hint', 'created_at', 'updated_at'];
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
            $user->roles->pluck('name')->implode(', '),
            $user->password,        // bcrypt hash; the import preserves it (no re-hash)
            $user->password_hint,
            $user->created_at?->toDateTimeString(),
            $user->updated_at?->toDateTimeString(),
        ];
    }
}
