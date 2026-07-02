# Roadmap

_A plain-language overview of where Ordo is headed. Each version links to its tracking milestone and
the issues that make it up (issue links are added once the backlog is imported)._

_Last updated: 2026-07-02_

## Release train
- **R1 — Groundwork.** The standalone plugin bundling the finished 1962 engine, ahead of any thin-client
  work.
- **R2 — 3mi.org.** The plugin ships with the **SSPX** default preset for the **3mi.org pilot
  deployment** — the platform's first live surface.
- **R3+.** Everything else, in the build order below.

## 🚧 In progress — [v0.1.0](https://github.com/Introibo-App/Ordo/milestone/1)
**Standalone plugin — the 3mi.org pilot.** A self-contained MVP that bundles the Introibo Core engine
with the **SSPX default preset** for the **3mi.org pilot deployment (R2)**: the masthead calendar strip,
`[ordo_today]`, `[ordo_calendar]` (full month grid), the day view (REST modal + shareable pretty pages),
and a settings screen. The interface is re-implemented from the established design, **with mockups
approved before any UI is built**.

## 🗓️ Next — [v0.2.0](https://github.com/Introibo-App/Ordo/milestone/2)
**Thin client.** Replace the bundled engine with Introibo API calls, cache responses in WP transients
(transient cache), and degrade gracefully when the API is unreachable.

## 🔭 Future
- **[v0.3.0](https://github.com/Introibo-App/Ordo/milestone/3) — Configurable UI platform.** Reader
  switchers, tabbed settings (show/hide/lock), white-label palettes, accessibility, hour navigator +
  go-to-date, PWA/offline.
- **[v0.4.0](https://github.com/Introibo-App/Ordo/milestone/4) — Outputs & integrations.** Printable
  ordo + iCal + sacristy directive notes + necrology + print CSS.
- **[v1.0.0](https://github.com/Introibo-App/Ordo/milestone/5) — WordPress.org readiness.** Cut together
  with Core, Api, and Site.
- **[v1.1.0](https://github.com/Introibo-App/Ordo/milestone/6) — Mass & Office reader.** The reader
  surfaces inside the plugin + worship-aid output, plus an optional **"Why this day?" disclosure**
  (mockups-gated).
- **[v1.2.0](https://github.com/Introibo-App/Ordo/milestone/8) — Novus Ordo output.** The Novus Ordo
  surfaces inside the plugin.
- **[v2.0.0](https://github.com/Introibo-App/Ordo/milestone/7) — Comparison block.** An optional
  embeddable comparison widget (mockups-gated).

## ✅ Released
_None yet._
