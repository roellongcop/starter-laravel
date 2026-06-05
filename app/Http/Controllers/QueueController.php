<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Monitor for Laravel's database queue: pending (`jobs`) and failed
 * (`failed_jobs`) counts, with retry/clear actions.
 */
class QueueController extends Controller
{
    public function index(): Response
    {
        $this->authorize('queue.index');

        // The queue tables are excluded from backup dumps, but a restore (or a
        // half-finished migration) can briefly leave them absent — degrade to
        // an empty queue instead of 500ing.
        $hasJobs = Schema::hasTable('jobs');
        $hasFailed = Schema::hasTable('failed_jobs');

        $pending = $hasJobs
            ? DB::table('jobs')
                ->orderByDesc('id')->limit(25)->get()
                ->map(fn ($j) => [
                    'id' => $j->id,
                    'queue' => $j->queue,
                    'name' => $this->displayName($j->payload),
                    'attempts' => $j->attempts,
                ])
            : collect();

        $failed = $hasFailed
            ? DB::table('failed_jobs')
                ->orderByDesc('id')->limit(25)->get()
                ->map(fn ($j) => [
                    'id' => $j->id,
                    'uuid' => $j->uuid,
                    'queue' => $j->queue,
                    'name' => $this->displayName($j->payload),
                    'failed_at' => $j->failed_at,
                ])
            : collect();

        return Inertia::render('Queue/Index', [
            'stats' => [
                'pending' => $hasJobs ? DB::table('jobs')->count() : 0,
                'failed' => $hasFailed ? DB::table('failed_jobs')->count() : 0,
            ],
            'pending' => $pending,
            'failed' => $failed,
            'can' => ['manage' => request()->user()->can('queue.manage')],
        ]);
    }

    public function retry(Request $request): RedirectResponse
    {
        $this->authorize('queue.manage');

        $uuid = $request->string('uuid')->toString();
        Artisan::call('queue:retry', ['id' => $uuid !== '' ? [$uuid] : ['all']]);

        return back()->with('success', 'Retry dispatched.');
    }

    public function clearFailed(): RedirectResponse
    {
        $this->authorize('queue.manage');

        Artisan::call('queue:flush');

        return back()->with('success', 'Failed jobs cleared.');
    }

    public function clearPending(): RedirectResponse
    {
        $this->authorize('queue.manage');

        DB::table('jobs')->delete();

        return back()->with('success', 'Pending jobs cleared.');
    }

    protected function displayName(string $payload): string
    {
        $decoded = json_decode($payload, true);

        return $decoded['displayName'] ?? 'Job';
    }
}
