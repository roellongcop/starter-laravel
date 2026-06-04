<?php

namespace App\Http\Controllers;

use App\Models\VisitLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VisitLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('visit-logs.index');

        $search = trim((string) $request->string('search'));

        $logs = VisitLog::query()
            ->when($search !== '', fn ($q) => $q->where('url', 'like', "%{$search}%"))
            ->with('visitor:id,ip_address')
            ->keyset()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        return Inertia::render('VisitLogs/Index', [
            'logs' => cursorResponse($logs, fn (VisitLog $l) => [
                'token' => $l->token,
                'visitor_ip' => $l->visitor?->ip_address,
                'url' => $l->url,
                'action' => $l->action->value,
                'created_at' => $l->created_at?->toIso8601String(),
            ]),
            'filters' => ['search' => $search],
        ]);
    }
}
