<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Bulk-loads the `users` table with fake accounts to exercise keyset
 * pagination, search, and bulk actions. Deliberately NOT wired into
 * DatabaseSeeder. Run on demand:
 *
 *     docker compose exec -T app php artisan db:seed --class=BulkUserSeeder
 *
 * Set the row count by editing DEFAULT_COUNT, or programmatically:
 *
 *     (new \Database\Seeders\BulkUserSeeder)->setCount(5_000)->run();
 *
 * Uses the UserFactory rather than raw inserts: faker generates the unique
 * emails (so re-runs never collide), the HasToken `creating` hook fills the
 * UUID token, and the password is hashed once and cached on the factory. Every
 * seeded login is "password". A Sequence spreads rows across the three
 * UserStatus values so status filters have something to show, and each user is
 * given one random fixed role (developer/superadmin/admin/user) — bulk-inserted
 * into the pivot — so role filters and access checks have data too.
 */
class BulkUserSeeder extends Seeder
{
    /** Set by a caller to override the default count; <= 0 means "use default". */
    public int $count = 0;

    /** Rows per factory batch — keeps memory flat for larger counts. */
    private const CHUNK = 500;

    private const DEFAULT_COUNT = 10_000;

    public function setCount(int $count): static
    {
        $this->count = $count;

        return $this;
    }

    public function run(): void
    {
        $count = $this->count > 0 ? $this->count : self::DEFAULT_COUNT;

        $statuses = new Sequence(
            ['user_status' => UserStatus::Active],
            ['user_status' => UserStatus::Blocked],
            ['user_status' => UserStatus::Inactive],
        );

        // Fixed roles to draw from (must be seeded first via RoleSeeder).
        $fixed = array_map(fn (SystemRole $r) => $r->value, SystemRole::cases());
        $roleIds = Role::query()->whereIn('name', $fixed)->pluck('id')->all();
        // spatie ships these column-name keys as null (meaning "use the default"),
        // so fall back with ?: rather than config()'s missing-key default.
        $pivotTable = (string) (config('permission.table_names.model_has_roles') ?: 'model_has_roles');
        $roleKey = (string) (config('permission.column_names.role_pivot_key') ?: 'role_id');
        $modelKey = (string) (config('permission.column_names.model_morph_key') ?: 'model_id');
        $morphType = (new User)->getMorphClass();

        // $this->command is null when invoked programmatically (e.g. from a test).
        /** @var Command|null $command */
        $command = $this->command;
        $output = $command?->getOutput();
        $output?->progressStart($count);

        $remaining = $count;
        while ($remaining > 0) {
            $batch = min(self::CHUNK, $remaining);

            $users = User::factory()
                ->count($batch)
                ->state($statuses)
                // Random local part guarantees uniqueness even on re-runs against
                // a non-empty table — faker's unique() only dedupes within a run.
                ->state(fn () => ['email' => Str::lower(Str::random(16)).'@example.test'])
                ->create();

            // Assign one random fixed role per user via a single pivot insert
            // (assignRole() per user would flush the permission cache thousands
            // of times). Skipped when roles aren't seeded.
            if ($roleIds !== []) {
                DB::table($pivotTable)->insert(
                    $users->map(fn (User $u) => [
                        $roleKey => $roleIds[array_rand($roleIds)],
                        'model_type' => $morphType,
                        $modelKey => $u->getKey(),
                    ])->all(),
                );
            }

            $remaining -= $batch;
            $output?->progressAdvance($batch);
        }

        // Pivot rows were inserted directly; clear spatie's cached map.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $output?->progressFinish();

        $roleNote = $roleIds !== [] ? '' : ' (no roles assigned — run RoleSeeder first)';
        $output?->writeln("<info>Seeded {$count} user rows (login password: \"password\").{$roleNote}</info>");
    }
}
