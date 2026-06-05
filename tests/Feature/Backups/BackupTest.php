<?php

use App\Enums\BackupStatus;
use App\Enums\SystemRole;
use App\Jobs\CreateBackupJob;
use App\Models\Backup;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('queues a backup and records a pending row', function (): void {
    Bus::fake();
    actingAsRole(SystemRole::Developer);

    $this->post(route('backups.store'))->assertRedirect();

    $backup = Backup::first();
    expect($backup->status)->toBe(BackupStatus::Pending);
    Bus::assertDispatched(CreateBackupJob::class);
});

it('gates backup download and streams a generated archive', function (): void {
    Storage::fake('backups');
    Storage::disk('backups')->put('test.zip', 'ZIP');
    actingAsRole(SystemRole::Developer);

    $backup = Backup::factory()->create([
        'filename' => 'test.zip',
        'disk' => 'backups',
        'status' => BackupStatus::Generated,
    ]);

    $this->get(route('backups.download', $backup))->assertOk();
});

it('forbids backups without permission', function (): void {
    $this->get(route('backups.index'))->assertRedirect(route('login'));
});
