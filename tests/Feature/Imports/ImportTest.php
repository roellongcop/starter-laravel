<?php

use App\Enums\UserImportStatus;
use App\Jobs\ProcessImportJob;
use App\Models\User;
use App\Models\UserImport;
use App\Notifications\ImportCompleteNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('uploads a file and creates a pending import then previews it', function (): void {
    Storage::fake('imports');
    actingAsRole('developer');

    $csv = "name,email\nAda Lovelace,ada@example.com\n";
    $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

    $response = $this->post(route('imports.store'), ['file' => $file, 'resource' => 'users']);

    $import = UserImport::first();
    expect($import->status)->toBe(UserImportStatus::Pending);
    $response->assertRedirect(route('imports.preview', $import))
        ->assertSessionHas('success');

    $this->get(route('imports.preview', $import))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Imports/Preview')
            ->where('rowCount', 1)
            ->where('headings', ['name', 'email']));
});

it('processes an import, creating users and counting failures', function (): void {
    Storage::fake('imports');
    $owner = actingAsRole('developer');

    $csv = "name,email\nAda Lovelace,ada@example.com\nNoEmailRow,\n";
    Storage::disk('imports')->put('users.csv', $csv);

    $import = UserImport::create([
        'user_id' => $owner->id,
        'token' => 'tok-'.uniqid(),
        'resource' => 'users',
        'filename' => 'users.csv',
        'status' => UserImportStatus::Pending,
    ]);

    (new ProcessImportJob($import))->handle();

    $import->refresh();
    expect($import->status)->toBe(UserImportStatus::Done)
        ->and($import->total)->toBe(2)
        ->and($import->success)->toBe(1)
        ->and($import->failed)->toBe(1)
        ->and($import->error_report_path)->not->toBeNull()
        ->and(User::where('email', 'ada@example.com')->exists())->toBeTrue();
});

it('lets the owner download the original file but forbids others / 404s when missing', function (): void {
    Storage::fake('imports');
    $owner = actingAsRole('developer');

    Storage::disk('imports')->put('2026/06/users.csv', "name,email\nAda,ada@example.com\n");
    $import = UserImport::create([
        'user_id' => $owner->id,
        'token' => 'tok-'.uniqid(),
        'resource' => 'users',
        'filename' => '2026/06/users.csv',
        'status' => UserImportStatus::Pending,
    ]);

    $this->get(route('imports.download', $import))->assertOk();

    // A different user cannot download someone else's import.
    $this->actingAs(User::factory()->create());
    $this->get(route('imports.download', $import))->assertForbidden();

    // Owner again, but the stored file is gone → 404.
    $this->actingAs($owner);
    Storage::disk('imports')->delete('2026/06/users.csv');
    $this->get(route('imports.download', $import))->assertNotFound();
});

it('queues a large import via the process route and notifies', function (): void {
    config(['keen.import_sync_threshold' => 0]); // force the queued path
    Notification::fake();
    Storage::fake('imports');
    $owner = actingAsRole('developer');

    Storage::disk('imports')->put('users.csv', "name,email\nGrace Hopper,grace@example.com\n");
    $import = UserImport::create([
        'user_id' => $owner->id,
        'token' => 'tok-'.uniqid(),
        'resource' => 'users',
        'filename' => 'users.csv',
        'status' => UserImportStatus::Pending,
    ]);

    $this->post(route('imports.process', $import))->assertRedirect(route('imports.index'));

    expect($import->fresh()->status)->toBe(UserImportStatus::Done);
    Notification::assertSentTo($owner, ImportCompleteNotification::class);
});
