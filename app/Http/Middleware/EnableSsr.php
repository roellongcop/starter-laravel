<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opt a route group into Inertia SSR (off by default). See
 * docs/features/seo-and-ssr.md.
 */
class EnableSsr
{
    public function handle(Request $request, Closure $next): Response
    {
        config(['inertia.ssr.enabled' => true]);

        return $next($request);
    }
}
