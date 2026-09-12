# 03 — Database Schema (Core Tables)

Not full DDL — this is the entity list + key columns + relationships to hand to whoever
writes the migrations. Add timestamps + soft-deletes to all tables by default.

## Identity & profiles

**users**
`id, name, email (nullable, unique), phone (unique), phone_verified_at, email_verified_at, password (nullable if OTP-only), role_id, status(active/suspended/banned), device_id, last_login_at`

**kyc_documents**
`id, user_id, doc_type(aadhaar/pan/gst/rera), file_path, status(pending/verified/rejected), verified_by(admin_id), verified_at, rejection_reason`

**seller_profiles / agent_profiles**
`id, user_id, business_name(nullable, agents), rera_id(nullable), bio, avatar_path, is_verified_owner(bool)`

## Property catalog

**properties**
`id, owner_id(user_id), agent_id(nullable), title, description, property_type_id, listing_type(sale/rent), price, price_negotiable(bool), area_sqft, bedrooms, bathrooms, floor_no, total_floors, furnishing_status, city_id, locality_id, address, latitude, longitude, rera_registration_no(nullable), status(draft/pending_review/live/rejected/sold/expired), is_featured(bool), views_count, created_at`

**property_images / property_videos**
`id, property_id, file_path, is_primary(bool), sort_order`

**property_amenities** (pivot)
`property_id, amenity_id`

**amenities_master**
`id, name, icon, category(society/flat/security)`

**property_types_master**
`id, name` (Apartment, Villa, Plot, Commercial, PG/Co-living, ...)

**cities_master / localities_master**
`id, name, state, [localities: city_id, name, lat, long, avg_price_sqft]` — powers "locality insights"

**reported_listings**
`id, property_id, reported_by, reason, status(open/resolved), resolved_by`

## Monetization engine (see doc 06 for logic)

**wallets**
`id, user_id, contact_unlock_credits(int), cashback_balance(decimal)`

**wallet_transactions**
`id, wallet_id, type(credit/debit), source(signup_bonus/video_ad/coupon/scratch_reward/unlock_spend/admin_adjustment), amount, reference_id, created_at`

**coupons**
`id, code, type(fixed_credits/percentage_discount), value, price, max_redemptions, redemptions_count, valid_from, valid_until, is_active`

**coupon_redemptions**
`id, coupon_id, user_id, payment_id(nullable), redeemed_at`

**video_ad_events**
`id, user_id, ad_network(admob), ssv_signature, ssv_verified(bool), credited(bool), created_at` — **the SSV row is what actually authorizes crediting, not a client event**

**scratch_cards**
`id, user_id, triggered_by(video/coupon), reward_id(nullable), is_scratched(bool), scratched_at, expires_at`

**scratch_rewards_master**
`id, reward_type(bonus_credit/cashback/discount_voucher/none), value, probability_weight, is_active` — admin-configurable odds table

**contact_unlocks**
`id, unlocker_user_id, property_id, credits_spent, unlocked_at` — the actual "who has already paid to see whose contact" ledger; check this before charging again for the same property

## Transactions & subscriptions

**payments**
`id, user_id, gateway(razorpay), gateway_order_id, gateway_payment_id, amount, purpose(coupon_purchase/subscription/featured_listing), status(created/paid/failed/refunded), webhook_verified(bool)`

**subscription_plans**
`id, name, target_role(seller/agent), duration_days, price, featured_listings_included, contact_unlocks_included`

**subscriptions**
`id, user_id, plan_id, starts_at, ends_at, status`

## Engagement

**favorites** `id, user_id, property_id`
**property_views** `id, property_id, viewer_id(nullable), ip_hash, viewed_at` — for analytics + duplicate-view throttling
**chats** `id, property_id, buyer_id, seller_id, last_message_at`
**chat_messages** `id, chat_id, sender_id, message, attachment_path(nullable), read_at`
**reviews_ratings** `id, target_type(locality/agent/builder), target_id, user_id, rating, comment`
**notifications** `id, user_id, type, title, body, data(json), read_at`

## Admin & audit

**admin_users** — separate table or reuse `users` with role flag (recommend separate for clean permission boundary)
**audit_logs** `id, actor_id, action, subject_type, subject_id, before(json), after(json), ip_address, created_at`
**banners_cms / blogs_cms / faqs** — for the SEO website + in-app promo banners

## Indexing notes

- `properties`: composite index on `(city_id, locality_id, listing_type, status, price)` for the common filter query; spatial index on `(latitude, longitude)` for radius search
- `contact_unlocks`: unique index on `(unlocker_user_id, property_id)` to prevent double-charging
- `wallet_transactions`: index on `(wallet_id, created_at)` for statement queries
- Full-text/typo-tolerant search on `properties.title`, `localities_master.name` → delegate to Meilisearch rather than MySQL `FULLTEXT` for better relevance
