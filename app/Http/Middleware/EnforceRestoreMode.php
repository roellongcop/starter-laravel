<?php

namespace App\Http\Middleware;

use App\Support\RestoreSentinel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * While a restore sentinel is active, return 503 to everyone except the operator
 * who triggered it (or a developer), and the auth routes (so the operator can
 * re-authenticate after the session store is replaced).
 */
class EnforceRestoreMode
{
    /** Route names always reachable during a restore. */
    protected array $allowed = [
        'login', 'logout',
        'password.request', 'password.email', 'password.reset', 'password.store', 'password.hint',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! RestoreSentinel::active()) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), $this->allowed, true)) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && ($user->id === RestoreSentinel::operatorId() || $user->hasRole('developer'))) {
            return $next($request);
        }

        abort(503, 'A database restore is in progress. Please try again shortly.');
    }
}
