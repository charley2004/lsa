<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Guards the inbound sync endpoint the Python algorithm posts to. A shared
// secret, not a user login — nothing on the other end is a browser with a
// session. Set SYNC_SHARED_SECRET in .env; the algorithm side sends the same
// value as the X-Sync-Key header (see algorithm/sync_to_website.py).
class VerifySyncKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.sync_shared_secret');

        if (! $expected) {
            // No secret configured — refuse rather than silently accepting
            // unauthenticated writes. Set SYNC_SHARED_SECRET in .env to enable.
            abort(500, 'SYNC_SHARED_SECRET is not configured on this server.');
        }

        if (! hash_equals($expected, (string) $request->header('X-Sync-Key'))) {
            abort(401, 'Missing or invalid X-Sync-Key header.');
        }

        return $next($request);
    }
}
