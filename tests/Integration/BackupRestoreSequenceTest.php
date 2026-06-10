<?php

use App\Enums\BackupStatus;
use App\Enums\UserImportStatus;
use App\Jobs\CreateBackupJob;
use App\Jobs\RestoreBackupJob;
use App\Models\Backup;
use App\Models\Ip;
use App\Models\User;
use App\Models\UserImport;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
| Real end-to-end backup → restore against PostgreSQL (group "pg", run via
| `make test-pg`). These reproduce the duplicate-key (23505) crash that happened
| after a restore: pg_dump's setval() rewinds sequences, and without a self-cleaning
| dump the post-backup rows survive, so the next insert collides. The fix
| (--clean --if-exists in config/database.php) drops+recreates the dumped tables so
| the restore is faithful and sequences land correctly.
|
| pg_dump/psql are external processes on their own connection, so we CANNOT use a
| transactional RefreshDatabase (its rows would be invisible to the dump). Instead we
| run a committed migrate:fresh against a dedicated keen_admin_test database per test.
*/
beforeEach(function (): void {
    // Point the default connection at the dedicated integration DB so we never
    // touch dev data. The dump/restore both read config('database.connections.pgsql').
    config()->set('database.default', 'pgsql');
    config()->set('database.connections.pgsql.database', 'keen_admin_test');
    // spatie reads the connection to dump from config; the test env's DB_CONNECTION
    // is sqlite, so force it to pgsql here.
    config()->set('backup.backup.source.databases', ['pgsql']);
    DB::purge('pgsql');

    try {
        DB::connection('pgsql')->getPdo();
    } catch (Throwable $e) {
        test()->markTestSkipped('Postgres (keen_admin_test) not available: '.$e->getMessage());
    }

    // Committed schema (no surrounding transaction) so the external pg_dump sees it.
    Artisan::call('migrate:fresh', ['--force' => true]);
});

it('keeps every resource mutable after a restore (no sequence collisions)', function (): void {
    Storage::fake('backups');

    // Baseline rows the backup will capture.
    $ip = Ip::factory()->create();
    $owner = User::factory()->create();

    $backup = Backup::factory()->create(['status' => BackupStatus::Pending]);
    (new CreateBackupJob($backup))->handle();
    expect($backup->fresh()->status)->toBe(BackupStatus::Generated);

    // Post-backup growth: extra rows + an advanced sequence are the desync trap that
    // the old (non-cleaning) restore left behind.
    Ip::factory()->count(2)->create();
    expect(Ip::count())->toBe(3);

    (new RestoreBackupJob($backup))->handle();
    expect($backup->fresh()->status)->toBe(BackupStatus::Restored);

    // Faithful restore: the 2 post-backup IPs are gone — only the backed-up row remains.
    expect(Ip::count())->toBe(1);

    // The three real-world failure modes — each previously threw 23505. Reaching the
    // final assertion without an exception is the test.
    Ip::factory()->create();                 // create IP after restore (ips_id_seq)
    UserImport::create([                     // create import after restore (user_imports_id_seq)
        'user_id' => $owner->id,
        'token' => (string) Str::uuid(),
        'resource' => 'users',
        'filename' => 'after-restore.csv',
        'status' => UserImportStatus::Pending,
    ]);
    $ip->fresh()->delete();                  // delete IP → writes an audit row (audits_id_seq)

    expect(Ip::count())->toBe(1)             // the create + delete net out
        ->and(UserImport::count())->toBe(1);
});

// Future-proofing: each resource gets its own backup→grow→restore→mutate cycle, so
// adding a new resource that hit this bug is a one-line dataset addition.
it('lets you create a fresh row of {dataset} after a restore', function (Closure $makeRow): void {
    Storage::fake('backups');

    $makeRow();                               // baseline row (captured by the backup)

    $backup = Backup::factory()->create(['status' => BackupStatus::Pending]);
    (new CreateBackupJob($backup))->handle();

    $makeRow();                               // post-backup rows = the desync trap
    $makeRow();

    (new RestoreBackupJob($backup))->handle();

    $makeRow();                               // must NOT throw UniqueConstraintViolationException

    expect($backup->fresh()->status)->toBe(BackupStatus::Restored);
})->with([
    'ip' => [fn () => Ip::factory()->create()],
    'user' => [fn () => User::factory()->create()],
    'user_import' => [fn () => UserImport::create([
        'user_id' => User::factory()->create()->id,
        'token' => (string) Str::uuid(),
        'resource' => 'users',
        'filename' => 'x.csv',
        'status' => UserImportStatus::Pending,
    ])],
]);
