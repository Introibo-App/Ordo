# Ordo v0.1 — interface mockups (Epic #6, the approval gate)

Approved static mockups of the five v0.1 surfaces, on the **Illuminated Breviary** design system —
the same tokens and type the live site (`marymichaelmachabee.org` / `3mi.org`, one project on two
domains, mid design-refresh) already uses, so the plugin renders as native chrome.

Open [`v0.1-illuminated-breviary.html`](v0.1-illuminated-breviary.html) in a browser. The week strip
and the modal day-picker are live (they slide day-by-day; click a day in the modal picker to load it).

## Design tokens (adopted verbatim from the host `site.css`)

| | |
| --- | --- |
| navy | `#0b1d4a` (chrome/ink) · `#16306c` hover |
| gold | `#a97f2e` · `#c9a24a` (bright) · `#856420` (ink) · `#d8c08a` (rule) |
| oxblood | `#7a2e2a` — rubrics-in-red |
| parchment | `#f4ecdc` · cream `#fbf6ec` · paper `#fffdf8` |
| ink / muted | `#222a35` / `#6f7682` |
| radius | chrome **3px**, calendar cells **2px** · max-width 1140px |
| type | **Cormorant Garamond** (feasts/headings) · **Cormorant SC** (all labels/eyebrows, wide-tracked) · **Inter** (body/UI) · Cinzel Decorative (titling) |
| liturgical colour | white · red · green · violet · black · rose; **brightened on-navy palette** for the ribbon (`--on-*`) so dark colours stay legible |

## Surfaces (locked)

1. **Header ribbon** — one line, the site's existing top navy bar. Order **feast · class · feria · date**;
   feast bold but the same gold. Liturgical colour shown as a **brightened colour tab on both edges +
   a gold-ringed gem** by the feast (the gem/tabs use the `--on-*` brightened palette; verified on
   white/red/green/violet/black/rose).
2. **Week strip** (`[ordo_calendar_strip]`) — a rolling week that **slides one day per chevron** (same
   mechanism as the modal picker), so mobile shows 3–5 days but reaches every day — **no scrollbar**.
   Each day opens its day view.
3. **Month grid** (`[ordo_calendar]`) — square cells (`aspect-ratio:1/1`, 2px radius), colour on the
   leading edge, class numeral top-right, today ringed navy, first-class boxed gold; a **corner reserved
   for the v0.4 fasting/abstinence icon** (🐟 placeholder). A **typeable any-year field (1583–2200)** +
   month arrows. **Mobile: an agenda list** with a **date picker** (jump to any day/year) and **infinite
   scroll** (chosen over the dot-grid alternative).
4. **Today card + day view** — `[ordo_today]` is the embeddable today-summary card; the **modal** opens
   for any day with the full office (commemorations, Mass **rubrics in oxblood**). The modal footer is a
   **day-by-day picker** styled to the strip (navy bg, gold selected, white hover) — chevrons slide the
   row, click a day to load it.
5. **Settings** — elevated native-WP chrome, tabbed. Calendar preset list in **abbreviation-first**
   form (SSPX / FSSP / ICKSP over their full names). **Universal 1962 is the shipped default** (tagged
   *Default*); the live site selects SSPX. White-label palettes (Illuminated / Parchment / Slate).

## Build notes carried forward

- Plugin is **standalone** (bundles Core, resolves offline), **PHP 7.4** (WP floor), **GPL-2.0-or-later**
  (WP.org rule; bundled Core stays AGPL-3.0-or-later — compatible via "or-later"; data CC0).
- Fonts are **bundled** (self-hosted woff2 + @font-face, Garamond/Georgia/system fallback) so the plugin
  is correct on any site; on the host they already load.
- The mockup markup/tokens are the reference the surface implementations (Epics #11/#15/#20) build to.
