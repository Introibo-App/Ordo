# Ordo

> The traditional Roman liturgical calendar for WordPress — *Introíbo ad altáre Dei.*

**Ordo** is a WordPress plugin that presents the traditional Roman liturgical calendar on any site.
Its first release is a **standalone MVP** that bundles the Introibo engine and works with no
external dependency; a later release refactors it into a thin client of the Introibo API (caching
in WP transients and degrading gracefully when the API is unreachable).

It ships a masthead **calendar strip**, the `[ordo_today]` and `[ordo_calendar]` shortcodes (and
matching blocks), a **day view** (a modal via REST plus shareable `/ordo/YYYY-MM-DD/` pages), and a
tabbed **settings** screen with white-label palettes. The interface re-implements the established
navy/gold/parchment "Illuminated Breviary" design.

## Status

Pre-release — **v0.1.0 in progress**. See the [roadmap](ROADMAP.md).

## Install

Download the latest `ordo.zip` from [Releases](../../releases), then in WordPress go to
**Plugins → Add New → Upload Plugin**, upload the zip, and activate. Requires PHP 7.4+ and a
supported WordPress version.

## Development

Work branches off `develop`, lands via squash PRs with Conventional-Commit titles, and releases are
cut automatically by release-please (the plugin header and `readme.txt` stable tag are kept in
sync). No runtime build step — Node is used only to bundle assets at build time.

## Licence

© 2026 Introibo. Licensed under **AGPL-3.0-or-later** (see [LICENSE](LICENSE)). The compiled calendar
dataset is released under **CC0**.
