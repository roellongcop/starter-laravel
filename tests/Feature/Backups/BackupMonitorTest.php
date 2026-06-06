<?php

use App\Enums\BackupStatus;
use App\Enums\SystemRole;
use App\Models\Backup;
use App\Models\User;
use App\Notifications\AdminNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\seed;

beforeEach(function (): void {
    seed([PermissionSeeder::class, RoleSeeder::class]);
});

it('does not alert when a recent successful backup exists', function (): void {
    Notification::fake();
    Backup::factory()->create(['status' => BackupStatus::Generated, 'created_at' => now()->subHours(2)]);

    $this->artisan('backups:monitor', ['--hours' => 24])->assertSuccessful();

    Notification::assertNothingSent();
});

it('alerts developers when the last successful backup is stale', function (): void {
    Notification::fake();
    $developer = User::factory()->create();
    $developer->assignRole(SystemRole::Developer->value);
    Backup::factory()->create(['status' => BackupStatus::Generated, 'created_at' => now()->subHours(48)]);

    $this->artisan('backups:monitor', ['--hours' => 24])->assertSuccessful();

    Notification::assertSentTo($developer, AdminNotification::class);
});

it('alerts when no successful backup exists (a failed one does not count)', function (): void {
    Notification::fake();
    $developer = User::factory()->create();
    $developer->assignRole(SystemRole::Developer->value);
    Backup::factory()->create(['status' => BackupStatus::Failed, 'created_at' => now()]);

    $this->artisan('backups:monitor')->assertSuccessful();

    Notification::assertSentTo($developer, AdminNotification::class);
});
