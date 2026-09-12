# 05 — Security & Compliance Checklist

Treat this as a gate, not a phase — several items below must be designed into features
from the start (marked **[BUILD-IN]**), not added later.

## Authentication & authorization

- [ ] Sanctum tokens, short expiry + refresh, revoke on logout/password change
- [ ] OTP-based phone verification mandatory for posting a listing **[BUILD-IN — this is your core anti-fake-listing control]**
- [ ] `spatie/laravel-permission` for RBAC — never check roles by string comparison scattered across controllers
- [ ] Admin panel requires separate, stricter session policy (shorter token TTL, optional 2FA for admin accounts)
- [ ] Rate-limit OTP requests hard (see doc 04) — this is the #1 abused endpoint on every listings site (SMS pumping fraud)

## Input validation & data handling

- [ ] All input validated via Form Requests, never raw `$request->all()` into `Model::create()`
- [ ] `$fillable` (not `$guarded = []`) on every model — mass-assignment protection
- [ ] File uploads: whitelist mime types, max size, re-encode images server-side (strips embedded scripts/EXIF), randomize stored filenames, store outside `public/`, serve via signed S3 URLs
- [ ] Rich text (property descriptions) sanitized with HTMLPurifier before storage/display — XSS vector
- [ ] Never build raw SQL with string-concatenated user input; Eloquent/query builder bindings only

## Transport & storage

- [ ] TLS 1.2+ enforced everywhere (HSTS header)
- [ ] Sensitive columns (phone, email, KYC doc paths) — consider Laravel's `encrypted` cast for KYC-adjacent fields
- [ ] Passwords (if used at all alongside OTP) hashed with bcrypt/argon2, never reversible
- [ ] KYC documents: encrypted at rest, access logged (who viewed which user's Aadhaar/PAN), retained only as long as legally necessary, deletable on account deletion request

## API-specific

- [ ] CORS locked to known origins (app, website, admin) — not `*`
- [ ] CSRF protection on admin panel session-based routes; token-based API routes are CSRF-exempt but must require Bearer auth
- [ ] Razorpay **webhook signature verification** on every webhook call — never trust an unsigned "payment succeeded" hit **[BUILD-IN]**
- [ ] AdMob **Server-Side Verification (SSV)** for rewarded videos — the wallet credit is granted only after the signed SSV callback, never from a client "ad finished" event **[BUILD-IN — this is the single most abuse-prone part of the monetization engine]**
- [ ] Idempotency keys on payment/credit-granting endpoints to prevent double-crediting on retries

## Mobile app hardening

- [ ] Auth tokens in Keychain/Keystore (`react-native-keychain`), never AsyncStorage
- [ ] No API secrets/keys bundled in the RN JS bundle (it's trivially extractable) — all secret keys stay server-side; the app only ever talks to your API, never directly to Razorpay/AdMob with a secret
- [ ] Certificate pinning for the API domain (optional but recommended given financial transactions in-app)
- [ ] Obfuscate/minify release builds (Hermes + ProGuard/R8 on Android)

## Fraud & listing-integrity controls (the "genuine buyers/sellers" requirement)

- [ ] Mandatory phone OTP + optional KYC (Aadhaar/PAN) before a listing goes live
- [ ] Duplicate-listing detection: hash of (address + price + image perceptual hash) to catch the same flat re-posted by multiple "owners" (classic broker-spam pattern on these platforms)
- [ ] New listings enter `pending_review` and are moderated before going public (or auto-approved with post-hoc sampling if volume is too high for manual review — your call, document it)
- [ ] Report/flag button on every listing, feeding into `reported_listings` with an SLA for moderator action
- [ ] Device fingerprinting + IP velocity checks on signup to slow down fake-account farms created purely to harvest free unlock credits

## Compliance

- [ ] **India DPDP Act 2023**: consent capture for data collection, data principal rights (access/delete), data breach notification process, appoint a grievance officer if required at your scale
- [ ] **RERA**: for project/builder listings, capture and display RERA registration number where legally mandated in that state; disclaimers for listings without one
- [ ] **PCI-DSS**: never touch/store card data directly — Razorpay Checkout handles card entry, your backend only sees tokens/order IDs
- [ ] **Scratch-card/reward mechanic legal review**: structure rewards as non-cash, non-withdrawable wallet credits/discount vouchers (not cash prizes) to stay in "marketing promotion" territory rather than regulated games-of-chance; get local counsel to confirm before launch, since a few Indian states have specific rules on prize-linked promotions
- [ ] Terms of Service should not represent listings as "guaranteed genuine" — use "verified" badges with a clearly stated verification methodology instead, to manage liability

## Logging, monitoring, recovery

- [ ] Centralized error tracking (Sentry) for API + admin panel
- [ ] Audit log (`audit_logs` table) for every admin action: approve/reject listing, KYC decision, coupon/reward-odds changes, manual wallet adjustments
- [ ] Automated nightly DB backups with tested restore procedure; S3 bucket versioning for media
- [ ] Dependency scanning in CI (`composer audit`, `npm audit`) on every PR
- [ ] Cloudflare (or similar) WAF in front of both API and website for baseline DDoS/bot protection
