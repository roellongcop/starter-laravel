<?php

namespace App\Support;

use Illuminate\Support\Facades\Request;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\Resolver;

/**
 * Resolves the page a request originated from (HTTP Referer) for the audit trail.
 *
 * The built-in `url` resolver records the endpoint that was hit. For background
 * uploads — e.g. a POST to /documents fired from the user edit form — that endpoint
 * is all `url` ever shows, so you can't tell which page the action happened on. The
 * referrer fills that gap, and is naturally null off the web (console commands carry
 * no Referer header).
 */
class ReferrerResolver implements Resolver
{
    public static function resolve(Auditable $auditable): ?string
    {
        $referrer = Request::header('referer');

        return is_string($referrer) && $referrer !== '' ? $referrer : null;
    }
}
