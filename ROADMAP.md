# Roadmap

_A plain-language overview of where Ordo is headed. Each version links to its tracking milestone and
the issues that make it up._

_Last updated: 2026-07-03._

## Release train
- **R1 — Groundwork.** The standalone plugin bundling the finished 1962 engine, ahead of any thin-client
  work.
- **R2 — 3mi.org.** The plugin ships defaulting to **Universal 1962**; the **3mi.org** pilot **selects the
  SSPX preset** in its settings — the platform's first live surface. SSPX is a per-site setting, not the
  shipped default: one build serves every parish.
- **R3+.** Everything else, in the build order below.

## 🚧 v0.1.0 — [code-complete; the pilot deploy remains](https://github.com/Introibo-App/Ordo/milestone/1)
**Standalone plugin — the 3mi.org pilot.** A self-contained MVP that bundles the Introibo Core engine and
resolves every day offline (no API): the masthead calendar strip, `[ordo_today]`, `[ordo_calendar]` (full
month grid), the day view (a REST-served modal plus shareable `/ordo/YYYY-MM-DD/` pretty pages), and a
tabbed settings screen — each as both a shortcode and a block, with i18n, `filemtime` cache-busting,
rewrite rules, and the full activation lifecycle. The interface was built to **per-surface mockups approved
before any UI**.

**All build epics are merged** — #1 scaffold & lifecycle · #6 mockups · #11 today & masthead · #15 calendar
& day view · #20 settings, i18n & assets. The one remaining item is **[#99](https://github.com/Introibo-App/Ordo/issues/99)
— the live install on the 3mi.org refresh** (select SSPX, verify every surface, cross-check the SSPX ordo):
a **maintainer step** that needs access to the WordPress site.

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
_None yet — v0.1.0 is code-complete and awaiting its pilot deployment on 3mi.org._
