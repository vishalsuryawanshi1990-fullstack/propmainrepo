# 06 — Monetization Engine: Free Listing → Video/Coupon → Scratch Card

This is the core mechanic that differentiates the platform, so it gets its own doc.

## Model chosen: wallet + contact-unlock credits

Rather than "1st *property* free, then blocked" literally, use a **credit wallet** — it's
simpler to reason about, easier to make fair for both buyers and sellers, and much easier
to abuse-proof:

- Every new (OTP-verified) user gets **1 free contact-unlock credit** on signup.
- Viewing another user's contact details on any listing (as a buyer wanting the owner's
  number, or as a seller wanting a buyer's number who enquired) **consumes 1 credit**.
- The listing itself (title, photos, price, locality, specs) is always free to browse —
  only the *contact details* sit behind the credit wall. This matches how NoBroker/99acres
  actually gate value (they don't hide listings, they hide contact info).

## The unlock flow (step by step)

1. User taps "View Contact" / "Call Owner" on a property.
2. API `POST /unlocks/check` — if a `contact_unlocks` row already exists for this
   (user, property) pair, just return the contact info again for free (already paid).
3. If wallet has `contact_unlock_credits >= 1`, allow `POST /unlocks/spend` directly —
   decrements credit, writes `contact_unlocks` row, returns contact info.
4. If wallet is empty, show the **paywall sheet** with two options:
   - **Watch a rewarded video** (30s AdMob rewarded ad)
   - **Buy a coupon** (small Razorpay payment, e.g. ₹19 for 3 credits)
5. On successful completion of either path, credit the wallet **and** immediately spawn a
   `scratch_cards` row (`triggered_by = video` or `coupon`) for the user to play.
6. User taps to scratch → `POST /scratch-cards/{id}/scratch` → server picks a weighted
   random reward **at this moment** (not pre-determined at creation, to prevent
   reward-prediction/replay attacks) from `scratch_rewards_master`, applies it (extra
   credit / cashback / discount voucher / "better luck next time"), returns the result for
   the animated reveal on the client.
7. User now has credit(s) → back to step 3 to actually view the contact.

## Why the video path MUST be server-verified

The single highest-risk point in this whole system: a client can fake "ad watched" and
farm free unlocks. **Never grant a credit from a client-side event.** Use AdMob's
**Server-Side Verification (SSV)**:

1. App requests a signed ad-request token from your API (`POST /video-ads/request-token`) — ties the eventual ad watch to this specific user/session.
2. AdMob SDK plays the ad, and on completion AdMob's servers call **your** webhook
   (`POST /video-ads/ssv-callback`) with a signed payload.
3. Your backend verifies the signature against Google's public key, checks the token
   matches an un-consumed request, and *only then* credits the wallet.
4. The client polls/receives a push notification once the credit lands — it never
   self-reports success.

## Coupon purchase path

1. `POST /coupons/purchase` → backend creates a Razorpay order, returns order details to app.
2. App completes payment via Razorpay Checkout SDK.
3. Razorpay calls your **webhook** (`POST /webhooks/razorpay`) with a signed payload —
   this, not the app's "payment success" callback, is what actually credits the wallet.
   (The app's callback is only used for UX — "processing your payment..." — never for
   granting value.)
4. Idempotent on `gateway_payment_id` so a retried webhook can't double-credit.

## Anti-abuse rules to configure

| Control | Suggested default |
|---|---|
| Max rewarded videos per user per day | 5–10 (tune based on ad fill rate/eCPM economics) |
| Cooldown between video watches | 2–5 minutes |
| Max free-credit signups per device/IP per day | Low single digits — device fingerprint + phone OTP is your real defense here |
| Scratch card validity | Expires after 24–48h if unscratched, to create urgency without letting rewards pile up indefinitely |
| Reward odds | Configurable table in admin (`scratch_rewards_master.probability_weight`) — start conservative (mostly small/no rewards, rare bigger ones), tune from real redemption-cost data |

## Admin-configurable knobs (all live in the admin panel, doc 09)

- Free signup credit count
- Coupon bundle pricing (credits per ₹)
- Scratch reward table (reward types + probability weights) — must sum sensibly, validate in admin UI
- Daily video-watch cap
- Whether reward types are cash-equivalent or must stay non-cash (see compliance note in doc 05 — recommend enforcing this as a hard constraint in the admin UI, not just a policy)
