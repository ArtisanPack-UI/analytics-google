# ArtisanPack UI AnalyticsGoogle Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-08-09

### Added

- **Server-side GA4 forwarding over the Measurement Protocol.** `Ga4AnalyticsProviderAdapter::trackPageView()` and `::trackEvent()` were intentional no-ops, so adding `google-ga4` to the analytics parent's `active_providers` registered a provider that reported `isEnabled()` true and sent nothing, with no error and no warning — indistinguishable from a configuration problem in the consuming application. They now relay to GA4. Requires a `GA4_API_SECRET` alongside the measurement ID (GA4 → Admin → Data Streams → Measurement Protocol API secrets); forwarding stays off until both are present and can be disabled independently with `GA4_SERVER_SIDE_TRACKING=false`. ([#15](https://github.com/ArtisanPack-UI/analytics-google/issues/15))
- `MeasurementProtocol` client, taking primitives rather than the parent's DTOs so it is usable and testable whether or not the parent package is installed.
- `GA4_PAGE_LOCATION_BASE` for building the absolute `page_location` GA4 expects, defaulting to `app.url`. Sending a bare path leaves GA4's hostname and page-path reporting empty while the hit still succeeds.
- `GA4_MEASUREMENT_PROTOCOL_DEBUG` targets GA4's validation endpoint, which reports payload problems the live endpoint accepts silently.

### Changed

- `Ga4AnalyticsProviderAdapter::isEnabled()` now returns true when *either* the client-side tag or server-side forwarding is configured. The parent drops providers reporting `false` from `getActiveProviders()`, so answering on the tag alone would have silently disabled forwarding for anyone who turned the snippet off.
- `Ga4AnalyticsProviderAdapter::__construct()` takes an optional third `MeasurementProtocol` argument. It is optional and defaults to `null`, so existing instantiations keep working and simply do not forward.
- Event and parameter names are coerced to GA4's rules: letters, digits and underscores, **starting with a letter**, 40 characters. Names starting with a digit or underscore, or using the reserved `ga_` / `google_` / `firebase_` prefixes, get an `e_` prefix, and the length cap is applied afterwards. Event parameters are capped at GA4's limit of 25 and non-scalar values are dropped. GA4 discards non-conforming events silently, so a rejected name is indistinguishable from a working one without this.
- `session_id` is forwarded only when it matches GA4's required `^\d+$` and is non-zero. The parent's session identifiers are UUIDs, and sending one produces a rejected or mis-attributed hit rather than an error, so it is omitted and GA4 derives its own session. Forwarded hits therefore do not share session boundaries with the parent's dashboard.
- Validation messages are surfaced. GA4's debug endpoint answers `200` with a `validationMessages` array describing what it would reject; reading only the status code there made debug mode report nothing, which is the opposite of its purpose.
- Measurement Protocol calls fail soft: transport errors and non-2xx responses are logged and swallowed, with a short timeout. Forwarding runs on the ingest request path with a visitor waiting on a beacon response, so a GA4 outage must degrade to lost hits rather than to a slow or failing host application.

### Fixed

- The 25-parameter cap now holds once `session_id` is added. The cap was applied to the event properties and the session ID appended afterwards, so an event carrying 25 properties plus a valid numeric session ID left with 26 parameters — and GA4 discards over-limit events silently, the exact failure this release exists to remove. The session ID now claims a reserved slot.
- A custom property named `session_id` can no longer reach GA4 unvalidated. It went straight through the property loop, bypassing the `^\d+$` check that keeps a malformed session ID from getting the whole hit rejected.
- The GA4 API secret is redacted from logged transport errors. It travels in the query string per Google's spec, and Guzzle embeds the full request URI in its exception messages, so a timeout could write a credential that can post events to the property into the application log.
- `GA4_PAGE_LOCATION_BASE=` with no value falls back to `app.url` again. An empty string is not `null`, so the null-coalescing fallback did not fire and `page_location` was sent as a bare path — which leaves GA4's hostname and page-path reporting empty, the very thing the setting exists to prevent.

### Notes

- Client-side `gtag.js` and server-side forwarding are complementary, not alternatives — but anything both observe is counted twice. Run one or the other per event stream.
- The analytics parent ships its own Measurement-Protocol provider named `google`, configured through a separate `ANALYTICS_GOOGLE_*` env surface. Use one or the other; both against the same property double-counts.

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

[Unreleased]: https://github.com/ArtisanPack-UI/analytics-google/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/ArtisanPack-UI/analytics-google/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/ArtisanPack-UI/analytics-google/releases/tag/v1.0.0
