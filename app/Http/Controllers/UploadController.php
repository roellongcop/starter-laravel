<?php

namespace App\Http\Controllers;

use App\Actions\FinalizeUpload;
use App\Enums\UploadStatus;
use App\Http\Requests\InitUploadRequest;
use App\Jobs\FinalizeUploadJob;
use App\Models\File;
use App\Models\UploadSession;
use App\Support\ChunkStorage\ChunkStorageFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Resumable chunked uploads (the Google-Drive-style flow): init a session, PUT
 * chunks, resume from the gap after a drop, then complete into a File. Creating
 * a session is gated by `files.create`; every session-scoped action additionally
 * requires ownership. Chunk bytes ride the `web` group's CSRF cookie (axios sends
 * X-XSRF-TOKEN), so no exemption is needed.
 */
class UploadController extends Controller
{
    public function __construct(
        private ChunkStorageFactory $chunkStorage,
        private FinalizeUpload $finalize,
    ) {}

    /** Open a session and begin the underlying (multipart/local) upload. */
    public function store(InitUploadRequest $request): JsonResponse
    {
        $this->authorize('create', File::class);

        $data = $request->validated();
        $size = (int) $data['size'];
        $ext = strtolower(pathinfo((string) $data['original_name'], PATHINFO_EXTENSION));
        $chunkSize = $this->chunkSizeFor($size);

        $session = new UploadSession([
            'original_name' => $data['original_name'],
            'extension' => $ext,
            'mime' => $data['mime'] ?? null,
            'size' => $size,
            'chunk_size' => $chunkSize,
            'total_chunks' => max(1, (int) ceil($size / $chunkSize)),
            'driver' => ChunkStorageFactory::currentDriver(),
            'object_key' => now()->format('Y/m').'/'.Str::random(40).'.'.$ext,
            'status' => UploadStatus::Uploading,
            'owner_id' => $request->user()->id,
            'tag' => $data['tag'] ?? null,
            'expires_at' => now()->addHours((int) config('keen.upload_session_ttl_hours', 24)),
        ]);
        $session->save();

        $state = $this->chunkStorage->for($session)->begin($session);
        if ($state !== []) {
            $session->update($state);
        }

        return response()->json($this->status($session), 201);
    }

    /** Current state of a session — drives resume (received_parts) and polling. */
    public function show(Request $request, UploadSession $uploadSession): JsonResponse
    {
        $this->authorizeOwner($request, $uploadSession);

        return response()->json($this->status($uploadSession));
    }

    /** Store one chunk (raw binary body). Idempotent on the part number. */
    public function part(Request $request, UploadSession $uploadSession, int $part): JsonResponse
    {
        $this->authorizeOwner($request, $uploadSession);
        $this->assertActive($uploadSession);

        if ($part < 1 || $part > (int) $uploadSession->total_chunks) {
            abort(422, 'Part number out of range.');
        }

        $body = (string) $request->getContent();
        if ($body === '') {
            abort(422, 'Empty chunk.');
        }

        // Every part but the last must be exactly chunk_size — this also keeps
        // S3 parts above the 5 MiB minimum.
        $isLast = $part === (int) $uploadSession->total_chunks;
        if (! $isLast && strlen($body) !== (int) $uploadSession->chunk_size) {
            abort(422, 'Unexpected chunk size.');
        }

        // A retried chunk is a no-op: the unique (session, part) row already
        // exists, so we neither re-store nor double-count.
        if ($uploadSession->parts()->where('part_number', $part)->doesntExist()) {
            $result = $this->chunkStorage->for($uploadSession)->putPart($uploadSession, $part, $body);
            $uploadSession->parts()->updateOrCreate(
                ['part_number' => $part],
                ['etag' => $result['etag'], 'size' => $result['size']],
            );
        }

        return response()->json($this->status($uploadSession->refresh()));
    }

    /** Verify all chunks arrived, then assemble into a File (inline or queued). */
    public function complete(Request $request, UploadSession $uploadSession): JsonResponse
    {
        $this->authorizeOwner($request, $uploadSession);

        // Idempotent terminal/in-flight responses (a retried complete must not
        // re-dispatch assembly or 409).
        if ($uploadSession->status === UploadStatus::Done && $uploadSession->file_id !== null) {
            return response()->json(['status' => 'done', 'file' => $this->fileRow($uploadSession->file)]);
        }
        if ($uploadSession->status === UploadStatus::Assembling) {
            return response()->json(['status' => 'assembling', 'token' => $uploadSession->token], 202);
        }

        $this->assertActive($uploadSession);

        $received = $uploadSession->parts()->count();
        if ($received !== (int) $uploadSession->total_chunks) {
            abort(422, "Upload incomplete: {$received} of {$uploadSession->total_chunks} parts received.");
        }

        // Local-driver concatenation of a large file is heavy → queue it and let
        // the client poll. S3 multipart completes server-side, so finalize inline.
        $queueAssembly = $uploadSession->driver !== 's3'
            && (int) $uploadSession->size > (int) config('keen.upload_local_concat_async_threshold');

        if ($queueAssembly) {
            $uploadSession->update(['status' => UploadStatus::Assembling]);
            FinalizeUploadJob::dispatch($uploadSession);

            return response()->json(['status' => 'assembling', 'token' => $uploadSession->token], 202);
        }

        $file = ($this->finalize)($uploadSession);

        return response()->json(['status' => 'done', 'file' => $this->fileRow($file)]);
    }

    /** Abort and clean up an in-flight session. */
    public function destroy(Request $request, UploadSession $uploadSession): JsonResponse
    {
        $this->authorizeOwner($request, $uploadSession);

        if ($uploadSession->status !== UploadStatus::Done) {
            $this->chunkStorage->for($uploadSession)->abort($uploadSession);
            $uploadSession->update(['status' => UploadStatus::Aborted]);
        }

        return response()->json(['status' => 'aborted']);
    }

    private function authorizeOwner(Request $request, UploadSession $session): void
    {
        abort_unless($session->owner_id === $request->user()->id, 403);
    }

    private function assertActive(UploadSession $session): void
    {
        abort_if($session->isExpired(), 410, 'Upload session expired.');
        abort_if(
            in_array($session->status, [UploadStatus::Done, UploadStatus::Aborted, UploadStatus::Failed], true),
            409,
            'Upload session is no longer active.'
        );
    }

    /**
     * Pick a chunk size: the configured baseline, scaled up for very large files
     * to keep the part count under S3's 10,000 limit, capped at 32 MB so the
     * chunk request stays under the 64 MB body limit.
     */
    private function chunkSizeFor(int $size): int
    {
        $base = (int) config('keen.upload_chunk_size', 8 * 1024 * 1024);
        $cap = 32 * 1024 * 1024;
        $needed = (int) ceil($size / 9000);

        return (int) min($cap, max($base, $needed));
    }

    /**
     * @return array<string, mixed>
     */
    private function status(UploadSession $session): array
    {
        return [
            'token' => $session->token,
            'status' => $session->status->value,
            'chunk_size' => (int) $session->chunk_size,
            'total_chunks' => (int) $session->total_chunks,
            'received_parts' => $session->receivedPartNumbers(),
            'received_bytes' => (int) $session->parts()->sum('size'),
            'size' => (int) $session->size,
            'file' => $session->file_id !== null && $session->file !== null
                ? $this->fileRow($session->file)
                : null,
        ];
    }

    /**
     * The same row shape FileController returns, so onUploaded gets identical data
     * from the resumable and single-shot paths.
     *
     * @return array<string, mixed>
     */
    private function fileRow(File $file): array
    {
        return [
            'token' => $file->token,
            'original_name' => $file->original_name,
            'extension' => $file->extension,
            'mime' => $file->mime,
            'size' => $file->size,
            'tag' => $file->tag,
            'owner' => $file->owner?->name,
            'created_at' => $file->created_at?->toIso8601String(),
        ];
    }
}
