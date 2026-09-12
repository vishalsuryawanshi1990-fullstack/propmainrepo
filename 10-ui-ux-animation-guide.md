# 10 — UI/UX & Animation Guide

Applies to the RN app, the website, and the admin panel — one shared design language.

## Design principles

- Real estate is a high-trust, high-stakes decision — the UI should read as **calm and
  credible**, not gimmicky. Animation should reward interaction, not distract from a
  ₹50-lakh decision.
- Verification/trust signals (verified badge, RERA tag, KYC checkmark) should be visually
  prominent — this is your actual differentiator vs. broker-spam-heavy competitors.
- Motion should communicate state (loading, success, error) before it decorates.

## Suggested visual direction

| Token | Suggestion |
|---|---|
| Primary | Deep teal/indigo (`#0F4C5C`-ish) — trust, stability, distinct from 99acres' red and Housing's blue |
| Accent (CTAs, rewards) | Warm gold/amber (`#F4A425`-ish) — used *specifically* for the monetization/reward moments (unlock, scratch card, coupon) so it becomes a learned "something good happens here" cue |
| Neutral surface | Off-white/light gray backgrounds, not pure white — reduces glare on long browsing sessions |
| Success | Green, standard |
| Typography | A geometric sans (Inter, or Poppins for a slightly warmer feel) for UI; a serif or semi-serif for hero/marketing headings on the website only, to feel more "premium property" than "app-y" |

## Specific animated components (by priority)

1. **Scratch card** — React Native Skia canvas: gray "scratch" layer over the revealed
   reward, erased via touch-path masking; confetti burst (Skia or Lottie) on a win, subtle
   shake/fade on "better luck next time" so losses don't feel jarring.
2. **Rewarded-video → credit granted** — loading state while server verifies SSV callback
   (this has real latency, don't fake instant success), then a satisfying credit-added
   animation (coin/number fly-in to wallet icon).
3. **Property card list** — skeleton shimmer while loading (not spinners), staggered
   fade-in as cards resolve.
4. **Image gallery / hero** — parallax scroll on property detail, shared-element
   transition from the list thumbnail into the detail hero (feels seamless, not a hard cut).
5. **Post-property wizard** — animated progress bar + step-to-step slide transition,
   makes a long form feel shorter.
6. **Bottom tab bar** — subtle bounce/scale on active tab icon, not just a color change.
7. **Pull-to-refresh** — custom branded animation instead of the default spinner.
8. **Map markers** — cluster-to-individual expand animation when zooming in.
9. **Admin dashboard stat cards** — count-up number animation on load, small sparkline trend.
10. **Admin listing moderation** — approve/reject causes the card to animate off the queue rather than just disappearing.

## Libraries

| Purpose | Library |
|---|---|
| General animation (RN) | `react-native-reanimated` + `moti` (declarative wrapper) |
| Vector/brand animations | `lottie-react-native` (use for splash, empty states, success/error illustrations) |
| Scratch card / confetti / custom canvas | `@shopify/react-native-skia` |
| Shared element transitions | `react-navigation-shared-element` or Reanimated's built-in shared transitions (RN 0.75+) |
| Admin panel transitions | `framer-motion` |
| Admin charts | `recharts` or `apexcharts` |

## Guardrails

- Every animation needs a "reduce motion" fallback (accessibility setting) — instant
  state changes instead of transitions when the OS-level reduce-motion flag is on.
- Don't animate anything that delays the user from completing a task (e.g., don't make
  them wait through a 2-second flourish before they can tap "Call Owner" — decorate around
  the action, don't gate it).
- Keep the scratch-card and reward animations visually distinct from the "genuine listing"
  browsing experience — mixing the gamified/playful reward aesthetic into the core
  property-browsing UI would undercut the "credible marketplace" trust signal.
