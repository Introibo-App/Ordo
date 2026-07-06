=== Ordo ===
Contributors: directorium
Tags: liturgical, calendar, catholic, latin-mass, traditional
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The traditional Roman liturgical calendar (1962) for WordPress. Bundles the Directorium engine and resolves every day on your server — no external service.

== Description ==

**Ordo** presents the traditional Roman liturgical calendar — the Rubrics of 1960 (the 1962 editio typica) — on any WordPress site. It bundles the clean-room Directorium engine and computes every day locally, so it works with **no API key and no external dependency**.

The interface adopts the navy-and-gold "Illuminated Breviary" design and is fully self-contained.

**Surfaces**

* **Today card** — `[ordo_today]` (and the *Ordo — Today* block): the current liturgical day with its class, colour and season, linking into the day view.
* **Masthead strip** — `[ordo_calendar_strip]` (and the *Ordo — Calendar strip* block): a rolling week that slides a day at a time; a one-line `variant="ribbon"` suits a site header.
* **Month calendar** — `[ordo_calendar]` (and the *Ordo — Month calendar* block): a month grid marking the liturgical colour, class and first-class feasts; a mobile agenda below.
* **Day view** — a focus-trapped modal (served over a public REST route) plus a shareable `/ordo/YYYY-MM-DD/` page for every day. The same body backs both, so they never disagree.
* **Settings** — a tabbed screen (Settings API) to pick the calendar, a white-label palette, and display preferences.

**Particular calendars**

Ordo ships defaulting to the **Universal 1962** calendar. A site can select a society's proper calendar — **SSPX**, **FSSP** or **ICKSP** — which layers that society's feasts over the universal calendar.

**Accuracy first**

Ordo renders only what the engine can state from the calendar: the winning celebration, its rank and colour, the day's commemorations, the season and the temporal day. Mass propers and rubric texts belong to a later milestone and are never invented.

**Licensing**

Ordo is licensed **GPL-2.0-or-later**. It bundles the Directorium Core engine (under `src/Core/`), which is licensed **AGPL-3.0-or-later**; the two are compatible because GPL-2.0-or-later reaches GPLv3, with which AGPL-3.0 combines. The compiled calendar dataset (`src/Core/data/corpus/`) is released under **CC0**.

== Installation ==

1. Upload the `ordo` folder to `/wp-content/plugins/`, or install the `ordo.zip` via **Plugins → Add New → Upload Plugin**.
2. Activate **Ordo** through the **Plugins** menu. Activation registers the `/ordo/YYYY-MM-DD/` day pages and seeds the default settings.
3. Visit **Settings → Ordo** to choose the calendar (Universal 1962 by default) and a palette.
4. Add a surface with a shortcode or its block — e.g. `[ordo_calendar]` on a page, or `[ordo_calendar_strip variant="ribbon"]` in your header.

Requires PHP 7.4 or newer and WordPress 6.3 or newer (the blocks use the iframed editor, block API v3). If the site runs an older PHP, Ordo declines to load and shows an admin notice instead of erroring.

== Frequently Asked Questions ==

= Does Ordo need an internet connection or an API key? =

No. The v0.1 release bundles the engine and resolves every day on your own server. A later release adds an optional thin-client mode backed by the Directorium API.

= Which rubrics does it follow? =

The Rubrics of 1960 — the 1962 editio typica. It is the only rubric system in v0.1; historical editions (1954, 1955, Tridentine) and the Novus Ordo calendar are on the roadmap.

= Can I use my parish's proper calendar? =

v0.1 offers the SSPX, FSSP and ICKSP overlays over the Universal 1962 calendar. Diocesan and religious-order propers are planned.

= Can I match my theme's colours? =

Choose one of the bundled palettes (Illuminated, Parchment, Slate) under **Settings → Ordo → Appearance**. The surfaces are namespaced so they never disturb the rest of your theme.

= Is the day view accessible without JavaScript? =

Yes. Every day links to its `/ordo/YYYY-MM-DD/` page; the modal is a progressive enhancement over those links, with a focus trap and keyboard dismissal when JavaScript is available.

== Screenshots ==

1. The month calendar with liturgical colours, class numerals and first-class feasts.
2. The day view modal: rank, colour, commemorations, season and calendar context.
3. The masthead calendar strip and one-line ribbon.
4. The tabbed settings screen — calendar preset, palette and display.

== Changelog ==

= 0.1.0 =
* Initial release — a standalone plugin that bundles the Directorium engine (no external dependency).
* Today card (`[ordo_today]`) and its block.
* Masthead calendar strip (`[ordo_calendar_strip]`, with a one-line `ribbon` variant) and its block.
* Month calendar (`[ordo_calendar]`) with a mobile agenda, and its block.
* Day view: a REST-served, focus-trapped modal and shareable `/ordo/YYYY-MM-DD/` pages sharing one body.
* Particular calendars: Universal 1962 (default), SSPX, FSSP, ICKSP.
* Tabbed settings (Settings API) with white-label palettes and a display preference.
* Full internationalisation via the `ordo` text domain; Latin liturgical text is content, not translated.
* Assets versioned by file modification time and enqueued only where a surface renders.

== Upgrade Notice ==

= 0.1.0 =
First release.
