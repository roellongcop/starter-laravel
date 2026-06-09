<?php

namespace App\Http\Controllers;

use App\Enums\UserExportStatus;
use App\Exports\UsersExport;
use App\Filters\ExportFilters;
use App\Http\Requests\StoreExportRequest;
use App\Jobs\DispatchExportJob;
use App\Jobs\GenerateExportJob;
use App\Models\UserExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function index(Request $request, ExportFilters $filters): Response
    {
        $this->authorize('viewAny', UserExport::class);

        $exports = $filters->apply(UserExport::query())
            ->keyset()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        return Inertia::render('Exports/Index', [
            'exports' => cursorResponse($exports, fn (UserExport $e) => $this->row($e)),
            'filters' => $filters->echoBack(),
            'can' => ['create' => $request->user()->can('exports.create')],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', UserExport::class);

        return Inertia::render('Exports/Create', [
            'formats' => ['csv', 'xls', 'xlsx', 'pdf'],
            'resources' => ['users'],
        ]);
    }

    public function store(StoreExportRequest $request): RedirectResponse
    {
        $this->authorize('create', UserExport::class);

        $filters = $request->input('filters', []);

        $export = UserExport::create([
            'user_id' => $request->user()->id,
            'format' => $request->string('format'),
            'resource' => $request->string('resource'),
            'filters' => $filters,
            'status' => UserExportStatus::Pending,
        ]);

        // Small exports run inline (immediate download); larger ones queue + notify.
        $count = (new UsersExport($filters))->query()->count();

        if ($count <= config('keen.export_sync_threshold')) {
            GenerateExportJob::dispatchSync($export, notify: false);

            return redirect()->route('exports.index')->with('success', 'Export ready.');
        }

        // Larger exports shard into ~5k-row files processed as a batch, then zip.
        DispatchExportJob::dispatch($export);

        return redirect()->route('exports.index')
            ->with('success', 'Export queued — you will be notified when it is ready.');
    }

    public function download(Request $request, string $token): StreamedResponse
    {
        $export = UserExport::where('token', $token)->firstOrFail();

        abort_unless($export->user_id === $request->user()->id, 403);
        abort_unless(
            $export->status === UserExportStatus::Done
                && $export->filename
                && Storage::disk('exports')->exists($export->filename),
            404,
        );

        return Storage::disk('exports')->download($export->filename);
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(UserExport $e): array
    {
        return [
            'token' => $e->token,
            'format' => $e->format,
            'resource' => $e->resource,
            'row_count' => $e->row_count,
            'total_rows' => $e->total_rows,
            'processed_rows' => $e->processed_rows,
            'status' => $e->status->value,
            'error_message' => $e->error_message,
            'created_at' => $e->created_at?->toIso8601String(),
        ];
    }
}
