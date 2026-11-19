# SEO Phase 1 — handover (your side)

Code changes are on branch `seo/analysis-phase-1`. Everything below needs
server, account or content access that only you have. Work top to bottom.

## A. Deploy (required — in this order)

1. Merge / deploy the branch to production.
2. **Back up the production database**, then run:
   ```
   php artisan migrate --force
   ```
   This adds the `slug` column to `predictions` and backfills every existing
   row, so every past fixture gets its permanent page immediately.
3. Clear and rebuild Laravel's caches:
   ```
   php artisan optimize:clear
   php artisan optimize
   ```
4. Check these by hand:
   - `https://legitsourceanalysis.com/sitemap.xml` returns XML (it was HTTP 500).
   - `https://legitsourceanalysis.com/analysis` shows "Football Analysis Today".
   - Click "Full … analysis" on any card: the fixture page opens.
   - On `/results`, match names are now links.

## B. Server (nginx / hosting panel)

5. **Redirects are now in code**: `public/.htaccess` 301-redirects `www.` to the
   main domain, and `http://` to `https://` when the proxy sends
   `X-Forwarded-Proto: http`. After deploying, check that both
   `http://legitsourceanalysis.com/analysis` and
   `https://www.legitsourceanalysis.com/analysis` return 301. If `http://`
   still returns 200, switch on "Force HTTPS" in the hosting panel.
6. **Long cache for static files.** CSS and JS links now carry `?v=<timestamp>`,
   so it's safe to set long expiry on them (hosting panel, or nginx `expires 30d`).
7. **Server response time** (first byte is about 1.6 s today):
   - Make sure PHP **OPcache** is on (`opcache.enable=1`).
   - In production `.env`, consider `SESSION_DRIVER=file` and `CACHE_STORE=file`
     (or `redis`) instead of `database`. With `database`, every page view writes
     a session row to SQLite.
   - Set `APP_DEBUG=false` and `APP_ENV=production`.

## C. Search engines

8. **Google Search Console**: verify `legitsourceanalysis.com` (a Domain
   property via DNS is best), then submit `https://legitsourceanalysis.com/sitemap.xml`.
9. **Bing Webmaster Tools**: import from Search Console and submit the same sitemap.
10. In Search Console, run **URL Inspection → Request indexing** on `/analysis`
    and on two or three fixture pages.
11. Check again after 7 and after 28 days:
    - Pages report: how many `/analysis/...` URLs are indexed, and why any are not.
    - Performance report: queries containing "analysis today", "prediction" and "vs".
    - Use these real queries to replace the estimated keyword priorities.

## D. Content / settings

12. **Telegram link — nothing to do.** The link is already the live channel
    ("LEGITSOURCE ANALYSIS", about 6.5k subscribers) and is the built-in default
    in `config/lsa.php`, so production needs no `.env` change. `@LEGITSOURCE_A`
    is a personal contact account, not the channel. To change the link later,
    set `LSA_TELEGRAM_URL` in the server's `.env` and run `php artisan config:cache`.
13. **Bookmaker offers.** The local `bookmaker_offers` table is empty. Make sure
    the production offers (22Bet, BC Game) are active in `/admin`, so the offers
    block shows on the analysis and fixture pages.

## E. Optional, low priority

- Self-host the Geist fonts (download from Google Fonts or `@fontsource/geist`,
  put them under `public/fonts`, and replace the Google Fonts `<link>`). This
  removes one render-blocking request from a third-party host.
- Run PageSpeed Insights (mobile) on `/analysis` and one fixture page after
  deploying, and keep the numbers as a baseline.

## Timezone (done)

Kickoff times are stored in UTC and now **shown in WAT** with a "WAT" label
everywhere: analysis cards, fixture pages, combos, home, results and the
admin list. "Today"/"Tomorrow" and the daily combo bundles follow the Lagos
calendar day. Override with `LSA_DISPLAY_TIMEZONE` if that's ever needed.
