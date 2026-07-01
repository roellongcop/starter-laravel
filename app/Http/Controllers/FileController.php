<?php

namespace App\Http\Controllers;

use App\Actions\StoreUploadedFile;
use App\Filters\FileFilters;
use App\Http\Requests\StoreFileRequest;
use App\Models\File;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function index(Request $request, FileFilters $filters): Response
    {
        $this->authorize('viewAny', File::class);

        $files = $filters->apply(File::query())
            ->with('owner:id,name')
            ->keyset()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        return Inertia::render('Files/Index', [
            'files' => cursorResponse($files, fn (File $f) => $this->row($f)),
            'filters' => $filters->echoBack(),
            'can' => [
                'create' => $request->user()->can('files.create'),
                'delete' => $request->user()->can('files.delete'),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', File::class);

        return Inertia::render('Files/Create');
    }

    public function store(StoreFileRequest $request, StoreUploadedFile $store): RedirectResponse|JsonResponse
    {
        $this->authorize('create', File::class);

        $file = $store($request->file('file'), $request->user()->id, $request->input('tag'));

        // The drag-and-drop uploader posts each file via axios and expects JSON;
        // a plain form submit still gets the redirect-to-detail behaviour.
        if ($request->expectsJson()) {
            return response()->json($this->row($file));
        }

        return redirect()->route('files.show', $file)->with('success', 'File uploaded.');
    }

    public function show(File $file): Response
    {
        $this->authorize('view', $file);

        return Inertia::render('Files/Show', ['file' => $this->row($file, detailed: true)]);
    }

    public function destroy(File $file): RedirectResponse
    {
        $this->authorize('delete', $file);

        // medialibrary removes the backing media on model delete.
        $file->delete();

        return back(fallback: route('files.index'))->with('success', 'File deleted.');
    }

    /** Gated attachment download from the private disk. */
    public function download(File $file): StreamedResponse
    {
        $this->authorize('view', $file);

        [$disk, $path, $media] = $this->mediaParts($file);

        return $disk->download($path, $media->file_name);
    }

    /** Gated inline display (e.g. image preview) from the private disk. */
    public function preview(File $file): StreamedResponse
    {
        $this->authorize('view', $file);

        [$disk, $path, $media] = $this->mediaParts($file);

        return $disk->response($path, $media->file_name, [
            'Content-Type' => $media->mime_type,
        ]);
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

    /**
     * @return array<string, mixed>
     */
    protected function row(File $file, bool $detailed = false): array
    {
        $data = [
            'token' => $file->token,
            'original_name' => $file->original_name,
            'extension' => $file->extension,
            'mime' => $file->mime,
            'size' => $file->size,
            'tag' => $file->tag,
            'owner' => $file->owner?->name,
            'created_at' => $file->created_at?->toIso8601String(),
        ];

        if ($detailed) {
            $data['disk'] = $file->disk;
            $data['path'] = $file->path;
            $data['is_image'] = str_starts_with((string) $file->mime, 'image/');
        }

        return $data;
    }
}
