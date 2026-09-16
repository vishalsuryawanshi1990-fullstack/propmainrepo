# Deployment Runbook

Sprint 8 deliverable per `07-backend-tasks-laravel.md`. This is written
against what actually exists in this repo — commands, file paths, and
config keys are real, not generic placeholders.

**Actual target: shared hosting (SSH + cron, no root, no persistent
daemons).** That changes several defaults from a "normal" Laravel
deploy — read this whole doc before touching `.env`, not just the
first-deploy command block.

## Processes, and what shared hosting actually runs

| Process | On a VPS | On shared hosting (this repo's actual setup) |
|---|---|---|
| App server | nginx + php-fpm (see `docker/`) | Whatever the host already runs (Apache/LiteSpeed via cPanel, usually) — nothing to configure here |
| Queue worker | `php artisan horizon` (persistent, Redis-backed) | No persistent worker exists. `routes/console.php` schedules `queue:work --stop-when-empty --max-time=50` every minute instead — jobs (`DetectDuplicateListingJob`, `SanitizeUploadedImageJob`, queued notification mail) run in short bursts, not instantly, but never block the HTTP response either |
| Scheduler | `php artisan schedule:work` under supervisor | A single cron entry (below) calling `schedule:run` every minute — this is what actually drives the queue-worker line above, so it's not optional |
| Realtime chat | `php artisan reverb:start` (a persistent WebSocket listener) | Not possible on shared hosting at all — no persistent port to bind. Set `BROADCAST_CONNECTION=pusher` instead (doc02 named Pusher as exactly this fallback); clients connect to Pusher's servers, not yours |
| Search | Meilisearch (persistent server) | Not possible either. `SCOUT_DRIVER=database` (works, no typo-tolerance) unless you point at a hosted Meilisearch Cloud/Algolia instance |
| Cache/queue/session store | Redis | Already `database` by default in `.env.example` — no change needed, Redis was never a hard requirement here |

`docker/supervisor/*.conf` and the VPS-flavored deploy steps mentioned
in earlier drafts of this doc are kept in git history for if/when this
moves to a VPS — they don't apply to the current target.

## One-time server setup (do this before the first automated deploy)

1. **Cron job** — in cPanel → Cron Jobs (or equivalent), add:
   ```
   * * * * * cd /home/youruser/path-to/backend && php artisan schedule:run >> /dev/null 2>&1
   ```
   Without this, `telescope:prune`, `sanctum:prune-expired`, and —
   critically — the queue worker never run at all. Queued jobs would
   just sit in the `jobs` table forever.
2. **Directory layout** — most shared hosts serve `public_html` (or
   similar) directly and have no concept of "point the web root at this
   app's `public/` folder." The standard workaround: put this repo
   somewhere *outside* the web root (e.g. `~/estateconnect-backend`),
   then either symlink `public_html` → `~/estateconnect-backend/backend/public`,
   or (if your host disallows symlinks) copy `public/`'s contents into
   `public_html` and adjust the two `require` paths in `public_html/index.php`
   to point at the real `../vendor/autoload.php` and
   `../bootstrap/app.php`. Check what your specific host/panel expects
   before the first deploy — this genuinely varies by provider.
3. **Composer/PHP availability over SSH** — confirm `composer` and
   `php -v` (needs 8.4+, see below) both resolve in a non-interactive
   SSH session (`ssh user@host 'php -v'`) before relying on the
   GitHub Actions deploy step to find them.
4. Clone the repo to that path, then run "First deploy" below once by
   hand.

## Environment checklist

Every key in `.env.example` needs a real value in production. The ones
that silently degrade instead of failing loudly if left blank:

- `RAZORPAY_*` — coupon purchase creates orders against Razorpay's live
  API only once `RAZORPAY_KEY_ID`/`SECRET` are live-mode keys; webhook
  signature verification fails closed (rejects) if `RAZORPAY_WEBHOOK_SECRET`
  is wrong, so a misconfigured secret is at least loud, not silent.
- `ADMOB_VERIFIER_KEYS_URL` — has a working default (Google's real
  endpoint), no action needed unless self-hosting a mirror.
- `FIREBASE_CREDENTIALS_PATH` — push notifications silently no-op
  (`FcmPushService::credentials()` returns null) if unset. Fine for a
  soft launch, not fine if you're relying on push for the scratch-card
  "ready" notification.
- `CORS_ALLOWED_ORIGINS` — must list the admin panel's and website's
  real origins, comma-separated, no wildcard (05-security-compliance.md).
- `TELESCOPE_ENABLED=false` — doc02 says dev-only; there's no code-level
  block on running it in production, this env var is the only gate.
- `BROADCAST_CONNECTION=pusher` + `PUSHER_*` — see the table above;
  leaving this as `reverb` on shared hosting means chat silently never
  delivers realtime messages (REST endpoints still work, nothing pushes).
- `SCOUT_DRIVER=database` — leave as `meilisearch` here and search
  requests will error trying to reach a Meilisearch server that doesn't
  exist, unless you've pointed `MEILISEARCH_HOST` at a real hosted one.
- `DB_CONNECTION=mysql` + real `DB_HOST`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD`
  — `.env.example` defaults to `sqlite` for local dev convenience; your
  host's cPanel → MySQL Databases is where the real ones come from.

## Razorpay: sandbox → live cutover

1. Confirm `RAZORPAY_WEBHOOK_SECRET` matches what's configured in the
   Razorpay Dashboard for the **live** webhook endpoint
   (`POST /api/webhooks/razorpay`) — sandbox and live webhooks have
   separate secrets.
2. Switch `RAZORPAY_KEY_ID` / `RAZORPAY_KEY_SECRET` from test-mode
   (`rzp_test_...`) to live-mode (`rzp_live_...`) keys.
3. Run one real ₹1-coupon purchase end-to-end in production before
   announcing launch: `POST /coupons/purchase` → complete checkout →
   confirm the webhook fires, `payments.status` flips to `paid`, wallet
   credits, and a scratch card spawns. This is doc11's milestone M3.
   Remember the webhook is now processed by the cron-driven queue
   worker if you dispatch it as a job, or synchronously if not — check
   which before assuming "no response yet" means "broken."
4. Watch `payments` for rows stuck in `status = created` for more than
   a few minutes after go-live — that's a sign webhooks aren't reaching
   you (firewall/URL misconfiguration), not that nobody's buying.

## AdMob: sandbox → live cutover

1. Replace any test ad unit IDs (client-side, in the mobile app) with
   real ones from the AdMob console.
2. Confirm `ADMOB_VERIFIER_KEYS_URL` is reachable from the production
   server (it's a public Google endpoint — this only fails on
   restrictive egress firewalls, which some locked-down shared hosts
   do have; test with `curl` over SSH if in doubt).
3. Watch `video_ad_events` after go-live: `ssv_verified = true` rows
   confirm the real signature-verification path is working end-to-end
   (doc11's milestone M2).

## CI/CD (GitHub Actions)

This backend lives in two places: `propmainrepo` (the monorepo, where
development actually happens) and `propbackend` (a standalone repo
produced by `git subtree split --prefix=backend`, kept in sync after
every backend change — see the split-repo note at the top of this
project). **The server's git remote points at `propbackend`, not
`propmainrepo`** — that's the repo whose secrets/workflows actually
matter for deployment.

`propbackend`'s own `.github/workflows/ci.yml` and `deploy.yml` (they
live at `backend/.github/workflows/` in the monorepo, inert there —
only propbackend's *root* `.github/workflows/` is read by GitHub —
and become live once split out) run lint/test, then deploy over SSH
chained via `workflow_run` so it only fires **after** CI has passed on
`main`, not as a parallel race against it. Set these secrets on the
**`propbackend` repo itself** (Settings → Secrets and variables →
Actions on github.com/.../propbackend), not on propmainrepo:

| Secret | What it is |
|---|---|
| `VPS_HOST` | Your shared host's SSH hostname/IP |
| `VPS_USERNAME` | Your SSH username |
| `VPS_PASSWORD` | Your SSH password (most shared hosts don't offer easy key-based SSH access — password auth over SSH is fine here) |
| `VPS_DEPLOY_PATH` | Absolute path to the existing git checkout, e.g. `/home/<user>/domains/api.<domain>/laravel` (step 4 of one-time setup above) |
| `VPS_PORT` | Optional, defaults to 22 — some shared hosts use a non-standard SSH port, check yours |
| `MAINTENANCE_BYPASS_SECRET` | Optional — lets you hit `/?<secret>` to bypass maintenance mode while `php artisan down` is active mid-deploy |

The deploy script only pulls new code, runs composer/migrations, and
rebuilds caches — it doesn't provision anything, so the one-time server
setup above has to happen first, by hand, once. It invokes PHP via its
full path (`/opt/alt/php84/usr/bin/php` on this host) rather than the
bare `php`/`composer` commands — CloudLinux's per-account CLI PHP
selector defaults to an older version (8.1) than what the site's
PHP-FPM actually runs (8.4), and this app's dependencies require 8.4+.

**Do not connect this repo to Hostinger's own Git auto-deploy feature**
(Websites → your site → Advanced → GIT). That tool always deploys flat
into `public_html`, which on this setup is a *symlink* to
`laravel/public` (see "Directory layout" above) — it follows the
symlink and dumps the entire repo into it, corrupting the real public
folder. This exact thing happened once already; the fix was moving the
misplaced `public/public/` back out (see git history/session notes)
and disconnecting the Git integration for this site for good. The
GitHub Actions workflow above is the only deploy path for this app.

Admin panel and website are deployed separately via each platform's
own native Git integration (Hostinger's auto-deploy for the admin
panel — a plain static Vite build, which that tool handles correctly;
Vercel for the website, since it needs a real Node SSR runtime) rather
than a custom Actions workflow.

## First deploy

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env   # then fill in every value for real — see the checklist above
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class="Database\Seeders\RolesAndPermissionsSeeder" --force
php artisan config:cache
php artisan route:cache
php artisan storage:link
```

Then add the cron entry from "One-time server setup" if you haven't
already — nothing scheduled or queued runs without it.

## Subsequent deploys

```bash
php artisan down --secret="<a-throwaway-token>"   # bypass URL: /?<token>
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan up
```

No worker/websocket process to restart here (there isn't one) — the
next cron tick just picks up the new code automatically.

## Rollback

1. `php artisan down`
2. Redeploy the previous release (previous git commit — `git reset
   --hard <previous-sha>` on the server, same as the deploy script but
   backwards).
3. `php artisan migrate:rollback` **only if** the bad deploy's
   migrations are safe to reverse — check the migration's `down()`
   first. Every migration in this repo has a real (non-empty) `down()`,
   but rolling back a column that's since had data written into it
   (e.g. `properties.city_name`/`locality_name`) will lose that data,
   not just the schema change.
4. `php artisan config:clear && php artisan route:clear`, then
   re-run `config:cache`/`route:cache` for the rolled-back code.
5. `php artisan up`

## Monitoring — what's wired vs. what still needs a real account

Wired in code, works today:

- `GET /up` — Laravel's built-in health check (used by `down`'s
  companion `up`, and whatever external uptime monitor you point at it).
- `audit_logs` — every admin mutation, queryable via
  `GET /api/v1/admin/audit-logs`.
- The Horizon/Telescope dashboards are installed but not meaningful
  here without a persistent worker/on shared hosting's typical
  restrictions — treat them as local-dev-only tools for this
  deployment target, not production monitoring.

Explicitly not set up here — each needs a real external account this
sandboxed environment doesn't have, and faking the integration would be
worse than not having it:

- **Sentry** (error tracking) — `composer require sentry/sentry-laravel`
  + a DSN from a real Sentry project.
- **Automated nightly DB backups** — many shared hosts include this in
  cPanel already (check "Backup" in your panel first); otherwise
  `spatie/laravel-backup` + real S3 credentials.
- **Uptime checks** — point any external uptime monitor (UptimeRobot,
  Better Uptime, etc.) at `GET /up`.

Doc11's M5 ("Security checklist fully signed off") should treat the
items above as still-open, not done, until they're real.
