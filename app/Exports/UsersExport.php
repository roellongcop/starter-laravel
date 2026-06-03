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
 * PDF path to build the row collection.
 */
class UsersExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(public array $filters = []) {}

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
                ->orWhere('email', 'like', "%{$search}%")));
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['ID', 'Name', 'Email', 'Username', 'Status', 'Roles', 'Created'];
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
            $user->created_at?->toDateTimeString(),
        ];
    }
}
