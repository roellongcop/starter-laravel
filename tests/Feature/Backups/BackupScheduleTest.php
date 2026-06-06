<?php

use App\Enums\BackupStatus;
use App\Jobs\CreateBackupJob;
use App\Models\Backup;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

it('backups:run queues a pending backup', function (): void {
    Bus::fake();

    $this->artisan('backups:run')->assertSuccessful();

    expect(Backup::where('status', BackupStatus::Pending)->count())->toBe(1);
    Bus::assertDispatched(CreateBackupJob::class);
});

it('backups:prune deletes old backups and their archives but keeps recent ones', function (): void {
    Storage::fake('backups');
    Storage::disk('backups')->put('2026/01/old.zip', 'OLD');
    Storage::disk('backups')->put('2026/06/new.zip', 'NEW');

    $oldGenerated = Backup::factory()->create([
        'status' => BackupStatus::Generated,
        'filename' => '2026/01/old.zip',
        'created_at' => now()->subDays(60),
    ]);
    $oldFailed = Backup::factory()->create([
        'status' => BackupStatus::Failed,
        'filename' => null,
        'created_at' => now()->subDays(60),
    ]);
    $recent = Backup::factory()->create([
        'status' => BackupStatus::Generated,
        'filename' => '2026/06/new.zip',
        'created_at' => now()->subDay(),
    ]);

    $this->artisan('backups:prune', ['--days' => 30])->assertSuccessful();

    expect(Backup::find($oldGenerated->id))->toBeNull();
    expect(Storage::disk('backups')->exists('2026/01/old.zip'))->toBeFalse();
    expect(Backup::find($oldFailed->id))->toBeNull();

    expect(Backup::find($recent->id))->not->toBeNull();
    expect(Storage::disk('backups')->exists('2026/06/new.zip'))->toBeTrue();
});

it('backups:prune always keeps the most recent generated backup even when all are old', function (): void {
    Storage::fake('backups');

    $older = Backup::factory()->create([
        'status' => BackupStatus::Generated,
        'created_at' => now()->subDays(90),
    ]);
    $newest = Backup::factory()->create([
        'status' => BackupStatus::Generated,
        'created_at' => now()->subDays(40),
    ]);

    $this->artisan('backups:prune', ['--days' => 30])->assertSuccessful();

    expect(Backup::find($older->id))->toBeNull();
    expect(Backup::find($newest->id))->not->toBeNull();
});
