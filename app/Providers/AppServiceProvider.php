<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            // Without this, url()/route()/asset() — and therefore the
            // canonical tag, OG/Twitter tags, JSON-LD, and the sitemap —
            // follow whatever Host header a request arrived on, not the
            // one true domain. That's a real SEO problem: the canonical
            // URL must never depend on how the request reached the server
            // (a bare IP, a misconfigured DNS entry, a load-balancer health
            // check). Forcing it here makes every generated URL always
            // resolve to the real domain, regardless of how it was reached.
            // Scoped to production only — local dev still wants links that
            // follow 127.0.0.1:<port> so clicking through the site works.
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }
    }
}
