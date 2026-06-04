<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VisitorController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('visitors.index');

        $search = trim((string) $request->string('search'));

        $visitors = Visitor::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('ip_address', 'like', "%{$search}%")
                ->orWhere('browser', 'like', "%{$search}%")
                ->orWhere('os', 'like', "%{$search}%")))
            ->keyset()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        return Inertia::render('Visitors/Index', [
            'visitors' => cursorResponse($visitors, fn (Visitor $v) => $this->row($v)),
            'filters' => ['search' => $search],
        ]);
    }

    public function show(Visitor $visitor): Response
    {
        $this->authorize('visitors.show');

        $visitor->load(['logs' => fn ($q) => $q->latest()->limit(50)]);

        return Inertia::render('Visitors/Show', [
            'visitor' => $this->row($visitor),
            'logs' => $visitor->logs->map(fn ($l) => [
                'token' => $l->token,
                'url' => $l->url,
                'action' => $l->action->value,
                'created_at' => $l->created_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Visitor $v): array
    {
        return [
            'token' => $v->token,
            'cookie_id' => $v->cookie_id,
            'ip_address' => $v->ip_address,
            'browser' => $v->browser,
            'os' => $v->os,
            'device' => $v->device,
            'visit_count' => $v->visit_count,
            'last_visit_at' => $v->last_visit_at?->toIso8601String(),
            'created_at' => $v->created_at?->toIso8601String(),
        ];
    }
}
