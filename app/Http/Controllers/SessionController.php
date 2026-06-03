<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Jenssegers\Agent\Agent;

/**
 * Read-only admin view over the database `sessions` table, plus a revoke action.
 */
class SessionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('sessions.index');

        $sessions = DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->select('sessions.id', 'sessions.ip_address', 'sessions.user_agent', 'sessions.last_activity', 'users.name as user_name')
            ->orderByDesc('sessions.last_activity')
            ->orderBy('sessions.id')
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        $currentId = $request->session()->getId();

        return Inertia::render('Sessions/Index', [
            'sessions' => cursorResponse($sessions, fn ($s) => $this->row($s, $currentId)),
            'can' => ['delete' => $request->user()->can('sessions.show')],
        ]);
    }

    public function destroy(Request $request, string $session): RedirectResponse
    {
        $this->authorize('sessions.show');

        if ($session === $request->session()->getId()) {
            return back()->with('error', 'You cannot revoke your own active session.');
        }

        DB::table('sessions')->where('id', $session)->delete();

        return back()->with('success', 'Session revoked.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(object $s, string $currentId): array
    {
        $agent = new Agent;
        $agent->setUserAgent((string) $s->user_agent);

        return [
            'id' => $s->id,
            'user' => $s->user_name,
            'ip_address' => $s->ip_address,
            'browser' => $agent->browser() ?: 'Unknown',
            'os' => $agent->platform() ?: 'Unknown',
            'last_activity' => $s->last_activity ? date('c', (int) $s->last_activity) : null,
            'is_current' => $s->id === $currentId,
        ];
    }
}
