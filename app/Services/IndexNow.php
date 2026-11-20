<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tells search engines about new or changed pages the moment they change, so
 * nobody has to "Request indexing" by hand.
 *
 * IndexNow (https://www.indexnow.org) is read by Bing (which also powers
 * DuckDuckGo and Yahoo), Yandex, Seznam and Naver; one submission reaches all
 * of them. Google doesn't take IndexNow; it discovers changes from the sitemap's
 * <lastmod> dates, which update on every sync too.
 *
 * Ownership is proven by serving the key at /{key}.txt (see routes/web.php).
 * Only runs in production, never throws: a slow or failing ping must not
 * break the algorithm's sync.
 */
class IndexNow
{
    public function submit(array $urls): int
    {
        $urls = array_values(array_unique(array_filter($urls)));
        $key = config('lsa.indexnow_key');

        if (! $urls || ! $key || ! config('lsa.indexnow_enabled')) {
            return 0;
        }

        $host = parse_url(config('app.url'), PHP_URL_HOST);

        try {
            foreach (array_chunk($urls, 10000) as $chunk) {  // protocol limit per request
                Http::timeout(5)->acceptJson()->post('https://api.indexnow.org/indexnow', [
                    'host' => $host,
                    'key' => $key,
                    'keyLocation' => rtrim(config('app.url'), '/') . "/{$key}.txt",
                    'urlList' => $chunk,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('IndexNow submit failed: ' . $e->getMessage());
            return 0;
        }

        return count($urls);
    }
}
