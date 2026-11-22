# LSA SEO, phase 2: from invisible to top 3

Checked 22 Nov 2026 against the live site. This builds on `SEO_STRATEGY.md` (16 Nov) and `docs/seo-phase-1-handover.md`.

## Where LSA stands today

| Check | Live result | Why it matters |
|---|---|---|
| Google, brand search "legitsourceanalysis.com" | **No results for the site** | Google has barely indexed it. Nothing can rank until pages are indexed. |
| `/sitemap.xml` | **8 URLs, no match pages**; newest `lastmod` 20 Nov | The same code on this PC lists 46 match pages. Production is serving an old or cached version. Google isn't told the match pages exist. |
| Match pages (`/analysis/{home}-vs-{away}-{date}`) | Live, `index, follow`, about 600 words, xG and form data | Good. These are the pages that can rank. |
| Server response time | **4 to 23 seconds** before the first byte | Google crawls fewer pages on slow sites, and visitors leave. Target: under 0.8 s. |
| Fresh data | Pipeline runs on the home PC every 3 h | When the PC sleeps the site stops updating, and Google sees stale pages. |
| Search Console | Unknown, needs the owner | Required to see what Google indexes and which queries you appear for. |

**Bottom line:** the pages are built well, but Google can barely see them and the server is too slow. Fixing those two things comes before any content work.

## What can realistically rank 1 to 3, and when

The head terms ("football predictions today", "sure win predictions") belong to 17-year-old domains like Forebet and PredictZ with millions of visits. LSA will not take those in months. These are winnable:

| Query type | Example | Competition | Realistic timeline |
|---|---|---|---|
| Lower-league fixture predictions | "sparta rotterdam vs az alkmaar prediction" | Sports Mole and Sportskeeda cover big leagues only | 4 to 10 weeks after indexing |
| Corners and cards markets | "corners prediction today", "[team] vs [team] corners" | Thin, few sites do this properly | 6 to 12 weeks |
| Record and trust queries | "football prediction site with results", "legit football predictions" | Tipster sites don't publish full records | 2 to 4 months, needs links |
| Daily hubs | "btts predictions today", "over 2.5 predictions today" | Strong, but beatable with fresh daily pages | 3 to 6 months |
| Head terms | "football predictions today" | Forebet, PredictZ | 9+ months, only after the above |

SEO brings no money this month. It's December onward, and it compounds.

## The plan, in order

### Step 1. Make Google able to see the site (this week)

Owner (server and accounts):
1. On the server, find out why the sitemap is stale:
   - Check that no static `public/sitemap.xml` file exists. If it does, nginx serves it before Laravel. Delete it.
   - Run `php artisan optimize:clear && php artisan optimize`, then restart PHP-FPM (or "Restart PHP" in DirectAdmin) to clear OPcache.
   - Reload `/sitemap.xml`. It should list about 45+ `/analysis/...` URLs with today's date.
2. Google Search Console: verify the domain (DNS), submit the sitemap, and request indexing for `/analysis`, `/results` and 3 match pages.
3. Bing Webmaster Tools: import from Search Console.

Claude (code):
4. Split the sitemap into a sitemap index (`/sitemap-pages.xml`, `/sitemap-fixtures-YYYY-MM.xml`) so it scales and Google re-crawls today's file.
5. Add a sitemap ping to the pipeline: after every sync, submit IndexNow (already there) and ping Google with the sitemap URL.

### Step 2. Make it fast (this week)

Claude (code), owner deploys:
1. Full-page cache for public pages: cache the rendered HTML of `/`, `/analysis`, `/results` and each match page for 10 minutes, cleared on every pipeline sync. This turns a 4 to 20 s response into about 0.1 s for nearly every visit.
2. Switch sessions and cache from the SQLite database to files for guests, so a page view doesn't write to the database.
3. Self-host fonts and add long cache headers for CSS and JS.

Owner: confirm OPcache is on, `APP_DEBUG=false`.
Target: first byte under 0.8 s, mobile PageSpeed 85+.

### Step 3. Keep it fresh without your PC (next week)

Move the 3-hourly pipeline off the home PC to the server (cron) or a cheap always-on runner. Fresh "today" pages every few hours are a ranking signal in this niche, and right now they stop whenever the PC sleeps.

### Step 4. Pages that match how people search (weeks 2 to 4)

Built automatically from data LSA already has:
- **Market hubs:** `/corners-predictions-today`, `/cards-predictions-today`, `/btts-predictions-today`, `/over-2-5-predictions-today`, with matching `-tomorrow` versions.
- **League hubs:** `/leagues/eredivisie`, `/leagues/premier-league`, and so on. Each lists upcoming fixtures, the model's season record in that league, and links to match pages.
- **Team pages:** `/teams/ajax` with form, xG trend, upcoming fixtures and past LSA calls.
- **Match page upgrades:** add a 120 to 200 word written preview generated from the numbers (form, xG gap, H2H, which market the model likes and why), a "how this call did" box after the match, and FAQ schema ("Who will win Ajax vs Den Haag?", "Will both teams score?").
- **Schema:** `SportsEvent` with both `SportsTeam`s, kickoff in WAT, league, and `BreadcrumbList` on every match page.
- **Internal links:** each match page links to its league hub, both team pages and the market hubs. Each hub links to today's matches.

### Step 5. Earn trust and links (ongoing)

- **The public results ledger is the link asset.** Publish a monthly "LSA model report: X picks, Y% hit rate, ROI by market" page. Forums, Reddit and Telegram channels link to honest records.
- Turn the Telegram channel (about 6.5k) into links: post the match page link with every pick, not just the pick.
- Run 5 to 10 guest posts or data features on football blogs ("Which Eredivisie teams beat their xG most?") built from LSA data.
- Keep the honest positioning. Don't target "sure win", "fixed match" or "booking code" queries; they attract the wrong audience and risk a manual penalty.

### Step 6. Measure every week

From Search Console: indexed pages, impressions, clicks and average position for queries containing "vs", "prediction", "corners", "cards", "today". Double down on whatever starts getting impressions.

## Turning this into a product you can sell

Once LSA has 6 to 8 weeks of Search Console data showing growth, package the method as **"Programmatic SEO for data-driven sites"**:
- What it is: the sitemap and IndexNow pipeline, full-page cache, the hub → league → team → match page structure, schema templates, and the generated-preview approach.
- Who buys it: sports, betting-tips, real-estate listing and job-board sites that have data but no search traffic.
- How to sell it: as a Prime-Axis service (setup from ₦500k or $1,500), and later as a playbook ($49 to $99) with the LSA Search Console charts as proof.

Sell it only with real before-and-after numbers. Without them it's another SEO course.
