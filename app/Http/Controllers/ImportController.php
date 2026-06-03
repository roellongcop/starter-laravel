<?php

namespace App\Http\Controllers;

use App\Enums\UserImportStatus;
use App\Http\Requests\StoreImportRequest;
use App\Imports\UsersImport;
use App\Jobs\ProcessImportJob;
use App\Models\UserImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', UserImport::class);

        $imports = UserImport::query()
            ->where('user_id', $request->user()->id)
            ->keyset()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        return Inertia::render('Imports/Index', [
            'imports' => cursorResponse($imports, fn (UserImport $i) => $this->row($i)),
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

        $path = $request->file('file')->store('', 'imports');

        $import = UserImport::create([
            'user_id' => $request->user()->id,
            'token' => Str::random(48),
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

        $sheets = Excel::toArray(new UsersImport, $import->filename, 'imports');
        $rows = collect($sheets[0] ?? []);

        return Inertia::render('Imports/Preview', [
            'import' => $this->row($import),
            'headings' => array_keys((array) $rows->first()),
            'rows' => $rows->take(10)->values(),
            'rowCount' => $rows->count(),
        ]);
    }

    public function process(Request $request, UserImport $import): RedirectResponse
    {
        $this->authorize('create', UserImport::class);
        abort_unless($import->user_id === $request->user()->id, 403);

        // Small imports run inline; larger ones queue + notify on completion.
        $sheets = Excel::toArray(new UsersImport, $import->filename, 'imports');
        $count = count($sheets[0] ?? []);

        if ($count <= config('keen.import_sync_threshold')) {
            ProcessImportJob::dispatchSync($import, notify: false);

            return redirect()->route('imports.index')->with('success', 'Import processed.');
        }

        ProcessImportJob::dispatch($import);

        return redirect()->route('imports.index')
            ->with('success', 'Import queued — you will be notified when it finishes.');
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
            'id' => $i->id,
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
