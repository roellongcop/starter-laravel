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
| Real end-to-end backup → restore against MariaDB (group "mariadb", run via
| `make test-mariadb`). This is the MariaDB counterpart of the Postgres
| sequence-collision investigation: it sets the same trap (rows created AFTER the
| backup, then a restore) and checks whether a subsequent insert/delete hits a
| duplicate-key error. mysqldump defaults to --add-drop-table and bakes
| AUTO_INCREMENT into the CREATE TABLE, so the restore is faithful and the counter
| comes back correct — i.e. this should pass with NO extra fix, unlike Postgres.
|
| mysqldump/mysql are external processes on their own connection, so we CANNOT use a
| transactional RefreshDatabase (its rows would be invisible to the dump). We run a
| committed migrate:fresh against a dedicated keen_admin_test database per test.
*/
beforeEach(function (): void {
    config()->set('database.default', 'mariadb');
    config()->set('database.connections.mariadb.database', 'keen_admin_test');
    // The test env's DB_CONNECTION is sqlite; force spatie to dump mariadb.
    config()->set('backup.backup.source.databases', ['mariadb']);
    DB::purge('mariadb');

    try {
        DB::connection('mariadb')->getPdo();
    } catch (Throwable $e) {
        test()->markTestSkipped('MariaDB (keen_admin_test) not available: '.$e->getMessage());
    }

    // Committed schema (no surrounding transaction) so the external mysqldump sees it.
    Artisan::call('migrate:fresh', ['--force' => true]);
});

it('keeps every resource mutable after a restore (no auto-increment collisions)', function (): void {
    Storage::fake('backups');

    // Baseline rows the backup will capture.
    $ip = Ip::factory()->create();
    $owner = User::factory()->create();

    $backup = Backup::factory()->create(['status' => BackupStatus::Pending]);
    (new CreateBackupJob($backup))->handle();
    expect($backup->fresh()->status)->toBe(BackupStatus::Generated);

    // Post-backup growth — the trap that broke Postgres (stale rows + an advanced counter).
    Ip::factory()->count(2)->create();
    expect(Ip::count())->toBe(3);

    (new RestoreBackupJob($backup))->handle();
    expect($backup->fresh()->status)->toBe(BackupStatus::Restored);

    // mysqldump --add-drop-table makes the restore faithful: the 2 post-backup IPs are gone.
    expect(Ip::count())->toBe(1);

    // The mutations that crashed on Postgres — must NOT throw a duplicate-key error here.
    Ip::factory()->create();                 // create IP after restore
    UserImport::create([                     // create import after restore
        'user_id' => $owner->id,
        'token' => (string) Str::uuid(),
        'resource' => 'users',
        'filename' => 'after-restore.csv',
        'status' => UserImportStatus::Pending,
    ]);
    $ip->fresh()->delete();                  // delete IP → writes an audit row

    expect(Ip::count())->toBe(1)
        ->and(UserImport::count())->toBe(1);
});

it('lets you create a fresh row of {dataset} after a restore', function (Closure $makeRow): void {
    Storage::fake('backups');

    $makeRow();                               // baseline row (captured by the backup)

    $backup = Backup::factory()->create(['status' => BackupStatus::Pending]);
    (new CreateBackupJob($backup))->handle();

    $makeRow();                               // post-backup rows = the trap
    $makeRow();

    (new RestoreBackupJob($backup))->handle();

    $makeRow();                               // must NOT throw a duplicate-key error

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
