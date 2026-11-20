# What runs by itself (and the one-time setup for each)

| Job | Runs where | How often | Setup |
|---|---|---|---|
| Scan fixtures, sync to the site, settle results | Your PC (Task Scheduler "LSA Pipeline") | every 3 h | done |
| Refresh the results database (form/xG data) | Your PC, pipeline step 0 | once a day | done |
| Deploy new code to the website | Server cron (`deploy.sh`) | every 5 min (only acts when GitHub has new commits) | **one-time, below** |
| Database backup before every migration | Server, inside `deploy.sh` | each deploy, keeps last 10 | none |
| Tell search engines about new/changed match pages | Website, on every sync (IndexNow) | every sync | none |
| Google discovery | Sitemap with per-page `lastmod` | continuous | submit the sitemap once in Search Console |

## One-time: server auto-deploy

1. In the HostAfrica terminal, find the app folder:
   ```bash
   cd ~/domains/legitsourceanalysis.com/public_html && cd "$(git rev-parse --show-toplevel)" && pwd
   ```
   Note the path it prints (called APP_DIR below), and `git pull origin main` once so `deploy.sh` exists.
2. DirectAdmin → **Advanced Features → Cron Jobs** → add:
   - Minute `*/5`, Hour `*`, Day `*`, Month `*`, Weekday `*`
   - Command: `/bin/bash APP_DIR/deploy.sh`
3. After the next push, check `APP_DIR/storage/logs/deploy.log`. It should end with `deployed ... OK`.

After this, a `git push` is the whole deploy. No SSH, no pull, no migrate.
If a deploy ever fails, the log says why and the site keeps running the previous code.
Restore a backup by copying `storage/backups/db-<date>.sqlite` over `database/database.sqlite`.

## Search engines, without manual "Request indexing"
- **Google:** submit `https://legitsourceanalysis.com/sitemap.xml` once in Search Console. Google re-reads it on its own; each match page's `lastmod` changes when it updates. Manual "Request indexing" is optional, not required.
- **Bing / DuckDuckGo / Yahoo / Yandex:** in Bing Webmaster Tools, "Import from Google Search Console" once. After that, every sync pushes new/changed match pages instantly through IndexNow (ownership key served at `/<key>.txt`).

## PC pipeline reliability (already applied)
The "LSA Pipeline" task now runs on battery and keeps running if the charger is unplugged (before, it was skipped or killed), wakes the PC from sleep, waits for a network connection, and retries up to 3 times, 10 minutes apart, when a step fails.
One Windows setting is yours to check: **Control Panel → Power Options → Change plan settings → Change advanced power settings → Sleep → Allow wake timers → Enable** (both "On battery" and "Plugged in").
