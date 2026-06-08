<?php

namespace App\Http\Middleware;

use App\Enums\IpListType;
use App\Models\Ip;
use App\Settings\SystemSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Allow/deny requests by client IP using the Ip table (blacklist always;
 * whitelist when whitelist_ip_only). Inert by default; note the TrustProxies
 * caveat in docs/features/ip-rules.md.
 */
class EnforceIpRules
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $ip = $request->ip();

            $blacklisted = Ip::query()
                ->where('list_type', IpListType::Blacklist->value)
                ->where('ip_address', $ip)
                ->exists();

            if ($blacklisted) {
                abort(403, 'Your IP address is blocked.');
            }

            if (app(SystemSettings::class)->whitelist_ip_only) {
                $whitelisted = Ip::query()
                    ->where('list_type', IpListType::Whitelist->value)
                    ->where('ip_address', $ip)
                    ->exists();

                if (! $whitelisted) {
                    abort(403, 'Your IP address is not whitelisted.');
                }
            }
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable) {
            // Tables/settings not ready (e.g. mid-migration): fail open.
        }

        return $next($request);
    }
}
