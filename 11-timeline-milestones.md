# 11 — Timeline & Milestones

Estimates assume the team in `00-README.md` (2 backend, 2 mobile, 1 web/admin, 1 designer,
1 QA) working in parallel from week 1, design running ~1 sprint ahead of dev.

## MVP scope cut (recommended — don't build all 8+8+8 sprints before launching)

If you want to actually ship and learn instead of building for 6+ months first, cut to:

**MVP includes:** OTP auth, property CRUD + basic search/filter, image upload, contact-unlock
wallet with the video-or-coupon-then-scratch-card mechanic (this is your differentiator —
don't cut it), basic chat, basic admin (moderation + coupon/reward config only).

**MVP defers:** Elasticsearch/Meilisearch (DB search is fine at low volume), locality
insights/price trends, agent microsites, property comparison tool, full CMS/blog, dark
mode, shared-element transitions (nice-to-have polish, not launch-blocking).

## Phased timeline

| Phase | Weeks | Covers |
|---|---|---|
| Phase 1 — Foundations | 1–2 | Backend Sprint 0, Mobile Sprint 0, Admin Sprint 0, final design system in doc 10 signed off |
| Phase 2 — Auth & Catalog | 3–6 | Backend Sprints 1–2, Mobile Sprints 1–3, design for post-property wizard finalized |
| Phase 3 — Monetization Engine | 7–10 | Backend Sprint 3, Mobile Sprint 5 (wallet/paywall/scratch card), Admin Sprint 4 — **this phase is the riskiest, budget slack time for SSV/webhook integration debugging** |
| Phase 4 — Chat, Admin Core | 11–13 | Backend Sprint 4–5, Mobile Sprint 6, Admin Sprints 1–3 |
| Phase 5 — Hardening | 14–16 | Backend Sprints 6–7, Mobile Sprints 7–8, Admin Sprints 5–7, full doc 05 security pass, load testing |
| Phase 6 — Launch prep | 17–18 | Backend Sprint 8, store submission, staged rollout, monitoring live |

**Rough MVP timeline: ~18 weeks (4–4.5 months)** with the assumed team size. Fewer people
or a broader initial scope (full feature parity with 99acres on day one) pushes this
significantly — realistically 7–9 months for the "everything" version described across
docs 07–09.

## Key milestones to actually track

- [ ] **M1:** First OTP login + first property posted end-to-end (staging) — proves auth+catalog pipeline
- [ ] **M2:** First successful video-ad-verified credit grant (staging, AdMob test ads) — proves the SSV integration, your highest-risk piece
- [ ] **M3:** First successful Razorpay test-mode coupon purchase → wallet credit → scratch card → reward — proves the full monetization loop
- [ ] **M4:** First real listing moderated end-to-end through the admin panel
- [ ] **M5:** Security checklist (doc 05) fully signed off
- [ ] **M6:** Store submissions accepted (Play Store + App Store)
- [ ] **M7:** Public launch

## Post-launch, not pre-launch

Explicitly *don't* block launch on: Elasticsearch migration, agent microsites, locality
price-trend charts, property comparison tool, full blog/CMS. Ship the differentiator
(verified listings + gamified unlock), then iterate based on real usage data — you'll learn
more from real scratch-card redemption rates than from any amount of pre-launch polish on
features competitors already have.
