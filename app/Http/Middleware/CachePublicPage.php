<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Full-page cache for the public, read-only pages.
 *
 * Rendering /analysis or a fixture page takes seconds on the shared host, which
 * hurts both visitors and Google's crawl rate. Every anonymous GET to an
 * allow-listed path is served from a rendered copy for up to TTL seconds.
 *
 * Runs before the session starts (it is prepended to the web group), so a
 * cache hit touches neither the session table nor the predictions table.
 *
 * Invalidation: PageCache::flush() bumps a version number, which orphans every
 * stored page at once. The prediction sync calls it, so new or settled
 * fixtures show up immediately instead of after the TTL.
 */
class CachePublicPage
{
    public const TTL = 600;          // seconds
    private const VERSION_KEY = 'page-cache:version';

    /** Path patterns that are safe to cache: no forms, no per-visitor content. */
    private const CACHEABLE = [
        '#^/$#',
        '#^/analysis$#',
        '#^/analysis/[a-z0-9-]+$#',
        '#^/combos$#',
        '#^/results$#',
        '#^/about$#',
        '#^/disclaimer$#',
        '#^/privacy-policy$#',
        '#^/sitemap\.xml$#',
        '#^/robots\.txt$#',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->cacheable($request)) {
            return $next($request);
        }

        $store = self::store();
        $key = 'page-cache:' . self::version() . ':' . $request->getPathInfo();

        if ($hit = $store->get($key)) {
            return response($hit['body'], 200, [
                'Content-Type' => $hit['type'],
                'X-Page-Cache' => 'HIT',
                'Cache-Control' => 'public, max-age=60',
            ]);
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200 && ! $response->headers->has('Location')) {
            $store->put($key, [
                'body' => $response->getContent(),
                'type' => $response->headers->get('Content-Type', 'text/html; charset=UTF-8'),
            ], self::TTL);
        }
        $response->headers->set('X-Page-Cache', 'MISS');

        return $response;
    }

    /** Drop every cached page (called after each prediction sync). */
    public static function flush(): void
    {
        self::store()->forever(self::VERSION_KEY, self::version() + 1);
    }

    private function cacheable(Request $request): bool
    {
        if (! config('lsa.page_cache', true) || ! $request->isMethodCacheable()) {
            return false;
        }
        // Query strings (utm_, filters) would multiply entries and may change output.
        if ($request->getQueryString() !== null && $request->getQueryString() !== '') {
            return false;
        }
        $path = $request->getPathInfo();
        foreach (self::CACHEABLE as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    private static function version(): int
    {
        return (int) self::store()->get(self::VERSION_KEY, 1);
    }

    /** File store: a cache hit must not touch the database. */
    private static function store(): \Illuminate\Contracts\Cache\Repository
    {
        return Cache::store(config('lsa.page_cache_store', 'file'));
    }
}
