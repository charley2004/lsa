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
];
