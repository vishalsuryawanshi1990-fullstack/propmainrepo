# 07 — Backend Tasks (Laravel API) — Sprint Checklist

Assume 2-week sprints, 2 backend devs. Each checkbox ≈ one ticket.

## Sprint 0 — Foundations
- [ ] Repo setup, branching strategy, GitHub Actions CI (lint + test on PR)
- [ ] Docker Compose (php-fpm, nginx, mysql, redis, meilisearch, mailhog)
- [ ] Install: Sanctum, spatie/laravel-permission, Horizon, Telescope (dev), Scout+Meilisearch driver
- [ ] Base API response macros (success/error envelope), global exception handler formatting
- [ ] Roles & permissions seed (Buyer, Seller, Agent, Moderator, Admin)
- [ ] Environment configs for staging/production, secrets management plan

## Sprint 1 — Auth & Users
- [ ] OTP request/verify endpoints (MSG91/Twilio integration), strict rate limiting
- [ ] Registration/profile completion flow
- [ ] `users`, `seller_profiles`, `agent_profiles` migrations + models
- [ ] KYC document upload endpoint + admin verification workflow (status machine: pending/verified/rejected)
- [ ] Sanctum token issuance/revocation, logout, refresh
- [ ] Public agent/seller profile endpoint

## Sprint 2 — Property Catalog
- [ ] Migrations: properties, property_images, property_videos, amenities, property_types, cities, localities
- [ ] Property CRUD (Form Requests with full validation), status lifecycle (draft → pending_review → live)
- [ ] Image/video upload via pre-signed S3 URLs (avoid proxying large files through API server)
- [ ] Search/filter endpoint (city, locality, price range, bedrooms, listing_type) — start with DB query, wire Scout/Meilisearch in Sprint 6
- [ ] Geo radius search (spatial index or haversine query)
- [ ] Favorites, similar-properties, featured-listings endpoints
- [ ] Duplicate-listing detection job (address+price+image-hash matching) queued on listing creation

## Sprint 3 — Monetization Engine (core differentiator — see doc 06)
- [ ] Wallet model + `wallet_transactions` ledger (never mutate balance directly — always via transaction rows)
- [ ] `contact_unlocks` check/spend endpoints with unique-constraint guard against double charging
- [ ] Coupon CRUD + purchase flow, Razorpay order creation
- [ ] Razorpay webhook endpoint — signature verification, idempotent crediting
- [ ] AdMob SSV callback endpoint — signature verification against Google's public key, token matching, idempotent crediting
- [ ] Scratch card creation (post-unlock trigger) + scratch endpoint with weighted-random reward selection at scratch-time
- [ ] Admin-configurable reward odds table + validation (weights must be sane)
- [ ] Anti-abuse: daily video cap, cooldown, device/IP signup throttling

## Sprint 4 — Chat & Notifications
- [ ] Laravel Reverb setup, private channel auth per chat
- [ ] Chat/message endpoints + read receipts
- [ ] Phone-number masking logic (don't leak real numbers into chat unless explicitly unlocked)
- [ ] FCM push integration (new message, listing approved, KYC result, scratch reward ready)
- [ ] SMS/email transactional triggers (OTP, payment receipt, listing status change)

## Sprint 5 — Admin APIs
- [ ] Listing moderation queue endpoints (approve/reject with reason)
- [ ] KYC review queue endpoints
- [ ] Reported-listings management
- [ ] Coupon & scratch-reward config CRUD
- [ ] Analytics endpoints: signups over time, listings by status, revenue by source (video vs coupon), unlock funnel conversion
- [ ] CMS endpoints (banners, blogs, FAQs) for the website
- [ ] Audit log write-through on all admin mutations

## Sprint 6 — Search & Performance
- [ ] Wire Meilisearch via Scout for property search (typo tolerance, ranking rules by recency/relevance/featured)
- [ ] Add composite/spatial indexes per doc 03
- [ ] Query performance pass on hot endpoints (search, property detail, wallet)
- [ ] Redis caching for locality insights / featured listings (short TTL, invalidate on write)

## Sprint 7 — Hardening & Testing
- [ ] Full security checklist pass from doc 05
- [ ] Pest/PHPUnit coverage on monetization engine specifically (highest financial risk surface)
- [ ] Load test search + unlock endpoints (k6 or Locust)
- [ ] Dependency audit, fix flagged packages
- [ ] Penetration-test pass or at minimum OWASP ZAP automated scan

## Sprint 8 — Deployment
- [ ] Staging deploy, full Razorpay/AdMob sandbox-to-live cutover checklist
- [ ] Production deploy runbook, rollback plan
- [ ] Monitoring dashboards (Horizon, Sentry, uptime checks) live before go-live, not after
