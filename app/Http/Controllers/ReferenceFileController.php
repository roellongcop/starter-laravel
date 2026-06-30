<?php

namespace App\Http\Controllers;

use App\Actions\StoreUploadedFile;
use App\Filters\ReferenceFileFilters;
use App\Http\Controllers\Concerns\ProvidesOptions;
use App\Http\Controllers\Concerns\ResolvesDataTags;
use App\Http\Requests\StoreReferenceFileRequest;
use App\Http\Requests\UpdateReferenceFileRequest;
use App\Models\File;
use App\Models\Organization;
use App\Models\ReferenceFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReferenceFileController extends Controller
{
    use ProvidesOptions;
    use ResolvesDataTags;

    public function index(Request $request, ReferenceFileFilters $filters): Response
    {
        $this->authorize('viewAny', ReferenceFile::class);

        $references = $filters->apply(ReferenceFile::query()->with(['organization', 'file', 'tags']))
            ->keysetByToken()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString()
            ->through(fn (ReferenceFile $reference) => $this->row($reference));

        return Inertia::render('ReferenceFiles/Index', [
            'references' => Inertia::scroll($references),
            'filters' => $filters->echoBack(),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        return $this->optionsResponse(
            $request,
            ReferenceFile::class,
            fn (ReferenceFile $reference): array => ['value' => $reference->token, 'label' => $reference->name],
            organizationScoped: true,
        );
    }

    public function store(StoreReferenceFileRequest $request): RedirectResponse
    {
        $this->authorize('create', ReferenceFile::class);

        $data = $this->resolveData($request->validated());
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $reference = ReferenceFile::create($data);
        $reference->syncDataTags($tags);

        return redirect()->route('reference-files.index')->with('success', 'Reference created.');
    }

    public function show(ReferenceFile $referenceFile): Response
    {
        $this->authorize('view', $referenceFile);

        return Inertia::render('ReferenceFiles/Show', [
            'reference' => $this->row($referenceFile->load(['organization', 'file', 'tags'])),
        ]);
    }

    public function update(UpdateReferenceFileRequest $request, ReferenceFile $referenceFile): RedirectResponse
    {
        $this->authorize('update', $referenceFile);

        $previousFileId = $referenceFile->file_id;
        $data = $this->resolveData($request->validated());
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $referenceFile->update($data);
        $referenceFile->syncDataTags($tags);

        // The reference owns its single file; drop the previous one when it was
        // replaced or removed so uploads don't accumulate orphaned File rows.
        if ($previousFileId !== null && $previousFileId !== $referenceFile->file_id) {
            File::find($previousFileId)?->delete();
        }

        return back()->with('success', 'Reference updated.');
    }

    public function destroy(ReferenceFile $referenceFile): RedirectResponse
    {
        $this->authorize('delete', $referenceFile);

        $fileId = $referenceFile->file_id;
        $referenceFile->delete();

        if ($fileId !== null) {
            File::find($fileId)?->delete();
        }

        return redirect()->route('reference-files.index')->with('success', 'Reference deleted.');
    }

    /**
     * Accept the single file from the <FileDropzone> and return its token; the
     * reference form posts that token back on save.
     */
    public function upload(Request $request, StoreUploadedFile $store): JsonResponse
    {
        $this->authorize('create', ReferenceFile::class);

        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'png', 'jpg', 'jpeg', 'gif', 'webp'];
        $maxKb = (int) (config('media-library.max_file_size', 10 * 1024 * 1024) / 1024);

        $request->validate([
            'file' => [
                'required',
                'file',
                "max:{$maxKb}",
                'mimes:'.implode(',', $allowed),
                'extensions:'.implode(',', $allowed),
            ],
        ]);

        $file = $store($request->file('file'), $request->user()->id, 'reference');

        return response()->json([
            'token' => $file->token,
            'name' => $file->original_name,
            'size' => $file->size,
            'extension' => $file->extension,
        ]);
    }

    /** Stream the attached file as a gated download (reference-files.show). */
    public function download(ReferenceFile $referenceFile): StreamedResponse
    {
        $this->authorize('view', $referenceFile);

        $file = $referenceFile->file;
        abort_if($file === null, 404);

        $media = $file->getFirstMedia(File::COLLECTION);
        abort_if($media === null, 404);

        $disk = Storage::disk($media->disk);
        $path = $media->getPathRelativeToRoot();
        abort_unless($disk->exists($path), 404);

        return $disk->download($path, $file->original_name, [
            'Content-Type' => $media->mime_type,
        ]);
    }

    /**
     * Resolve the org + file tokens to ids (only tokens cross the wire) and drop
     * the wire-only keys.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function resolveData(array $data): array
    {
        $data['organization_id'] = Organization::where('token', $data['organization'])->value('id');
        $data['file_id'] = ! empty($data['file_token'])
            ? File::where('token', $data['file_token'])->value('id')
            : null;

        unset($data['organization'], $data['file_token']);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(ReferenceFile $reference): array
    {
        return [
            'token' => $reference->token,
            'name' => $reference->name,
            'description' => $reference->description,
            'organization' => $reference->organization->token,
            'organization_name' => $reference->organization->name,
            'file_token' => $reference->file?->token,
            'file_name' => $reference->file?->original_name,
            'file_url' => $reference->file ? route('reference-files.download', $reference->token) : null,
            'tags' => $this->serializeTags($reference->tags),
            'record_status' => $reference->record_status->value,
            'created_at' => $reference->created_at?->toIso8601String(),
        ];
    }
}
