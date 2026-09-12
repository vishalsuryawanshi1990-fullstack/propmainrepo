# EstateConnect Admin Panel

React + Vite + TypeScript SPA per `02-tech-stack-architecture.md` /
`09-admin-panel-tasks.md`. Talks to the Laravel API in `../backend` —
same Sanctum-token auth, scoped to `admin`/`moderator` roles.

## Setup

```bash
npm install
cp .env.example .env   # point VITE_API_BASE_URL at your backend
npm run dev
```

Sign-in uses the same OTP flow as the mobile app
(`POST /auth/otp/request` / `verify`) — there is no separate admin
login. A user needs the `admin` or `moderator` role (see the backend's
`RolesAndPermissionsSeeder`) to get past the login screen.

## What's here vs. deferred

All 8 sprints from `09-admin-panel-tasks.md` have working coverage:
dashboard, KYC review, listing moderation, user management, reported
listings, coupon CRUD, scratch-reward odds editor, CMS (banners/blogs/
FAQs), analytics, audit log viewer, and a dark-mode toggle.

Deferred as polish rather than core workflow (doc11's own MVP-cut logic
applied to this panel): a live/websocket activity feed on the
dashboard, a toast notification system (inline error/success states are
used instead), skeleton loading states (plain "Loading…" text instead),
a wallet-ledger viewer, video-ad cap/cooldown config UI, a fraud-signal
dashboard, drag-and-drop Kanban for moderation (doc09 explicitly allows
the simpler button-based flow used here "if drag feels gimmicky"), and
a dedicated role/permission management UI (roles are seeded on the
backend; there's no in-panel role editor yet).

## Scripts

- `npm run dev` — dev server
- `npm run build` — type-check (`tsc -b`) + production build
- `npm run lint` — oxlint
