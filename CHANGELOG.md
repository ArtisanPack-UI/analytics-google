# ArtisanPack UI AnalyticsGoogle Changelog

## Unreleased

- Initial scaffold from the ArtisanPack UI package blueprint.
- Client-side GA4 tracking via the `@ga4Snippet` Blade directive plus React and Vue equivalents.
- Server-side GA4 Data API client (`Ga4DataClient`) with typed `ReportRequest` / `ReportResponse` DTOs.
- GA overview surface (Livewire + React + Vue) rendering sessions, users, page views, and average engagement time with a daily trend chart.
- Registers the package as a `google-ga4` provider with the `artisanpack-ui/analytics` parent when installed, gating tracking behind the parent's consent banner.
- Contributes `analytics.readonly` to the `artisanpack-ui/google` scope registry via the `ap.google.scopes` filter hook.
- Graceful degradation when `artisanpack-ui/google` is absent — tracking side stays available, reporting throws a well-typed `BaseNotInstalledException`.
- Supports Laravel 10, 11, 12, and 13.
