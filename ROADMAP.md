# Roadmap

_A plain-language overview of where Ordo is headed. Each version links to its tracking milestone and
the issues that make it up (issue links are added once the backlog is imported)._

_Last updated: 2026-06-30_

## 🚧 In progress — v0.1.0
**Standalone plugin.** A self-contained MVP that bundles the Introibo engine: the masthead calendar
strip, `[ordo_today]`, `[ordo_calendar]` (full month grid), the day view (REST modal + shareable
pretty pages), and a settings screen. The interface is re-implemented from the established design,
**with mockups approved before any UI is built**.

## 🗓️ Next — v0.2.0
**Thin client.** Replace the bundled engine with Introibo API calls, cache responses in WP
transients, and degrade gracefully when the API is unreachable.

## 🔭 Future
- **v0.5.0 — Configurable UI platform.** Reader switchers, tabbed settings (show/hide/lock),
  white-label palettes, accessibility, hour navigator + go-to-date, PWA/offline.
- **v0.6.0 — Outputs & integrations.** iCal + printable monthly Ordo (PDF) + print CSS.
- **v1.0.0 — Platform launch.** Cut together with Core, Api, and Site.
- **v1.1.0+ — On-site Mass & Office reader surfaces.**

## ✅ Released
_None yet._
