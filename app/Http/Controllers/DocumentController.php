<?php

namespace App\Http\Controllers;

use App\Actions\StoreUploadedFile;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\File;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Self-service user documents (pdf/doc/docx). Documents are ordinary File
 * records owned by the uploader; stored/served from the private `uploads` disk.
 * Auth-only and owner-scoped — uploading your own documents needs no files.*
 * permission.
 */
class DocumentController extends Controller
{
    /**
     * Store an uploaded document and return its metadata. By default it belongs
     * to the caller; an admin (users.update) may upload on another user's behalf
     * by passing user_id.
     */
    public function store(StoreDocumentRequest $request, StoreUploadedFile $store): JsonResponse
    {
        $ownerId = $request->user()->id;
        $targetId = $request->integer('user_id');

        if ($targetId !== 0 && $targetId !== $ownerId) {
            abort_unless($request->user()->can('users.update'), 403);
            $ownerId = $targetId;
        }

        $file = $store($request->file('file'), $ownerId, $request->input('tag') ?? 'document');

        return response()->json($this->row($file));
    }

    /**
     * Stream a document as an attachment. Allowed for the owner, anyone who can
     * view users (admin browsing a user), or the files admin.
     */
    public function download(Request $request, File $file): StreamedResponse
    {
        abort_unless($this->canRead($request, $file), 403);

        [$disk, $path, $media] = $this->mediaParts($file);

        return $disk->download($path, $file->original_name, [
            'Content-Type' => $media->mime_type,
        ]);
    }

    /** Stream a document inline (for the in-app file viewer). Same read gate. */
    public function view(Request $request, File $file): StreamedResponse
    {
        abort_unless($this->canRead($request, $file), 403);

        [$disk, $path, $media] = $this->mediaParts($file);

        return $disk->response($path, $file->original_name, [
            'Content-Type' => $media->mime_type,
        ]);
    }

    /**
     * Delete a document. Allowed for the owner, an admin who can update users,
     * or the files admin.
     */
    public function destroy(Request $request, File $file): RedirectResponse
    {
        abort_unless($this->canWrite($request, $file), 403);

        $file->delete();

        return back()->with('success', 'Document deleted.');
    }

    protected function canRead(Request $request, File $file): bool
    {
        return $file->owner_id === $request->user()->id
            || $request->user()->can('users.show')
            || $request->user()->can('files.view');
    }

    protected function canWrite(Request $request, File $file): bool
    {
        return $file->owner_id === $request->user()->id
            || $request->user()->can('users.update')
            || $request->user()->can('files.view');
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(File $file): array
    {
        return [
            'id' => $file->id,
            'name' => $file->original_name,
            'url' => route('documents.download', $file),
            'size' => $file->size,
            'extension' => $file->extension,
            'created_at' => $file->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{0: Filesystem, 1: string, 2: Media}
     */
    protected function mediaParts(File $file): array
    {
        $media = $file->getFirstMedia(File::COLLECTION);
        abort_if($media === null, 404);

        $disk = Storage::disk($media->disk);
        $path = $media->getPathRelativeToRoot();
        abort_unless($disk->exists($path), 404);

        return [$disk, $path, $media];
    }
}
