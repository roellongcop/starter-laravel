<?php

use App\Enums\SystemRole;
use App\Enums\UserExportStatus;
use App\Enums\UserImportStatus;
use App\Enums\UserStatus;
use App\Jobs\DispatchExportJob;
use App\Jobs\DispatchImportJob;
use App\Jobs\GenerateExportJob;
use App\Models\User;
use App\Models\UserExport;
use App\Models\UserImport;
use App\Notifications\ExportReadyNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('runs a small export synchronously', function (): void {
    Bus::fake();
    actingAsRole(SystemRole::Developer); // only a handful of users → under threshold

    $this->post(route('exports.store'), [
        'format' => 'csv',
        'resource' => 'users',
        'filters' => ['search' => ''],
    ])->assertRedirect(route('exports.index'));

    Bus::assertDispatchedSync(GenerateExportJob::class);
});

it('routes a large export through the sharded dispatcher', function (): void {
    config(['keen.export_sync_threshold' => 0]); // force the queued path
    Bus::fake();
    actingAsRole(SystemRole::Developer);

    $this->post(route('exports.store'), [
        'format' => 'csv',
        'resource' => 'users',
        'filters' => ['search' => ''],
    ])->assertRedirect(route('exports.index'));

    Bus::assertDispatched(DispatchExportJob::class);
});

it('queues a large export and notifies on completion', function (): void {
    config(['keen.export_sync_threshold' => 0]); // force the queued path
    Notification::fake();
    Storage::fake('exports');
    $owner = actingAsRole(SystemRole::Developer);

    // sync queue driver runs the dispatched job (+ its batch) immediately.
    $this->post(route('exports.store'), [
        'format' => 'csv',
        'resource' => 'users',
        'filters' => ['search' => ''],
    ])->assertRedirect(route('exports.index'));

    expect(UserExport::first()->status)->toBe(UserExportStatus::Done);
    Notification::assertSentTo($owner, ExportReadyNotification::class);
});

it('shards a large xls export into a single zip and marks it done', function (): void {
    // Small shard size so a handful of users spans multiple shards; xls proves the
    // 65,536-row format cap can never be reached (each shard holds ≤ shard_size).
    config(['keen.export_sync_threshold' => 0, 'keen.export_shard_size' => 2]);
    Storage::fake('exports');
    actingAsRole(SystemRole::Developer);
    User::factory()->count(5)->create();

    $this->post(route('exports.store'), [
        'format' => 'xls',
        'resource' => 'users',
        'filters' => ['search' => ''],
    ])->assertRedirect(route('exports.index'));

    $export = UserExport::first();
    expect($export->status)->toBe(UserExportStatus::Done)
        ->and($export->filename)->toEndWith('.zip')
        ->and($export->total_rows)->toBeGreaterThanOrEqual(6)
        ->and($export->row_count)->toBe($export->total_rows)
        ->and(Storage::disk('exports')->exists($export->filename))->toBeTrue();

    // The zip holds one part file per shard.
    $tmp = (string) tempnam(sys_get_temp_dir(), 'exp');
    file_put_contents($tmp, (string) Storage::disk('exports')->get($export->filename));
    $zip = new ZipArchive;
    $zip->open($tmp);
    expect($zip->numFiles)->toBeGreaterThan(1);
    $zip->close();
    unlink($tmp);
});

it('shards a large pdf export into a single zip and marks it done', function (): void {
    // PDF renders a whole shard in memory, so it uses its own smaller shard size; a tiny
    // size here spans a handful of users across multiple shards. Guards the regression where
    // an oversized PDF shard ran long enough for the queue to re-attempt and fail it.
    config(['keen.export_sync_threshold' => 0, 'keen.export_pdf_shard_size' => 2]);
    Storage::fake('exports');
    actingAsRole(SystemRole::Developer);
    User::factory()->count(5)->create();

    $this->post(route('exports.store'), [
        'format' => 'pdf',
        'resource' => 'users',
        'filters' => ['search' => ''],
    ])->assertRedirect(route('exports.index'));

    $export = UserExport::first();
    expect($export->status)->toBe(UserExportStatus::Done)
        ->and($export->filename)->toEndWith('.zip')
        ->and(Storage::disk('exports')->exists($export->filename))->toBeTrue();

    // The zip holds one PDF part file per shard.
    $tmp = (string) tempnam(sys_get_temp_dir(), 'exp');
    file_put_contents($tmp, (string) Storage::disk('exports')->get($export->filename));
    $zip = new ZipArchive;
    $zip->open($tmp);
    expect($zip->numFiles)->toBeGreaterThan(1);
    $zip->close();
    unlink($tmp);
});

it('generates a csv file and marks the export done', function (): void {
    Storage::fake('exports');
    $owner = actingAsRole(SystemRole::Developer);
    User::factory()->count(3)->create();

    $export = UserExport::create([
        'user_id' => $owner->id,
        'token' => 'tok-'.uniqid(),
        'format' => 'csv',
        'resource' => 'users',
        'filters' => [],
        'status' => UserExportStatus::Pending,
    ]);

    (new GenerateExportJob($export))->handle();

    $export->refresh();
    expect($export->status)->toBe(UserExportStatus::Done)
        ->and($export->row_count)->toBeGreaterThanOrEqual(4)
        ->and(Storage::disk('exports')->exists($export->filename))->toBeTrue();
});

it('exports spreadsheets with real column-name headers', function (): void {
    Storage::fake('exports');
    $owner = actingAsRole(SystemRole::Developer);
    User::factory()->count(2)->create();

    $export = UserExport::create([
        'user_id' => $owner->id,
        'token' => 'tok-'.uniqid(),
        'format' => 'csv',
        'resource' => 'users',
        'filters' => [],
        'status' => UserExportStatus::Pending,
    ]);

    (new GenerateExportJob($export, notify: false))->handle();

    $csv = (string) Storage::disk('exports')->get($export->fresh()->filename);
    // Real DB column names (not display labels) so the file re-imports as-is.
    expect($csv)->toContain('"id","name","email","username","user_status","roles","password","password_hint","created_at","updated_at"');
});

it('honors the inactive filter in the export', function (): void {
    Storage::fake('exports');
    $owner = actingAsRole(SystemRole::Developer); // has view-inactive

    $active = User::factory()->create(['name' => 'Active Annie']);
    $inactive = User::factory()->create(['name' => 'Inactive Ivan']);
    $inactive->inactivate();

    $export = UserExport::create([
        'user_id' => $owner->id,
        'token' => 'tok-'.uniqid(),
        'format' => 'csv',
        'resource' => 'users',
        'filters' => ['inactive' => true],
        'status' => UserExportStatus::Pending,
    ]);

    (new GenerateExportJob($export, notify: false))->handle();

    $csv = (string) Storage::disk('exports')->get($export->fresh()->filename);
    expect($csv)->toContain('Inactive Ivan')
        ->and($csv)->not->toContain('Active Annie');
});

it('drops the inactive filter for an owner without view-inactive', function (): void {
    Storage::fake('exports');
    $owner = actingAsRole(SystemRole::Admin); // lacks view-inactive

    User::factory()->create(['name' => 'Active Annie']);
    User::factory()->create(['name' => 'Inactive Ivan'])->inactivate();

    $export = UserExport::create([
        'user_id' => $owner->id,
        'token' => 'tok-'.uniqid(),
        'format' => 'csv',
        'resource' => 'users',
        'filters' => ['inactive' => true],
        'status' => UserExportStatus::Pending,
    ]);

    (new GenerateExportJob($export, notify: false))->handle();

    // Gate denies inactive rows; the export falls back to the active scope.
    $csv = (string) Storage::disk('exports')->get($export->fresh()->filename);
    expect($csv)->toContain('Active Annie')
        ->and($csv)->not->toContain('Inactive Ivan');
});

it('round-trips a csv export back through the import', function (): void {
    Storage::fake('exports');
    Storage::fake('imports');
    $owner = actingAsRole(SystemRole::Developer);

    $user = User::factory()->create([
        'name' => 'Round Trip',
        'email' => 'round-trip@example.com',
        'username' => 'roundtrip',
        'user_status' => UserStatus::Blocked->value,
        'password_hint' => 'the usual',
    ]);
    $user->assignRole(SystemRole::Admin->value);
    $hash = $user->password;

    // Export everyone to CSV via the sync path.
    $export = UserExport::create([
        'user_id' => $owner->id,
        'token' => 'tok-'.uniqid(),
        'format' => 'csv',
        'resource' => 'users',
        'filters' => [],
        'status' => UserExportStatus::Pending,
    ]);
    (new GenerateExportJob($export, notify: false))->handle();
    $csv = (string) Storage::disk('exports')->get($export->fresh()->filename);

    // Drop the user, then feed the very file we exported straight back in.
    $user->delete();
    Storage::disk('imports')->put('round-trip.csv', $csv);
    $import = UserImport::create([
        'user_id' => $owner->id,
        'token' => 'tok-'.uniqid(),
        'resource' => 'users',
        'filename' => 'round-trip.csv',
        'status' => UserImportStatus::Pending,
    ]);
    (new DispatchImportJob($import, notify: false))->handle();

    $restored = User::where('email', 'round-trip@example.com')->first();
    expect($restored)->not->toBeNull()
        ->and($restored->name)->toBe('Round Trip')
        ->and($restored->username)->toBe('roundtrip')
        ->and($restored->user_status)->toBe(UserStatus::Blocked)
        ->and($restored->password_hint)->toBe('the usual')
        ->and($restored->hasRole(SystemRole::Admin->value))->toBeTrue() // roles round-trip
        ->and($restored->password)->toBe($hash) // preserved — the hashed cast skips re-hashing
        ->and($import->fresh()->failed)->toBe(0);
});

it('owner-gates the token download', function (): void {
    Storage::fake('exports');
    Storage::disk('exports')->put('out.csv', 'id,name');
    $owner = actingAsRole(SystemRole::Developer);

    $export = UserExport::factory()->create([
        'user_id' => $owner->id,
        'status' => UserExportStatus::Done,
        'filename' => 'out.csv',
    ]);

    $this->get(route('exports.download', $export->token))->assertOk();

    // a different user cannot use the token.
    $this->actingAs(User::factory()->create());
    $this->get(route('exports.download', $export->token))->assertForbidden();
});
