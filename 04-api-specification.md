# 04 — API Specification (v1)

Base path: `/api/v1`. All authenticated routes use `Authorization: Bearer <sanctum-token>`.
Standard response envelope:

```json
{ "success": true, "data": { ... }, "message": "OK", "meta": { "page": 1, "per_page": 20, "total": 134 } }
```

Errors:
```json
{ "success": false, "message": "Validation failed", "errors": { "field": ["reason"] } }
```

## Auth

| Method | Endpoint | Notes |
|---|---|---|
| POST | `/auth/otp/request` | Rate-limited hard (5/hour/phone, 20/hour/IP) — SMS-bombing vector |
| POST | `/auth/otp/verify` | Returns Sanctum token on success |
| POST | `/auth/register` | Profile completion after OTP verify |
| POST | `/auth/logout` | Revoke current token |
| POST | `/auth/refresh` | If using short-lived tokens |
| POST | `/auth/social/{provider}` | Optional: Google login |

## Users / Profile

| Method | Endpoint |
|---|---|
| GET | `/me` |
| PATCH | `/me` |
| POST | `/me/kyc-documents` |
| GET | `/me/kyc-documents` |
| GET | `/users/{id}/public-profile` (agent/seller public page) |

## Properties

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/properties` | Filters: `city, locality, type, listing_type, min_price, max_price, bedrooms, lat, lng, radius_km, sort, page` |
| GET | `/properties/{id}` | Contact fields masked unless unlocked (see Monetization) |
| POST | `/properties` | Seller/Agent only; goes to `pending_review` |
| PATCH | `/properties/{id}` | Owner only |
| DELETE | `/properties/{id}` | Soft delete |
| POST | `/properties/{id}/images` | Multipart, pre-signed S3 upload preferred over direct proxy |
| GET | `/properties/{id}/similar` | |
| GET | `/properties/featured` | |
| POST | `/properties/{id}/report` | |
| POST | `/properties/{id}/favorite` / DELETE | |
| GET | `/localities/{id}/insights` | Avg price/sqft, trend |

## Monetization (see doc 06 for full flow)

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/wallet` | Balance + credits |
| GET | `/wallet/transactions` | |
| POST | `/unlocks/check` | `{property_id}` → tells client whether already unlocked / needs payment path |
| POST | `/unlocks/spend` | Consumes 1 credit, returns contact details, writes `contact_unlocks` row |
| POST | `/video-ads/request-token` | Generates a server-issued token embedded in the ad request, tied to user+timestamp |
| POST | `/video-ads/ssv-callback` | **Server-to-server callback from AdMob only** — verifies signature, credits wallet, never called by the app directly |
| GET | `/coupons` | List purchasable coupon bundles |
| POST | `/coupons/purchase` | Creates Razorpay order |
| POST | `/coupons/redeem` | For promo-code style coupons |
| POST | `/scratch-cards/{id}/scratch` | Server picks weighted-random reward at this moment (not at creation) and returns it |
| GET | `/scratch-cards/pending` | |

## Payments

| Method | Endpoint |
|---|---|
| POST | `/payments/razorpay/order` |
| POST | `/webhooks/razorpay` | Signature-verified, idempotent (dedupe on `gateway_payment_id`) |

## Chat

| Method | Endpoint |
|---|---|
| GET | `/chats` |
| GET | `/chats/{id}/messages` |
| POST | `/chats/{id}/messages` |
| WebSocket channel | `private-chat.{chat_id}` via Reverb |

## Admin (separate `admin` role-gated group, ideally separate route prefix `/api/v1/admin`)

| Method | Endpoint |
|---|---|
| GET | `/admin/properties/pending` |
| POST | `/admin/properties/{id}/approve` / `/reject` |
| GET | `/admin/users` , `/admin/kyc/pending` |
| POST | `/admin/kyc/{id}/verify` / `/reject` |
| CRUD | `/admin/coupons` |
| CRUD | `/admin/scratch-rewards` (probability weights) |
| GET | `/admin/analytics/*` (signups, listings, revenue, unlock funnel) |
| CRUD | `/admin/cms/banners`, `/admin/cms/blogs`, `/admin/cms/faqs` |
| GET | `/admin/audit-logs` |

## Rate limiting (baseline — tune per traffic)

| Group | Limit |
|---|---|
| OTP request | 5/hour/phone, 20/hour/IP |
| Search/browse | 120/min/user |
| Unlock spend | 20/min/user (prevents credit-draining scripts) |
| Video SSV callback | verified by AdMob signature, not user-rate-limited the same way |
| Webhooks | not rate-limited, but signature-verified + idempotent |
