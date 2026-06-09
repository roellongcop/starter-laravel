<?php

namespace App\Http\Controllers;

use App\Enums\UserImportStatus;
use App\Filters\ImportFilters;
use App\Http\Requests\StoreImportRequest;
use App\Imports\UsersPreview;
use App\Jobs\DispatchImportJob;
use App\Models\UserImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function index(Request $request, ImportFilters $filters): Response
    {
        $this->authorize('viewAny', UserImport::class);

        $imports = $filters->apply(UserImport::query())
            ->keyset()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        return Inertia::render('Imports/Index', [
            'imports' => cursorResponse($imports, fn (UserImport $i) => $this->row($i)),
            'filters' => $filters->echoBack(),
            'can' => ['create' => $request->user()->can('imports.create')],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', UserImport::class);

        return Inertia::render('Imports/Create', ['resources' => ['users']]);
    }

    public function store(StoreImportRequest $request): RedirectResponse
    {
        $this->authorize('create', UserImport::class);

        $path = $request->file('file')->store(now()->format('Y/m'), 'imports');

        $import = UserImport::create([
            'user_id' => $request->user()->id,
            'resource' => $request->string('resource'),
            'filename' => $path,
            'status' => UserImportStatus::Pending,
        ]);

        return redirect()->route('imports.preview', $import)
            ->with('success', 'File uploaded — review and confirm.');
    }

    public function preview(Request $request, UserImport $import): Response
    {
        $this->authorize('view', $import);
        abort_unless($import->user_id === $request->user()->id, 403);

        // Sample only the first rows (WithLimit) — never parse the whole upload here.
        $rows = collect(Excel::toArray(new UsersPreview, $import->filename, 'imports')[0] ?? []);

        return Inertia::render('Imports/Preview', [
            'import' => $this->row($import),
            'headings' => array_keys((array) $rows->first()),
            'rows' => $rows->values(),
        ]);
    }

    public function process(Request $request, UserImport $import): RedirectResponse
    {
        $this->authorize('create', UserImport::class);
        abort_unless($import->user_id === $request->user()->id, 403);

        // Always queue: parsing/counting happens in the background coordinator, so the
        // request returns instantly even for 100k-row files. The list shows live progress.
        DispatchImportJob::dispatch($import);

        return redirect()->route('imports.index')
            ->with('success', 'Import queued — you will be notified when it finishes.');
    }

    /** Discard an import: drop its uploaded file + error report, then the record. */
    public function destroy(Request $request, UserImport $import): RedirectResponse
    {
        $this->authorize('delete', $import);
        abort_unless($import->user_id === $request->user()->id, 403);
        abort_if($import->status === UserImportStatus::Running, 409, 'Cannot delete a running import.');

        foreach (array_filter([$import->filename, $import->error_report_path]) as $path) {
            Storage::disk('imports')->delete($path);
        }
        $import->delete();

        // Cancel comes from this row's preview page, so redirect to the list (not back()).
        return redirect()->route('imports.index')->with('success', 'Import discarded.');
    }

    /** Stream the originally uploaded import file back to its owner. */
    public function download(Request $request, UserImport $import): StreamedResponse
    {
        $this->authorize('view', $import);
        abort_unless($import->user_id === $request->user()->id, 403);
        abort_unless($import->filename && Storage::disk('imports')->exists($import->filename), 404);

        return Storage::disk('imports')->download($import->filename);
    }

    public function errors(Request $request, UserImport $import): StreamedResponse
    {
        $this->authorize('view', $import);
        abort_unless($import->user_id === $request->user()->id, 403);
        abort_unless($import->error_report_path && Storage::disk('imports')->exists($import->error_report_path), 404);

        return Storage::disk('imports')->download($import->error_report_path, "import-{$import->id}-errors.csv");
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(UserImport $i): array
    {
        return [
            'token' => $i->token,
            'resource' => $i->resource,
            'filename' => $i->filename,
            'total' => $i->total,
            'success' => $i->success,
            'failed' => $i->failed,
            'has_error_report' => $i->error_report_path !== null,
            'status' => $i->status->value,
            'created_at' => $i->created_at?->toIso8601String(),
        ];
    }
}
