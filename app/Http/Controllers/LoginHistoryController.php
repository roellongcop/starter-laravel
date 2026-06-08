<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Jenssegers\Agent\Agent;

/**
 * Read-only admin view over the login_history table: who signed in/out, from
 * what IP and device, and when. Append-only — there is no show/edit/delete.
 */
class LoginHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('login-history.index');

        $search = trim((string) $request->string('search'));
        $event = trim((string) $request->string('event'));

        $history = LoginHistory::query()
            ->with('user')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('user', fn ($u) => $u
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
                ->orWhere('ip_address', 'like', "%{$search}%")))
            ->when(in_array($event, ['login', 'logout'], true), fn ($q) => $q->where('event', $event))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        return Inertia::render('LoginHistory/Index', [
            'history' => cursorResponse($history, fn (LoginHistory $h) => $this->row($h)),
            'filters' => ['search' => $search, 'event' => $event],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(LoginHistory $h): array
    {
        $agent = new Agent;
        $agent->setUserAgent((string) $h->user_agent);

        return [
            'id' => $h->id,
            'event' => $h->event->value,
            'user' => optional($h->user)->name ?? 'Unknown',
            'email' => optional($h->user)->email,
            'ip_address' => $h->ip_address,
            'browser' => $agent->browser() ?: 'Unknown',
            'os' => $agent->platform() ?: 'Unknown',
            'device' => $agent->device() ?: 'Unknown',
            'created_at' => $h->created_at?->toIso8601String(),
        ];
    }
}
