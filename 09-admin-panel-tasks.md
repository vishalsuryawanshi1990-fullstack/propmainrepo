# 09 — Admin Panel Tasks (React SPA) — Sprint Checklist

1 web/admin dev assumed. Animation direction lives in doc 10 — this panel is where you
have the most room to make it "eye catching" since it's not gated by app-store review or
mobile perf constraints the way the RN app is.

## Sprint 0 — Foundations
- [ ] React + Vite + TypeScript + Tailwind setup
- [ ] Auth guard against Laravel Sanctum admin/moderator roles, route protection
- [ ] Shared design tokens imported from the same source as the mobile app/website
- [ ] Framer Motion installed, base page-transition wrapper (`AnimatePresence`)
- [ ] Layout shell: collapsible animated sidebar, top bar with notifications bell

## Sprint 1 — Dashboard
- [ ] Animated stat cards (signups, live listings, revenue today) with count-up number animation on load
- [ ] Revenue/unlock-funnel chart (Recharts/ApexCharts) — signups → free-unlock-used → video/coupon → repeat
- [ ] Real-time-ish activity feed (poll or WebSocket) with slide-in new-item animation
- [ ] Quick-action shortcuts (pending KYC count, pending listings count) as animated badge counters

## Sprint 2 — User & KYC Management
- [ ] User list with search/filter, role badges
- [ ] KYC review queue: document viewer, approve/reject with animated card-swipe-away transition on decision
- [ ] Suspend/ban user flow with confirmation modal (animated, not a jarring native `confirm()`)

## Sprint 3 — Listing Moderation
- [ ] Kanban-style board: Pending → Approved / Rejected, drag-to-approve micro-interaction (or simpler button-based flow if drag feels gimmicky for the actual moderators)
- [ ] Image review grid with lightbox
- [ ] Duplicate-listing flag review UI (surfaces the hash-matching job's results from doc 07)
- [ ] Reported-listings queue with reason tags

## Sprint 4 — Monetization Config (the differentiator's control panel)
- [ ] Coupon CRUD (create bundles, pricing, validity, max redemptions)
- [ ] **Scratch-reward odds editor** — sliders/inputs for `probability_weight` per reward type, live-updating pie chart of resulting odds, hard validation that weights make sense and rewards stay non-cash per doc 05
- [ ] Wallet ledger viewer (search by user, see full transaction history, manual adjustment with mandatory reason + audit log entry)
- [ ] Video-ad cap / cooldown config
- [ ] Fraud-signal dashboard: users hitting daily caps repeatedly, device-fingerprint clusters

## Sprint 5 — CMS
- [ ] Banners manager (for app home screen + website)
- [ ] Blog/article manager (feeds the SEO website)
- [ ] FAQ manager

## Sprint 6 — Reports & Analytics
- [ ] Cohort/retention view
- [ ] Revenue breakdown by source (coupon sales vs featured-listing upsells)
- [ ] Exportable CSV reports
- [ ] Listings-by-locality heatmap (nice visual, genuinely useful for city-expansion decisions)

## Sprint 7 — Roles, Settings, Audit
- [ ] Role/permission management UI (spatie permissions exposed as checkboxes per role)
- [ ] Audit log viewer with diff view (before/after JSON from doc 03's `audit_logs`)
- [ ] Global settings (free-credit count, feature flags)

## Sprint 8 — Polish & Handoff
- [ ] Dark mode toggle with smooth theme-transition animation
- [ ] Toast notification system standardized across all actions
- [ ] Loading-state pass everywhere (skeletons, not spinners, per doc 10)
- [ ] Admin onboarding doc for whoever actually operates this day-to-day
