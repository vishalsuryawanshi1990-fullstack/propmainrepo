# 02 — Tech Stack & Architecture

## High-level architecture

```
                        ┌───────────────────────┐
                        │   CDN (CloudFront/     │
                        │   Cloudflare)          │
                        └──────────┬────────────┘
                                   │
   ┌───────────────┐   ┌──────────┴──────────┐   ┌───────────────────┐
   │ React Native   │   │  Next.js Website    │   │  Admin Panel (SPA) │
   │ (iOS/Android)  │   │  (SEO, public site)  │   │  React + Vite       │
   └───────┬────────┘   └──────────┬──────────┘   └─────────┬──────────┘
           │                       │                        │
           └───────────────┬───────┴────────────────────────┘
                            │  HTTPS / REST + WebSocket
                   ┌────────┴─────────┐
                   │  Nginx + WAF     │  (Cloudflare WAF/rate-limit in front)
                   └────────┬─────────┘
                   ┌────────┴─────────┐
                   │  Laravel API     │  (stateless, horizontally scaled)
                   │  (Sanctum auth)  │
                   └───┬───────┬──────┘
        ┌──────────────┘       └───────────────┐
┌───────┴────────┐                     ┌────────┴────────┐
│ MySQL 8 (RDS)   │                     │ Redis (cache +   │
│ primary + read  │                     │ queues + rate    │
│ replica         │                     │ limiting)        │
└─────────────────┘                     └─────────┬────────┘
                                                   │
                                        ┌──────────┴──────────┐
                                        │ Queue workers        │
                                        │ (Laravel Horizon)     │
                                        └──────────┬──────────┘
                                                   │
                    ┌──────────────┬───────────────┼───────────────┬───────────────┐
             ┌──────┴─────┐ ┌──────┴──────┐ ┌──────┴──────┐ ┌──────┴──────┐ ┌──────┴──────┐
             │ S3 / Spaces │ │ Meilisearch │ │ Razorpay    │ │ FCM (push)   │ │ MSG91/Twilio │
             │ (media+CDN) │ │ (search)    │ │ (payments)  │ │              │ │ (OTP SMS)    │
             └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘
```

Real-time chat/notifications: **Laravel Reverb** (self-hosted, first-party WebSocket
server, Laravel 11+) or Pusher if you'd rather not self-host.

## Backend — Laravel

| Concern | Choice | Notes |
|---|---|---|
| Framework | Laravel 11, PHP 8.3 | |
| API auth | Laravel Sanctum (SPA/mobile tokens) | Not Passport — Sanctum is simpler and sufficient for first-party app + admin |
| Authorization | `spatie/laravel-permission` | Role/permission based (Buyer, Seller, Agent, Moderator, Admin) |
| DB | MySQL 8 (or PostgreSQL if you prefer native geo types) | Use `POINT`/spatial index for lat/long if MySQL |
| Cache/Queue | Redis | Queues via Laravel Horizon for visibility |
| Search | Laravel Scout + Meilisearch (self-hosted, cheaper) or Algolia (managed, pricier but zero-ops) | Needed for filters + full-text + typo tolerance on locality names |
| Realtime | Laravel Reverb (WebSockets) | Chat, live notification badges |
| Media storage | S3-compatible + Laravel filesystem driver | Never store uploads on local disk in production |
| Image processing | `intervention/image` or async via queued job + AWS Lambda/Imgix | Thumbnails, watermarking |
| Payments | Razorpay PHP SDK | Webhook-verified, never trust client-reported payment status |
| PDF (agreements, invoices) | `barryvdh/laravel-dompdf` or `spatie/laravel-pdf` | |
| Testing | Pest or PHPUnit | |
| Monitoring | Laravel Telescope (dev only), Sentry (prod errors), Horizon (queue health) | |
| API docs | Scramble or L5-Swagger (OpenAPI) | Auto-generate from route/FormRequest annotations |

### Suggested Laravel folder structure (modular)

```
app/
  Modules/
    Auth/
    Users/
    Properties/
      Http/Controllers, Requests, Resources
      Models, Services, Repositories
    Monetization/        <- wallet, coupons, scratch cards, video-ad callbacks
    Chat/
    Admin/
    Notifications/
  Support/
    Traits, Enums, ValueObjects
routes/
  api_v1.php
  admin.php
  webhooks.php          <- separate, CSRF-exempt, signature-verified
```

## Mobile — React Native

| Concern | Choice | Notes |
|---|---|---|
| Workflow | Expo (bare/dev-client, not managed Expo Go) | Needed for AdMob, native maps, RN Skia |
| Language | TypeScript | |
| Navigation | React Navigation (native-stack + bottom-tabs) | |
| State/data | Redux Toolkit + RTK Query, or Zustand + React Query | RTK Query is a good fit — handles caching/invalidation for listing data automatically |
| Animations | Reanimated 3 + Moti + Lottie + React Native Skia | Skia needed for the scratch-card canvas effect and confetti |
| Maps | `react-native-maps` + Google Places Autocomplete | |
| Ads (rewarded video) | Google AdMob (`react-native-google-mobile-ads`) | **Server must verify via AdMob SSV (server-side verification) callback — never grant credit purely on client "ad closed" event** |
| Payments | Razorpay React Native SDK | |
| Push | Firebase Cloud Messaging | |
| Secure storage | `react-native-keychain` (not AsyncStorage) for auth tokens | |
| Media picker/upload | `expo-image-picker` + chunked/resumable upload to S3 via pre-signed URLs | |

## Website — Next.js (for SEO)

- Next.js 14 App Router, server-rendered property pages (critical: 99acres/Housing/NoBroker's traffic is majority Google organic search — an app-only product will not rank)
- Shares design tokens (colors/type) with the RN app and admin panel via a small shared "design-tokens" package
- Server-side rendering for property detail + locality pages specifically for SEO; can be a lighter client-rendered SPA for logged-in dashboard areas

## Admin panel — React SPA

- React + Vite + TypeScript + Tailwind CSS
- Framer Motion for page transitions/micro-interactions (see doc 10)
- Recharts or ApexCharts for analytics dashboards
- Same Sanctum token auth against the same Laravel API, scoped to admin/moderator roles

## Infra / DevOps

- Docker Compose for local dev (php-fpm, nginx, mysql, redis, meilisearch, mailhog)
- CI/CD: GitHub Actions → build/test → deploy (Laravel Forge, or ECS/Fargate if you want container-native)
- Staging environment mirrors production config exactly (esp. for payment/webhook testing with Razorpay test mode)
- Automated nightly DB backups + S3 versioning on media bucket
- Cloudflare in front of both API and website for WAF + DDoS protection + rate limiting at the edge
