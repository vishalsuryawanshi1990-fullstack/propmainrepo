# Deployment Runbook

Sprint 8 deliverable per `07-backend-tasks-laravel.md`. This is written
against what actually exists in this repo — commands, file paths, and
config keys are real, not generic placeholders.

## Processes a production deploy needs running

| Process | Command | Why |
|---|---|---|
| App server | nginx + php-fpm (see `docker/`) | serves the API |
| Queue worker | `php artisan horizon` | monetization jobs (unlock spend ledger writes are sync, but `DetectDuplicateListingJob`/`SanitizeUploadedImageJob` are queued) |
| WebSocket server | `php artisan reverb:start` | chat (`private-chat.{id}`) |
| Scheduler | `php artisan schedule:work` (or cron + `schedule:run`) | `horizon:snapshot`, `telescope:prune`, `sanctum:prune-expired` — see `routes/console.php` |

Supervisor configs for the three background processes are in
`docker/supervisor/`. If your platform doesn't use supervisor (e.g. it
runs one process per container — ECS/Fargate, Cloud Run), run each
command in its own service/task instead of copying the `.conf` files.

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
- `SCOUT_DRIVER=meilisearch` + `MEILISEARCH_HOST`/`KEY` — falls back to
  the `database` driver otherwise (works, but loses typo-tolerance and
  can't scale past a small catalog).

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
4. Watch `payments` for rows stuck in `status = created` for more than
   a few minutes after go-live — that's a sign webhooks aren't reaching
   you (firewall/URL misconfiguration), not that nobody's buying.

## AdMob: sandbox → live cutover

1. Replace any test ad unit IDs (client-side, in the mobile app) with
   real ones from the AdMob console.
2. Confirm `ADMOB_VERIFIER_KEYS_URL` is reachable from the production
   servers (it's a public Google endpoint — this only fails on
   restrictive egress firewalls).
3. Watch `video_ad_events` after go-live: `ssv_verified = true` rows
   confirm the real signature-verification path is working end-to-end
   (doc11's milestone M2) — a spike in unverified/ignored callbacks
   means either the key cache is stale or something's tampering with
   the callback in transit.

## CI/CD (GitHub Actions)

Three CI workflows (`.github/workflows/{backend,admin,website}-ci.yml`)
run on every push/PR touching their respective app, path-filtered so a
website-only change doesn't re-run the backend test suite. Each also
has a `workflow_dispatch` trigger for a manual re-run.

`backend-deploy.yml` deploys to a VPS over SSH, chained via
`workflow_run` so it only fires **after** Backend CI has passed on
`main` — not as a parallel race against it. It needs these repo
secrets (Settings → Secrets and variables → Actions), none of which
exist until you add them:

| Secret | What it is |
|---|---|
| `VPS_HOST` | Server IP or hostname |
| `VPS_USERNAME` | SSH user (needs write access to `VPS_DEPLOY_PATH` and permission to run the artisan commands / restart supervisor-managed processes) |
| `VPS_SSH_KEY` | Private key matching a public key already in that user's `~/.ssh/authorized_keys` |
| `VPS_DEPLOY_PATH` | Absolute path to the existing git checkout on the server (e.g. `/var/www/estateconnect/backend`) |
| `VPS_PORT` | Optional, defaults to 22 |
| `MAINTENANCE_BYPASS_SECRET` | Optional — lets you hit `/?<secret>` to bypass maintenance mode while `php artisan down` is active mid-deploy |

**Before the first automated deploy can work**, the server needs the
one-time setup this doc already describes: clone the repo to
`VPS_DEPLOY_PATH`, run through "First deploy" below once by hand, and
have supervisor already running Horizon/Reverb/the scheduler
(`docker/supervisor/*.conf` or your platform's equivalent) — the
deploy script only pulls new code and restarts what's already running,
it doesn't provision a server from scratch.

Admin panel and website are deployed separately via Vercel's native
Git integration (one Vercel project per app, each with its "Root
Directory" set to `admin` or `website`) rather than a custom Actions
workflow — Vercel builds and deploys on every push to `main`
automatically, with zero YAML needed on this side.

## First deploy

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env   # then fill in every value for real
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class="Database\Seeders\RolesAndPermissionsSeeder" --force
php artisan config:cache
php artisan route:cache
php artisan storage:link
```

Then start the four processes above (app server, Horizon, Reverb,
scheduler).

## Subsequent deploys (zero-ish downtime)

```bash
php artisan down --secret="<a-throwaway-token>"   # bypass URL: /?<token>
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan horizon:terminate   # supervisor restarts it, picking up new code
php artisan reverb:restart
php artisan up
```

`horizon:terminate` (not a hard kill) lets in-flight jobs finish before
the worker restarts — important here specifically because
`SanitizeUploadedImageJob`/`DetectDuplicateListingJob` shouldn't be cut
mid-write.

## Rollback

1. `php artisan down`
2. Redeploy the previous release (previous Docker image tag / previous
   git commit, per whatever your deploy tool uses).
3. `php artisan migrate:rollback` **only if** the bad deploy's
   migrations are safe to reverse — check the migration's `down()`
   first. Every migration in this repo has a real (non-empty) `down()`,
   but rolling back a column that's since had data written into it
   (e.g. `properties.city_name`/`locality_name`) will lose that data,
   not just the schema change.
4. `php artisan config:clear && php artisan route:clear` if the
   previous release predates the `config:cache`/`route:cache` calls
   above, then re-run `config:cache`/`route:cache` for the rolled-back
   code.
5. `php artisan up`

## Monitoring — what's wired vs. what still needs a real account

Wired in code, works today:

- `GET /up` — Laravel's built-in health check (used by `down`'s
  companion `up`, and whatever your load balancer/uptime checker hits).
- Horizon dashboard (`/horizon`) — job throughput, failed jobs, queue
  depth. Gate is role-based (`hasRole('admin')`) in
  `HorizonServiceProvider`, but the dashboard route itself authenticates
  via the **session** ("web") guard, not Sanctum — reaching it in
  production needs either a session-based admin login or (more common
  in practice) Basic Auth / an IP allowlist in front of `/horizon` at
  the nginx/Cloudflare layer. Neither of those exists in this repo yet.
- `audit_logs` — every admin mutation, queryable via
  `GET /api/v1/admin/audit-logs`.

Explicitly not set up here — each needs a real external account this
sandboxed environment doesn't have, and faking the integration would be
worse than not having it:

- **Sentry** (error tracking) — `composer require sentry/sentry-laravel`
  + a DSN from a real Sentry project.
- **Automated nightly DB backups** — `spatie/laravel-backup` + a real
  S3 bucket/credentials to back up to.
- **Cloudflare/WAF** — DNS-level, not application code.
- **Uptime checks** — point any external uptime monitor (UptimeRobot,
  Better Uptime, etc.) at `GET /up`.

Doc11's M5 ("Security checklist fully signed off") should treat the
three items above as still-open, not done, until they're real.
