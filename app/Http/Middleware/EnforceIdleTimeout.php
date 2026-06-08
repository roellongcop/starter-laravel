<?php

namespace App\Http\Middleware;

use App\Settings\SystemSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side idle timeout: invalidate an authenticated session after
 * `auto_logout_seconds` (0 = off) of inactivity, regardless of page or whether
 * the client-side `useIdleLogout` hook ran. This is the real security boundary;
 * the hook only provides the warning toast + proactive redirect.
 *
 * Background partial reloads (e.g. the Exports/Imports status polling) must not
 * count as activity, otherwise an open polling page would keep a walked-away
 * session alive forever.
 */
class EnforceIdleTimeout
{
    private const SESSION_KEY = 'idle.last_activity';

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $timeout = app(SystemSettings::class)->auto_logout_seconds;
        } catch (\Throwable) {
            // Settings table not ready (e.g. mid-migration): fail open.
            return $next($request);
        }

        if ($timeout <= 0 || Auth::guard('web')->guest()) {
            return $next($request);
        }

        $now = now()->getTimestamp();
        $last = $request->session()->get(self::SESSION_KEY);

        // Enforce *before* refreshing, so a late heartbeat can never resurrect
        // an already-expired session.
        if ($last !== null && ($now - (int) $last) > $timeout) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login', [], 303)
                ->with('error', 'You were logged out due to inactivity.');
        }

        if (! $request->hasHeader('X-Inertia-Partial-Component')) {
            $request->session()->put(self::SESSION_KEY, $now);
        }

        return $next($request);
    }
}
