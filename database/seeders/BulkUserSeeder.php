<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
 * UserStatus values so status filters have something to show.
 */
class BulkUserSeeder extends Seeder
{
    /** Set by a caller to override the default count; <= 0 means "use default". */
    public int $count = 0;

    /** Rows per factory batch — keeps memory flat for larger counts. */
    private const CHUNK = 500;

    private const DEFAULT_COUNT = 50_000;

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

        // $this->command is null when invoked programmatically (e.g. from a test).
        /** @var Command|null $command */
        $command = $this->command;
        $output = $command?->getOutput();
        $output?->progressStart($count);

        $remaining = $count;
        while ($remaining > 0) {
            $batch = min(self::CHUNK, $remaining);

            User::factory()
                ->count($batch)
                ->state($statuses)
                // Random local part guarantees uniqueness even on re-runs against
                // a non-empty table — faker's unique() only dedupes within a run.
                ->state(fn () => ['email' => Str::lower(Str::random(16)).'@example.test'])
                ->create();

            $remaining -= $batch;
            $output?->progressAdvance($batch);
        }

        $output?->progressFinish();
        $output?->writeln("<info>Seeded {$count} user rows (login password: \"password\").</info>");
    }
}
