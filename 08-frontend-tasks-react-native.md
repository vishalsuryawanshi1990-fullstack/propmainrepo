# 08 — Mobile App Tasks (React Native) — Sprint Checklist

Assume 2-week sprints, 2 mobile devs. Design tokens/animations reference doc 10.

## Sprint 0 — Foundations
- [ ] Expo bare/dev-client project init, TypeScript config
- [ ] Navigation skeleton (auth stack, main tab stack, modal stack for paywall/scratch-card)
- [ ] Design system setup: theme file (colors/type/spacing from doc 10), reusable component library (Button, Card, Input, BottomSheet)
- [ ] Reanimated + Moti + Lottie + Skia installed and smoke-tested
- [ ] RTK Query (or React Query) API client with auth token injection + refresh handling
- [ ] Secure token storage via `react-native-keychain`

## Sprint 1 — Onboarding & Auth
- [ ] Splash screen with Lottie brand animation
- [ ] OTP request/verify screens with auto-read SMS (Android) where available
- [ ] Profile completion screen, role selection (Buyer / Seller / Agent)
- [ ] Empty/first-run state design (what a brand-new user sees)

## Sprint 2 — Home, Search & Discovery
- [ ] Home screen: animated search bar, category chips, featured carousel
- [ ] Search results: list + map toggle, skeleton shimmer loaders while fetching
- [ ] Filter bottom sheet (price range slider, bedrooms, type, amenities) with animated open/close
- [ ] Map view with clustered markers, marker-tap-to-preview-card animation
- [ ] Save-search + favorites (animated heart micro-interaction)

## Sprint 3 — Property Details
- [ ] Image/video gallery with parallax hero + pinch-zoom
- [ ] Spec grid, amenities list, locality insights section (price trend mini-chart)
- [ ] EMI calculator (interactive slider, animated number counter for monthly payment)
- [ ] Share sheet, report-listing flow
- [ ] "View Contact" CTA wired to the unlock flow (see Sprint 5)

## Sprint 4 — Post Property Flow
- [ ] Multi-step animated form wizard (progress bar, step transitions) — type → location → specs → photos → price → review
- [ ] Map-based location picker with draggable pin + reverse geocoding
- [ ] Image picker with drag-to-reorder, primary-photo selection
- [ ] Draft autosave (don't lose a half-filled listing on app kill)
- [ ] Submission confirmation screen + "pending review" status tracking

## Sprint 5 — Monetization UI (core differentiator)
- [ ] Wallet screen (credit balance, transaction history)
- [ ] Paywall bottom sheet: "Watch Video" vs "Buy Coupon" options, clear credit-cost messaging
- [ ] AdMob rewarded video integration (client-side trigger only — actual crediting is server-verified per doc 06)
- [ ] Razorpay checkout integration for coupon purchase
- [ ] **Scratch card component** — Skia canvas-based scratch-to-reveal effect, confetti burst on win, clear "better luck next time" state too (don't only build the happy path)
- [ ] Result screen / toast after unlock showing revealed contact details

## Sprint 6 — Chat & Notifications
- [ ] Chat list + conversation screen, WebSocket live updates
- [ ] Typing indicators, read receipts
- [ ] Push notification handling (foreground/background/killed states), deep-link into the right screen
- [ ] In-app notification center

## Sprint 7 — Profile, Settings, Polish
- [ ] Profile screen, my-listings management (edit/pause/mark-sold), leads-received view for sellers
- [ ] Settings (notification prefs, language if applicable, delete-account flow — required for store compliance)
- [ ] Dark mode (optional but pairs well with animation polish)
- [ ] Accessibility pass (font scaling, contrast, screen reader labels)

## Sprint 8 — Testing & Store Submission
- [ ] Jest unit tests on core logic (unlock flow state machine, form validation)
- [ ] Detox E2E on critical paths (signup → post listing → unlock contact)
- [ ] Play Store / App Store metadata, privacy policy, data-safety form (must disclose ad SDK + payment SDK data usage)
- [ ] Staged rollout plan (internal testing → closed beta → production)
