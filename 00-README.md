# Real Estate Property Platform — Complete Build Plan

> Placeholder name used throughout this doc set: **"EstateConnect"** — rename freely.

## What this doc set is

A complete, assignable build plan for a property-listing marketplace (React Native app +
Laravel API backend + web admin panel + SEO website) with a free-first-listing,
video/coupon-unlock, scratch-and-win monetization model — inspired by 99acres, NoBroker,
and Housing.com feature sets.

**Note on "copying" competitors:** features (search filters, EMI calculators, chat, contact
masking, etc.) aren't copyrightable — building equivalent functionality is normal
competitive practice. This plan intentionally uses original UI, original copy, and an
original design language rather than cloning their actual screens, logos, or brand assets,
which avoids IP issues while matching functionality.

## Assumptions (change these if wrong)

| Assumption | Why | Where to change |
|---|---|---|
| Primary market: India | Drives payment gateway (Razorpay), SMS OTP (MSG91/Twilio), compliance (DPDP Act, RERA) | 02, 05 |
| Mobile: React Native (Expo bare/dev-client workflow) | Needed for AdMob, native maps, biometric, Skia | 02, 08 |
| Backend: Laravel 11 / PHP 8.3 | As requested | 02, 07 |
| A companion **website** exists (Next.js) alongside the app | 99acres/Housing/NoBroker's traffic is majority organic search; an RN-only app is not indexable by Google | 02 |
| Admin panel: separate React web app (not Laravel Blade) | You asked for "eye catching animations in admin panel" — full animation control needs a JS SPA, not server-rendered Blade | 02, 09 |
| Team: ~7 people (2 backend, 2 mobile, 1 web/admin, 1 designer, 1 QA) | Used only for timeline estimates in doc 11 | 11 |
| Payments: Razorpay primary | India-first | 02, 05, 06 |

## File index

| File | Contents |
|---|---|
| `01-project-overview.md` | Vision, user roles, competitor feature comparison, monetization summary |
| `02-tech-stack-architecture.md` | Full stack choices, system architecture, folder structures |
| `03-database-schema.md` | Tables, key columns, relationships, indexing notes |
| `04-api-specification.md` | REST endpoint list by module, response format, rate limits |
| `05-security-compliance.md` | Full security checklist + legal/compliance flags |
| `06-monetization-engine.md` | Free-listing → video/coupon → scratch-card wallet logic, anti-abuse rules |
| `07-backend-tasks-laravel.md` | Sprint-by-sprint backend task checklist |
| `08-frontend-tasks-react-native.md` | Sprint-by-sprint mobile app task checklist |
| `09-admin-panel-tasks.md` | Sprint-by-sprint admin panel task checklist |
| `10-ui-ux-animation-guide.md` | Design system, color/type direction, specific animated components |
| `11-timeline-milestones.md` | Phased timeline, team allocation, MVP scope cut line |

## How to use this

1. Read `01` and `06` first — they define *what* you're building and the core mechanic that makes this different from a plain listings site.
2. Hand `07`/`08`/`09` to the respective dev leads as sprint backlogs — each checkbox is roughly one ticket.
3. Treat `05-security-compliance.md` as non-negotiable, not a "phase 7 cleanup" — several items (server-side video-watch verification, webhook signature checks) must be built *into* the features from day one, not bolted on after.
4. Use `11` to decide your actual MVP cut — the full feature set below is the "99acres-grade" version; most teams should ship a smaller slice first (marked in doc 11).
