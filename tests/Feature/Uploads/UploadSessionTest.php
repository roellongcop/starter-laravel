<?php

use App\Enums\SystemRole;
use App\Enums\UploadStatus;
use App\Models\File;
use App\Models\UploadSession;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    Storage::fake('uploads');
    // Storage::fake swaps the disk instance but not its configured driver; pin it
    // to local so sessions record driver=local and exercise LocalChunkStorage.
    config()->set('filesystems.disks.uploads.driver', 'local');
    // Tiny chunks so a fixture file spans several parts without being huge.
    config()->set('keen.upload_chunk_size', 1024);
});

function resumableInit(string $name, int $size): TestResponse
{
    return test()->postJson(route('uploads.store'), [
        'original_name' => $name,
        'size' => $size,
        'mime' => 'application/pdf',
    ]);
}

function resumablePut(string $token, int $part, string $chunk): TestResponse
{
    return test()->call(
        'PUT',
        route('uploads.part', ['uploadSession' => $token, 'part' => $part]),
        [], [], [],
        ['CONTENT_TYPE' => 'application/octet-stream'],
        $chunk,
    );
}

it('requires the files.create permission to start an upload', function (): void {
    $this->actingAs(User::factory()->create());

    resumableInit('x.pdf', 2500)->assertForbidden();
});

it('uploads a file in chunks and finalizes it into a downloadable File', function (): void {
    actingAsRole(SystemRole::Developer);

    $content = random_bytes(2500);
    $chunks = str_split($content, 1024);

    $init = resumableInit('report.pdf', strlen($content))->assertCreated();
    $token = $init->json('token');

    expect($init->json('total_chunks'))->toBe(3)
        ->and($init->json('chunk_size'))->toBe(1024)
        ->and($init->json('received_parts'))->toBe([]);

    foreach ($chunks as $i => $chunk) {
        resumablePut($token, $i + 1, $chunk)->assertOk();
    }

    test()->postJson(route('uploads.complete', $token))
        ->assertOk()
        ->assertJsonPath('status', 'done')
        ->assertJsonPath('file.extension', 'pdf');

    $file = File::first();
    expect($file)->not->toBeNull()
        ->and($file->extension)->toBe('pdf')
        ->and($file->size)->toBe(2500)
        ->and($file->disk)->toBe('uploads')
        ->and($file->getFirstMedia(File::COLLECTION))->not->toBeNull();

    // The assembled object is byte-identical and reachable via the gated route.
    expect(Storage::disk('uploads')->get($file->path))->toBe($content);
    test()->get(route('files.download', $file))->assertOk();

    $session = UploadSession::first();
    expect($session->status)->toBe(UploadStatus::Done)
        ->and($session->file_id)->toBe($file->id)
        ->and($session->parts()->count())->toBe(0);
});

it('is idempotent when a chunk is retried', function (): void {
    actingAsRole(SystemRole::Developer);

    $chunks = str_split(random_bytes(2500), 1024);
    $token = resumableInit('a.pdf', 2500)->assertCreated()->json('token');

    resumablePut($token, 1, $chunks[0])->assertOk();
    $second = resumablePut($token, 1, $chunks[0])->assertOk();

    expect($second->json('received_parts'))->toBe([1])
        ->and($second->json('received_bytes'))->toBe(1024)
        ->and(UploadSession::first()->parts()->count())->toBe(1);
});

it('reports received parts so an interrupted upload resumes, and blocks completing early', function (): void {
    actingAsRole(SystemRole::Developer);

    $chunks = str_split(random_bytes(2500), 1024);
    $token = resumableInit('b.pdf', 2500)->assertCreated()->json('token');

    resumablePut($token, 1, $chunks[0])->assertOk();
    resumablePut($token, 3, $chunks[2])->assertOk();

    expect(test()->getJson(route('uploads.show', $token))->json('received_parts'))->toBe([1, 3]);

    // Missing part 2 → cannot complete yet.
    test()->postJson(route('uploads.complete', $token))->assertStatus(422);

    resumablePut($token, 2, $chunks[1])->assertOk();
    test()->postJson(route('uploads.complete', $token))->assertOk()->assertJsonPath('status', 'done');
});

it('rejects a non-final chunk that is not the full chunk size', function (): void {
    actingAsRole(SystemRole::Developer);

    $token = resumableInit('c.pdf', 2500)->assertCreated()->json('token');

    // Part 1 of 3 must be exactly chunk_size; a short body is rejected.
    resumablePut($token, 1, random_bytes(500))->assertStatus(422);
});

it('accepts a video extension at init', function (): void {
    actingAsRole(SystemRole::Developer);

    resumableInit('clip.mp4', 2500)->assertCreated();

    expect(UploadSession::first()->original_name)->toBe('clip.mp4');
});

it('rejects a disallowed extension at init', function (): void {
    actingAsRole(SystemRole::Developer);

    resumableInit('malware.exe', 2500)->assertStatus(422);

    expect(UploadSession::count())->toBe(0);
});

it('rejects a zero or oversize declared size', function (): void {
    actingAsRole(SystemRole::Developer);

    resumableInit('z.pdf', 0)->assertStatus(422);

    config()->set('keen.max_upload_size', 500);
    resumableInit('big.pdf', 2000)->assertStatus(422);
});

it("forbids acting on another user's session", function (): void {
    actingAsRole(SystemRole::Developer);

    $chunks = str_split(random_bytes(2500), 1024);
    $token = resumableInit('o.pdf', 2500)->assertCreated()->json('token');
    resumablePut($token, 1, $chunks[0])->assertOk();

    $this->actingAs(User::factory()->create());

    test()->getJson(route('uploads.show', $token))->assertForbidden();
    resumablePut($token, 2, $chunks[1])->assertForbidden();
    test()->postJson(route('uploads.complete', $token))->assertForbidden();
    test()->deleteJson(route('uploads.destroy', $token))->assertForbidden();
});

it('aborts a session and cleans up its chunks', function (): void {
    actingAsRole(SystemRole::Developer);

    $chunks = str_split(random_bytes(2500), 1024);
    $token = resumableInit('d.pdf', 2500)->assertCreated()->json('token');
    resumablePut($token, 1, $chunks[0])->assertOk();

    expect(Storage::disk('uploads')->exists("tmp/{$token}/000001.part"))->toBeTrue();

    test()->deleteJson(route('uploads.destroy', $token))->assertOk();

    expect(Storage::disk('uploads')->exists("tmp/{$token}/000001.part"))->toBeFalse()
        ->and(UploadSession::first()->status)->toBe(UploadStatus::Aborted);
});
