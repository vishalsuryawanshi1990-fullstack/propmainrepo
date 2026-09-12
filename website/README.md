# EstateConnect Website

Next.js 16 (App Router) SEO site per `02-tech-stack-architecture.md` —
"an RN-only app is not indexable by Google; 99acres/Housing/NoBroker's
traffic is majority organic search." Talks to the same Laravel API as
the mobile app and admin panel.

There's no dedicated sprint doc for the website (unlike doc07/08/09),
so scope here was decided against doc02's stated goal (SEO reach) and
doc11's MVP-cut philosophy applied the same way it was to the other two
apps: build the pages Google actually needs to index, and the minimum
transactional path needed to make them not-a-dead-end, rather than
mirroring the full mobile app.

## Setup

```bash
npm install
cp .env.example .env   # point API_BASE_URL at your backend
npm run dev
```

## Architecture note: the auth token never reaches the browser

Every other client (mobile, admin panel) stores its Sanctum bearer
token client-side (Keychain, localStorage). This site does not — the
token lives only in an `httpOnly` cookie, set by `app/api/auth/otp/verify/route.ts`.
Client Components never see it; a `UnlockContact`/`PostPropertyForm`
etc. click hits a same-origin Route Handler (`app/api/*`), which reads
the cookie server-side and attaches the `Authorization` header to the
real backend call. This is deliberately stricter than the other two
clients because a website's JS runs in the least trusted environment
of the three (arbitrary origins, browser extensions, XSS surface).

## Pages

SSR (the SEO-critical surfaces, per doc02):

- `/` — home, featured listings
- `/properties` — search/browse, filterable via query params (so a
  filtered URL is itself a crawlable, shareable page)
- `/properties/[id]` — property detail, dynamic `<title>`/OG metadata
- `/localities/[id]` — price/sqft insights
- `/blog`, `/blog/[slug]` — CMS-backed content
- `/faq` — CMS-backed FAQ
- `/agents/[id]` — public agent/seller profile

Interactive (client components / Route Handlers):

- `/login` — same OTP flow as every other client, no separate
  password-based login exists anywhere in this system
- `/post-property` — single-page form (not the mobile app's multi-step
  wizard); requires a seller/agent account

## What's simplified vs. the mobile app, and why

- **Unlock flow**: the app's video-ad path is an AdMob SDK concept that
  doesn't exist in a browser. The website's `UnlockContact` only offers
  "spend a credit you already have" — someone out of credits is told to
  top up in the app, rather than re-implementing Razorpay Checkout.js
  here too.
- **Post-property form**: no drop-pin map picker (mobile-only per
  doc08), no photo upload yet, no amenities picker — coordinates are
  typed directly and photos are added after a listing is approved.
- **Chat**: not implemented on the website at all. A lead can already
  be captured via contact unlock; live chat is left to the app.

## Backend gap this surfaced

Building `/post-property`'s city/locality/property-type dropdowns
needed a way to list valid ids to submit — `04-api-specification.md`
never defined one. Added `GET /property-types`, `/cities`,
`/localities`, `/amenities` to the backend (see its own commit) rather
than hardcoding IDs on the frontend.

## Verified, not just built

Unlike the mobile app (skipped — no simulator/device in this
environment), every page here was actually round-tripped against a
running backend during development: OTP login → httpOnly cookie set →
authenticated home page state → property creation → master-data
dropdowns, all over real HTTP, not just "it compiles."

## Scripts

- `npm run dev` — dev server
- `npm run build` — production build (includes type-checking)
- `npm run lint` — ESLint
