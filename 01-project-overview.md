# 01 — Project Overview

## Vision

A property marketplace where genuine buyers and sellers/owners connect directly, with:
- Verified users (KYC-lite) to filter out fake/broker spam listings — the core complaint users have about existing portals
- A free entry point (first listing/unlock free) to drive signups
- A gamified monetization layer (watch-video-or-buy-coupon → scratch card) instead of a flat paywall, to keep engagement high while still monetizing lead access

## User roles

| Role | Capabilities |
|---|---|
| **Guest** | Browse listings, search, view limited property info (no contact details) |
| **Buyer** | Everything Guest can + save favorites, chat, unlock contact details (1 free, then paid/video) |
| **Seller / Owner** | Post properties, manage listings, view leads, unlock buyer contact details (1 free, then paid/video) |
| **Agent / Builder** | Like Seller + agency profile page, multiple project listings, bulk upload |
| **Moderator (admin staff)** | Approve/reject listings, handle reports, KYC review |
| **Super Admin** | Full config: coupon pricing, scratch-reward odds, CMS, analytics, user management |

## Feature set (inspired by 99acres / NoBroker / Housing.com)

| Feature | 99acres | NoBroker | Housing.com | In this plan |
|---|---|---|---|---|
| Verified listings / KYC | ✓ | ✓ (strong) | ✓ | ✓ |
| Owner-direct (no broker) filter | – | ✓ (core USP) | partial | ✓ |
| Contact-unlock paywall | ✓ (credits) | ✓ (subscription) | ✓ | ✓ (unique: video/coupon + scratch card) |
| Map-based search | ✓ | ✓ | ✓ | ✓ |
| EMI / home-loan calculator | ✓ | ✓ | ✓ | ✓ |
| In-app chat with number masking | partial | ✓ | partial | ✓ |
| Locality insights / price trends | ✓ | partial | ✓ | ✓ (phase 2) |
| Virtual tour / 360° / video walkthrough | ✓ | ✓ | ✓ | ✓ |
| Agent/Builder microsite pages | ✓ | – | ✓ | ✓ |
| Property comparison tool | ✓ | – | partial | ✓ |
| Rewarded-video + scratch-card unlock | – | – | – | ✓ **(your differentiator)** |

## Monetization model (summary — full detail in `06-monetization-engine.md`)

1. Every user gets **1 free contact-unlock credit** on signup (or: first listing posted is fully free and visible with no unlock needed — pick one interpretation, both are documented in doc 06, recommend the credit model since it's simpler to reason about and abuse-proof).
2. From the 2nd unlock onward, the user must either:
   - **Watch a rewarded video ad** (30s), or
   - **Buy a coupon** (small payment via Razorpay)
3. Completing either action triggers a **scratch card** — a randomized reward (bonus credit, wallet cashback, discount voucher) with admin-configurable odds.
4. Credits are spent to reveal the other party's phone/contact details on a listing.

## Non-functional requirements

- **Performance:** property search results < 500ms p95 at 100k listings (requires DB indexing + optional Elasticsearch/Meilisearch — see doc 02)
- **Scalability:** stateless API servers behind a load balancer; horizontal scaling for search/queue workers
- **Availability target:** 99.5%+ for MVP, 99.9% post-scale
- **Data retention:** define retention/deletion policy for KYC documents and chat logs (compliance requirement, see doc 05)
- **Localization-ready:** even if launching English-only, structure translations/i18n from day one — real estate is highly regional

## Compliance flag (read before building the reward mechanic)

The "scratch and win" mechanic is a promotional gamification pattern (same category as
Swiggy/PhonePe/Paytm scratch cards) — not real-money gambling if rewards are wallet
credits/discounts rather than cash withdrawable prizes. Still, prize-based promotions can
be subject to state-level rules in India (e.g., contest/lottery-adjacent regulations vary
by state). **Get this reviewed by a lawyer before launch**, and design reward payouts as
non-cash, non-withdrawable credits/discounts to keep the mechanic squarely in "marketing
gamification" territory rather than "game of chance with a prize." Details in doc 05.
