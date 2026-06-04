<?php

namespace App\Http\Controllers;

use App\Enums\BackupStatus;
use App\Jobs\CreateBackupJob;
use App\Jobs\RestoreBackupJob;
use App\Models\Backup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Backup::class);

        $backups = Backup::query()
            ->keyset()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        return Inertia::render('Backups/Index', [
            'backups' => cursorResponse($backups, fn (Backup $b) => $this->row($b)),
            'can' => [
                'create' => $request->user()->can('backups.create'),
                'restore' => $request->user()->can('backups.update'),
                'delete' => $request->user()->can('backups.delete'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Backup::class);

        $backup = Backup::create(['status' => BackupStatus::Pending, 'disk' => 'backups']);
        CreateBackupJob::dispatch($backup);

        return back()->with('success', 'Backup queued.');
    }

    public function download(Backup $backup): StreamedResponse
    {
        $this->authorize('view', $backup);

        abort_unless($backup->filename && Storage::disk($backup->disk)->exists($backup->filename), 404);

        return Storage::disk($backup->disk)->download($backup->filename, basename($backup->filename));
    }

    public function restore(Backup $backup): RedirectResponse
    {
        $this->authorize('update', $backup);

        abort_unless($backup->status === BackupStatus::Generated, 422, 'Only generated backups can be restored.');

        RestoreBackupJob::dispatch($backup, auth()->id());

        return back()->with('success', 'Restore queued.');
    }

    public function destroy(Backup $backup): RedirectResponse
    {
        $this->authorize('delete', $backup);

        if ($backup->filename) {
            Storage::disk($backup->disk)->delete($backup->filename);
        }
        $backup->delete();

        return back()->with('success', 'Backup deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Backup $b): array
    {
        return [
            'token' => $b->token,
            'filename' => $b->filename ? basename($b->filename) : null,
            'disk' => $b->disk,
            'size' => $b->size,
            'status' => $b->status->value,
            'error_message' => $b->error_message,
            'created_at' => $b->created_at?->toIso8601String(),
        ];
    }
}
