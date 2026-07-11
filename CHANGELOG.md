# ArtisanPack UI AnalyticsGoogle Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-07-11

Initial release of the Google Analytics 4 (GA4) integration for the ArtisanPack UI ecosystem. Ships two independent surfaces — client-side `gtag.js` tracking and server-side GA4 Data API reporting — either of which can be adopted on its own.

### Added

- **Client-side GA4 tracking** — `@ga4Snippet` Blade directive plus React (`<Ga4Snippet />`) and Vue equivalents emit the standard `gtag.js` snippet with escaped measurement IDs and safe defaults. Emits nothing when `tracking.measurement_id` is empty, so it's safe to leave in a shared layout across environments.
- **Consent-aware tracking** — when installed alongside `artisanpack-ui/analytics`, the package registers itself as the `google-ga4` provider and defers `page_view` events until the parent's consent banner grants the `analytics` category. Toggled via `tracking.respect_consent`; when the parent is absent, tracking fires immediately with no consent gate.
- **Server-side GA4 Data API client** — `Ga4DataClient::runReport()` POSTs to the GA4 Data API with typed `ReportRequest` / `ReportResponse` DTOs, `DateRange` helper (with a 730-day `MAX_DAYS` clamp), transparent OAuth via the base package's `TokenManager`, and a configurable response cache keyed on `sha256(property | connection identity | payload)`.
- **GA overview surface** — `<livewire:analytics-google::ga-overview />` plus React and Vue components render sessions, users, page views, and average engagement time for a configurable date range, backed by `GaOverviewFetcher` and the `/analytics-google/overview` HTTP endpoint.
- **Top pages + top events surface** — `<livewire:analytics-google::ga-top-content />` plus React and Vue equivalents render top pages (by page views) and top events (by count) via `GaTopContentFetcher` and the `/analytics-google/top-content` HTTP endpoint. Limit clamped to `1..100`, default `10`.
- **CMS framework admin widgets** — `analytics-google-overview` and `analytics-google-top-content` register automatically when `artisanpack-ui/cms-framework` is installed, both guarded behind the `view_analytics` capability.
- **Scope registration** — contributes `analytics.readonly` to the `artisanpack-ui/google` scope registry via the `ap.google.scopes` filter hook, so consent flows include the GA4 read scope automatically.
- **Graceful degradation without the base** — the service provider boots regardless of whether `artisanpack-ui/google` is installed. `Ga4DataClient::isAvailable()` returns `false` when the base is missing, `runReport()` throws a well-typed `BaseNotInstalledException` instead of a class-not-found fatal, reporting components render an "install the base" prompt, and HTTP endpoints return `501 base_not_installed`.
- **`GoogleConnectionResolver`** — shared singleton for looking up the current user's connection; rebind it to override behavior for both Livewire components and both HTTP controllers uniformly (useful for multi-tenant apps).
- **HTTP error contract** — controllers return `401 unauthenticated`, `409 not_connected`, `501 base_not_installed` (with a `baseInstalled: false` flag), and `502 reporting_error`, all consumed by the React/Vue components' error-code branch.
- **Comprehensive documentation** — full `docs/` tree covering installation, client-side tracking, server-side reporting, components, HTTP endpoints, analytics-parent integration, and a complete API reference.
- **Laravel 10, 11, 12, and 13 support** — CI matrix runs PHP 8.2 / 8.3 / 8.4 against Laravel 12 and 13.

[Unreleased]: https://github.com/ArtisanPack-UI/analytics-google/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/ArtisanPack-UI/analytics-google/releases/tag/v1.0.0
