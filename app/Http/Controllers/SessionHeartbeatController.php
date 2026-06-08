<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnforceIdleTimeout;
use Symfony\Component\HttpFoundation\Response;

class SessionHeartbeatController extends Controller
{
    /**
     * Mark the session as active. The actual refresh happens in
     * {@see EnforceIdleTimeout}; this endpoint just needs
     * to be a real (non-partial) request that reaches it, pinged by the
     * client-side `useIdleLogout` hook on genuine user activity.
     */
    public function __invoke(): Response
    {
        return response()->noContent();
    }
}
