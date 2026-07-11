---
title: Changelog
---

# Changelog

The authoritative changelog lives at `CHANGELOG.md` in the package root. This page mirrors it.

## [Unreleased]

## [1.0.0] - 2026-07-11

Initial release of the Google Analytics 4 (GA4) integration for the ArtisanPack UI ecosystem. Ships two independent surfaces — client-side `gtag.js` tracking and server-side GA4 Data API reporting — either of which can be adopted on its own.

### Added

- **Client-side GA4 tracking** — `@ga4Snippet` Blade directive plus React (`<Ga4Snippet />`) and Vue equivalents emit the standard `gtag.js` snippet with escaped measurement IDs and safe defaults.
- **Consent-aware tracking** — when installed alongside `artisanpack-ui/analytics`, defers `page_view` until the parent's consent banner grants the `analytics` category.
- **Server-side GA4 Data API client** — `Ga4DataClient::runReport()` with typed `ReportRequest` / `ReportResponse` DTOs, `DateRange` helper (730-day `MAX_DAYS` clamp), transparent OAuth via the base package's `TokenManager`, and a configurable response cache.
- **GA overview surface** — Livewire, React, and Vue components rendering sessions, users, page views, and average engagement time, backed by `GaOverviewFetcher` and `/analytics-google/overview`.
- **Top pages + top events surface** — Livewire, React, and Vue components rendering top pages and top events, backed by `GaTopContentFetcher` and `/analytics-google/top-content`. Limit clamped to `1..100`.
- **CMS framework admin widgets** — `analytics-google-overview` and `analytics-google-top-content` register automatically alongside `artisanpack-ui/cms-framework`, guarded behind `view_analytics`.
- **Scope registration** — contributes `analytics.readonly` to the `artisanpack-ui/google` scope registry via the `ap.google.scopes` filter hook.
- **Graceful degradation without the base** — `Ga4DataClient::isAvailable()` returns `false`, `runReport()` throws `BaseNotInstalledException`, reporting components render an "install the base" prompt, and HTTP endpoints return `501 base_not_installed`.
- **`GoogleConnectionResolver`** — shared singleton for looking up the current user's connection; rebind to override behavior for both Livewire components and both HTTP controllers uniformly.
- **HTTP error contract** — `401 unauthenticated`, `409 not_connected`, `501 base_not_installed`, `502 reporting_error`.
- **Comprehensive documentation** — full `docs/` tree.
- **Laravel 10, 11, 12, and 13 support** — CI matrix over PHP 8.2 / 8.3 / 8.4 × Laravel 12 / 13.

[Unreleased]: https://github.com/ArtisanPack-UI/analytics-google/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/ArtisanPack-UI/analytics-google/releases/tag/v1.0.0
