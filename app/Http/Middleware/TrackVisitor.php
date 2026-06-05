<?php

namespace App\Http\Middleware;

use App\Enums\VisitLogAction;
use App\Models\Visitor;
use App\Settings\SystemSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records anonymous visitor activity (cookie-based) when enable_visitor is on;
 * GET page loads only, fails open. See docs/features/visitor-and-ip.md.
 */
class TrackVisitor
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if (
                $request->isMethod('GET')
                && ! $request->is('build/*', 'up', 'storage/*')
                && app(SystemSettings::class)->enable_visitor
            ) {
                $this->track($request);
            }
        } catch (\Throwable) {
            // never let tracking break the response
        }

        return $response;
    }

    protected function track(Request $request): void
    {
        $cookieId = $request->cookie('visitor_id') ?: (string) Str::uuid();
        Cookie::queue('visitor_id', $cookieId, 60 * 24 * 365);

        $agent = new Agent;
        $agent->setUserAgent((string) $request->userAgent());

        $visitor = Visitor::withInactive()->firstOrNew(['cookie_id' => $cookieId]);
        $visitor->fill([
            'ip_address' => $request->ip(),
            'browser' => $agent->browser() ?: 'Unknown',
            'os' => $agent->platform() ?: 'Unknown',
            'device' => $agent->isTablet() ? 'Tablet' : ($agent->isPhone() ? 'Mobile' : 'Desktop'),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'last_visit_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
        $visitor->visit_count = ($visitor->visit_count ?? 0) + 1;
        $visitor->save();

        $visitor->logs()->create([
            'url' => Str::limit($request->fullUrl(), 2000, ''),
            'action' => VisitLogAction::PageView,
        ]);
    }
}
