<?php

namespace App\Imports;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Marker import used with Excel::toCollection()/toArray() so rows are keyed by
 * their (slug-formatted) header row. The actual validation/upsert/counting runs
 * in ImportShardJob via importRow() — kept here so the import path has one
 * definition of the accepted columns.
 *
 * Accepted headers are the real users-table column names, matching what
 * UsersExport emits so a spreadsheet export round-trips straight back in.
 */
class UsersImport implements WithHeadingRow
{
    /**
     * Validate one import row and upsert the user (keyed on email), syncing roles
     * when the row carries a `roles` column. Returns the list of validation error
     * messages — empty when the row succeeded.
     *
     * Pass $validRoles (the known role names) to avoid a query per row; it falls
     * back to looking them up when omitted.
     *
     * @param  array<string, mixed>  $row
     * @param  array<int, string>|null  $validRoles
     * @return array<int, string>
     */
    public static function importRow(array $row, ?array $validRoles = null): array
    {
        $validRoles ??= Role::query()->pluck('name')->all();

        // Accept the real column name (user_status) or the legacy 'status' header.
        $status = $row['user_status'] ?? $row['status'] ?? null;

        $data = [
            'name' => $row['name'] ?? null,
            'email' => $row['email'] ?? null,
            'username' => ($row['username'] ?? null) ?: null,
            'password_hint' => ($row['password_hint'] ?? null) ?: null,
            'user_status' => $status === '' ? null : $status,
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'password_hint' => ['nullable', 'string', 'max:255'],
            'user_status' => ['nullable', Rule::enum(UserStatus::class)],
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        // Only touch roles when the file carries the column (so name/email-only
        // imports never strip an existing user's roles). Reject unknown names up
        // front so a typo fails the row instead of silently dropping access.
        $hasRoles = array_key_exists('roles', $row);
        $roleNames = collect(explode(',', (string) ($row['roles'] ?? '')))
            ->map(fn ($r) => trim((string) $r))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($hasRoles && ($unknown = array_diff($roleNames, $validRoles)) !== []) {
            return ['Unknown role(s): '.implode(', ', $unknown)];
        }

        // An already-hashed password round-trips untouched (the 'hashed' cast skips
        // re-hashing); a missing password gets a random one for newly-created users.
        $user = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => $row['password'] ?? Str::random(16),
                'password_hint' => $data['password_hint'],
                'user_status' => $data['user_status'] ?? UserStatus::Active->value,
            ],
        );

        if ($hasRoles) {
            $user->syncRoles($roleNames);
        }

        return [];
    }
}
