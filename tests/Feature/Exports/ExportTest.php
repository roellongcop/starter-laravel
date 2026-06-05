<?php

use App\Enums\SystemRole;
use App\Enums\UserExportStatus;
use App\Jobs\GenerateExportJob;
use App\Models\User;
use App\Models\UserExport;
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

it('queues a large export and notifies on completion', function (): void {
    config(['keen.export_sync_threshold' => 0]); // force the queued path
    Notification::fake();
    Storage::fake('exports');
    $owner = actingAsRole(SystemRole::Developer);

    // sync queue driver runs the dispatched job immediately.
    $this->post(route('exports.store'), [
        'format' => 'csv',
        'resource' => 'users',
        'filters' => ['search' => ''],
    ])->assertRedirect(route('exports.index'));

    expect(UserExport::first()->status)->toBe(UserExportStatus::Done);
    Notification::assertSentTo($owner, ExportReadyNotification::class);
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
