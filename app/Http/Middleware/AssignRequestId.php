<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stamps a correlation id on every request so logs, queued jobs, and (later)
 * traces can be tied together. Honors an inbound X-Request-Id (e.g. from a
 * proxy/load balancer) or mints a UUID, pushes it into Context — which Laravel
 * automatically adds to every log line and serializes into jobs dispatched in
 * this request — and echoes it back on the response.
 * See docs/infrastructure/observability.md.
 */
class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->header('X-Request-Id') ?: (string) Str::uuid();

        Context::add('request_id', $id);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $id);

        return $response;
    }
}
