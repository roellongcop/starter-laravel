<?php

namespace App\Http\Controllers;

use App\Filters\LogFilters;
use App\Models\Audit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only audit trail (owen-it/laravel-auditing) with browser/os/device parsed
 * from the user agent.
 */
class LogController extends Controller
{
    public function index(Request $request, LogFilters $filters): Response
    {
        $this->authorize('logs.index');

        $audits = $filters->apply(Audit::query())
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        return Inertia::render('Logs/Index', [
            'logs' => cursorResponse($audits, fn (Audit $a) => $this->row($a)),
            'filters' => $filters->echoBack(),
        ]);
    }

    public function show(Audit $log): Response
    {
        $this->authorize('logs.show');

        $log->load('user');

        return Inertia::render('Logs/Show', [
            'log' => [
                ...$this->row($log),
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'url' => $log->url,
                'referrer' => $log->referrer,
                'user_agent' => $log->user_agent,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Audit $a): array
    {
        return [
            'token' => $a->token,
            'event' => $a->event,
            'auditable_type' => class_basename((string) $a->auditable_type),
            'auditable_id' => $a->auditable_id,
            'user' => optional($a->user)->name ?? 'System',
            'ip_address' => $a->ip_address,
            'browser' => $a->browser,
            'os' => $a->os,
            'device' => $a->device,
            'tags' => $a->tags,
            'created_at' => $a->created_at?->toIso8601String(),
        ];
    }
}
