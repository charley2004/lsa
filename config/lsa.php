<?php

return [
    // Where contact form notifications are sent. Defaults to the public
    // contact address already shown across the site.
    'admin_email' => env('LSA_ADMIN_EMAIL', 'info@legitsourceanalysis.com'),

    // Public Telegram link used in the header, menu, footer, page CTAs and the
    // Organization schema's sameAs. This default IS the live LSA channel, so
    // production works without setting anything. (@LEGITSOURCE_A is a personal
    // contact account, not the channel, so don't swap it in here.)
    // Kickoff times are STORED in UTC (app.timezone, and what the algorithm
    // sync sends) but SHOWN to visitors in this zone, labelled (e.g. "WAT").
    // "Today"/"Tomorrow" and the daily combo bundles also follow this zone's
    // calendar day, so a 23:30 UTC kickoff counts as the next day in Lagos.
    'display_timezone' => env('LSA_DISPLAY_TIMEZONE', 'Africa/Lagos'),

    'telegram_url' => env('LSA_TELEGRAM_URL', 'https://t.me/+8Khvrbv71dZkNTc0'),
    // Full-page cache for public pages (App\Http\Middleware\CachePublicPage).
    // Uses the file store so a cache hit never touches the database.
    'page_cache' => env('LSA_PAGE_CACHE', true),
    'page_cache_store' => env('LSA_PAGE_CACHE_STORE', 'file'),

    // IndexNow: notifies Bing/DuckDuckGo/Yandex of new or changed fixture pages
    // on every sync (App\Services\IndexNow). The key is public by design: it is
    // served at /{key}.txt to prove site ownership. Production only by default.
    'indexnow_key' => env('INDEXNOW_KEY', 'd42dca6608ab67b3c36f3eab3271b2ba'),
    'indexnow_enabled' => env('INDEXNOW_ENABLED', env('APP_ENV') === 'production'),

    // The current algorithm model (engine v2). Its picks carry model_version "v2-...";
    // /results, the homepage and About headline its record from `since`, and the
    // previous model's record is archived. retest_line is the one-line hindsight
    // re-test summary (algorithm/ CLAUDE.md decision log, 2026-11-21): it is NOT
    // part of the record and must stay labelled as a re-test.
    'current_model' => [
        'version_prefix' => 'v2',
        'since' => '21 Nov 2026',
        'retest_line' => 'Re-tested on past matches, our current model won about 3 in 4 of its picks. That is a re-test in hindsight, not picks we published, so it does not count towards this record.',
        'leagues' => 38,
    ],
];
