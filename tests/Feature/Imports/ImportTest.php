<?php

use App\Enums\SystemRole;
use App\Enums\UserImportStatus;
use App\Jobs\DispatchImportJob;
use App\Models\User;
use App\Models\UserImport;
use App\Notifications\ImportCompleteNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('uploads a file and creates a pending import then previews it', function (): void {
    Storage::fake('imports');
    actingAsRole(SystemRole::Developer);

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
            ->where('headings', ['name', 'email'])
            ->where('rows', fn ($rows) => count($rows) === 1));
});

it('processes an import, creating users and counting failures', function (): void {
    Storage::fake('imports');
    $owner = actingAsRole(SystemRole::Developer);

    $csv = "name,email\nAda Lovelace,ada@example.com\nNoEmailRow,\n";
    Storage::disk('imports')->put('users.csv', $csv);

    $import = UserImport::create([
        'user_id' => $owner->id,
        'token' => 'tok-'.uniqid(),
        'resource' => 'users',
        'filename' => 'users.csv',
        'status' => UserImportStatus::Pending,
    ]);

    (new DispatchImportJob($import, notify: false))->handle();

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
    $owner = actingAsRole(SystemRole::Developer);

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

it('discards a pending import, removing the row and its file', function (): void {
    Storage::fake('imports');
    $owner = actingAsRole(SystemRole::Developer);

    Storage::disk('imports')->put('2026/06/users.csv', "name,email\nAda,ada@example.com\n");
    $import = UserImport::create([
        'user_id' => $owner->id,
        'token' => 'tok-'.uniqid(),
        'resource' => 'users',
        'filename' => '2026/06/users.csv',
        'status' => UserImportStatus::Pending,
    ]);

    $this->delete(route('imports.destroy', $import))
        ->assertRedirect(route('imports.index'))
        ->assertSessionHas('success');

    expect(UserImport::count())->toBe(0)
        ->and(Storage::disk('imports')->exists('2026/06/users.csv'))->toBeFalse();
});

it('owner-gates import deletion', function (): void {
    Storage::fake('imports');
    $owner = actingAsRole(SystemRole::Developer);

    $import = UserImport::create([
        'user_id' => $owner->id,
        'token' => 'tok-'.uniqid(),
        'resource' => 'users',
        'filename' => 'users.csv',
        'status' => UserImportStatus::Pending,
    ]);

    $this->actingAs(User::factory()->create());
    $this->delete(route('imports.destroy', $import))->assertForbidden();
    expect(UserImport::whereKey($import->id)->exists())->toBeTrue();
});

it('refuses to delete a running import', function (): void {
    Storage::fake('imports');
    $owner = actingAsRole(SystemRole::Developer);

    $import = UserImport::create([
        'user_id' => $owner->id,
        'token' => 'tok-'.uniqid(),
        'resource' => 'users',
        'filename' => 'users.csv',
        'status' => UserImportStatus::Running,
    ]);

    $this->delete(route('imports.destroy', $import))->assertStatus(409);
    expect(UserImport::whereKey($import->id)->exists())->toBeTrue();
});

it('routes a large import through the sharded dispatcher', function (): void {
    Bus::fake();
    Storage::fake('imports');
    $owner = actingAsRole(SystemRole::Developer);

    Storage::disk('imports')->put('users.csv', "name,email\nGrace Hopper,grace@example.com\n");
    $import = UserImport::create([
        'user_id' => $owner->id,
        'token' => 'tok-'.uniqid(),
        'resource' => 'users',
        'filename' => 'users.csv',
        'status' => UserImportStatus::Pending,
    ]);

    $this->post(route('imports.process', $import))->assertRedirect(route('imports.index'));

    Bus::assertDispatched(DispatchImportJob::class);
});

it('queues a large import via the process route and notifies', function (): void {
    Notification::fake();
    Storage::fake('imports');
    $owner = actingAsRole(SystemRole::Developer);

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

it('shards a large import, tallies counts, and merges one error report', function (): void {
    config(['keen.import_shard_size' => 2]);
    Notification::fake();
    Storage::fake('imports');
    $owner = actingAsRole(SystemRole::Developer);

    // 4 data rows across 2 shards; one row is invalid (missing email).
    $csv = "name,email\nA,a@example.com\nB,b@example.com\nBad,\nC,c@example.com\n";
    Storage::disk('imports')->put('users.csv', $csv);
    $import = UserImport::create([
        'user_id' => $owner->id,
        'token' => 'tok-'.uniqid(),
        'resource' => 'users',
        'filename' => 'users.csv',
        'status' => UserImportStatus::Pending,
    ]);

    (new DispatchImportJob($import))->handle();

    $import->refresh();
    expect($import->status)->toBe(UserImportStatus::Done)
        ->and($import->total)->toBe(4)
        ->and($import->success)->toBe(3)
        ->and($import->failed)->toBe(1)
        ->and($import->error_report_path)->not->toBeNull()
        ->and(User::where('email', 'c@example.com')->exists())->toBeTrue();

    // One merged report: single header + the single failing row (file line 4).
    $report = (string) Storage::disk('imports')->get($import->error_report_path);
    expect($report)->toContain('row,email,errors')
        ->and(substr_count($report, "\n"))->toBe(2); // header + 1 row
    Notification::assertSentTo($owner, ImportCompleteNotification::class);
});
