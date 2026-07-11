---
title: API Reference
---

# API Reference

The public surface of `artisanpack-ui/analytics-google`.

Sub-pages by class or namespace:

- [[API Reference/Analytics Google|`AnalyticsGoogle` — the facade / helper root]]
- [[API Reference/GA4 Data Client|`Ga4DataClient`]]
- [[API Reference/Report Request|`ReportRequest`]]
- [[API Reference/Report Response|`ReportResponse`]]
- [[API Reference/Date Range|`DateRange`]]
- [[API Reference/GA Overview Fetcher|`GaOverviewFetcher`]]
- [[API Reference/GA Top Content Fetcher|`GaTopContentFetcher`]]
- [[API Reference/GA Overview Data|`GaOverviewData`]]
- [[API Reference/GA Top Content Data|`GaTopContentData`]]
- [[API Reference/GA4 Provider|`Ga4Provider` and adapter]]
- [[API Reference/Exceptions|Exceptions]]
- [[API Reference/Helpers|Helpers, facades, and the `Gtag` service]]

## The facade

```php
use ArtisanPackUI\AnalyticsGoogle\Facades\AnalyticsGoogle;

AnalyticsGoogle::gtag();        // Tracking\Gtag — the client-side snippet renderer
AnalyticsGoogle::provider();    // Providers\Ga4Provider — the analytics-parent provider
AnalyticsGoogle::dataClient();  // Reporting\Ga4DataClient|null — null when the base is missing
AnalyticsGoogle::hasReporting();// bool — safe feature check
```

Backed by the `analytics-google` singleton binding.

## The helper

```php
analyticsGoogle();  // AnalyticsGoogle — same singleton as the facade
ga4Snippet();       // string — the rendered gtag snippet, or '' when unconfigured
```

## Container bindings

The service provider registers:

| Abstract | Concrete | Scope |
|---|---|---|
| `analytics-google` | `AnalyticsGoogle` | Singleton |
| `Tracking\Gtag::class` | `Tracking\Gtag` | Singleton |
| `Providers\Ga4Provider::class` | `Providers\Ga4Provider` | Singleton |
| `Reporting\Ga4DataClient::class` | `Reporting\Ga4DataClient` (with `TokenManager` when the base is installed, `null` otherwise) | Singleton |
| `Support\GoogleConnectionResolver::class` | `Support\GoogleConnectionResolver` | Singleton |

Rebind any of these to customize behavior. The most common override is `GoogleConnectionResolver` for multi-property / multi-tenant apps — see [[API Reference/Analytics Google|`GoogleConnectionResolver`]].

## Namespace map

```
ArtisanPackUI\AnalyticsGoogle\
├── AnalyticsGoogle.php                          — the aggregator
├── AnalyticsGoogleServiceProvider.php
├── helpers.php                                  — analyticsGoogle(), ga4Snippet()
├── Facades\
│   └── AnalyticsGoogle.php                      — facade
├── Tracking\
│   └── Gtag.php                                 — client-side snippet renderer
├── Reporting\
│   ├── Ga4DataClient.php
│   ├── ReportRequest.php
│   ├── ReportResponse.php
│   ├── DateRange.php
│   ├── GaOverviewFetcher.php
│   ├── GaTopContentFetcher.php
│   ├── GaOverviewData.php
│   └── GaTopContentData.php
├── Providers\
│   ├── Ga4Provider.php                          — analytics parent provider
│   └── Ga4AnalyticsProviderAdapter.php          — parent-interface adapter
├── Livewire\
│   ├── GaOverview.php                           — <livewire:analytics-google::ga-overview />
│   └── GaTopContent.php                         — <livewire:analytics-google::ga-top-content />
├── CmsFramework\
│   ├── GaOverviewWidget.php                     — AdminWidget wrapper
│   └── GaTopContentWidget.php                   — AdminWidget wrapper
├── Http\
│   └── Controllers\
│       ├── GaOverviewController.php             — GET /analytics-google/overview
│       └── GaTopContentController.php           — GET /analytics-google/top-content
├── Support\
│   ├── BaseInstalled.php                        — memoized base-present check
│   └── GoogleConnectionResolver.php             — the connection lookup
└── Exceptions\
    ├── BaseNotInstalledException.php
    └── ReportingException.php
```

## Public routes

Reference: [[HTTP Endpoints]].

## Public views

- `analytics-google::livewire.ga-overview` — Livewire overview view.
- `analytics-google::livewire.ga-top-content` — Livewire top-content view.

Publish with `--tag=analytics-google-views`.

## Publish tags

| Tag | What it publishes |
|---|---|
| `analytics-google-config` | `config/analytics-google.php` |
| `analytics-google-views` | Livewire views to `resources/views/vendor/analytics-google/`. |
| `analytics-google-js` | `resources/js/` (React, Vue, shared TypeScript) → `resources/js/vendor/analytics-google/`. |

## Filter hooks

| Hook | Contract | Purpose |
|---|---|---|
| `ap.google.scopes` | Filter — receives and returns `array<int, string>` | Contributes `analytics-google.scopes` to the base package's `ScopeRegistry`. Fires from this package's service provider `boot()`; the base's `ScopeRegistry::all()` picks it up. |

## Livewire component names

| Name | Class | When registered |
|---|---|---|
| `analytics-google::ga-overview` | `Livewire\GaOverview` | When Livewire is installed. |
| `analytics-google::ga-top-content` | `Livewire\GaTopContent` | When Livewire is installed. |
| `analytics-google::cms-ga-overview-widget` | `CmsFramework\GaOverviewWidget` | When Livewire *and* the CMS framework are installed. |
| `analytics-google::cms-ga-top-content-widget` | `CmsFramework\GaTopContentWidget` | Same. |

## CMS framework widget keys

| Key | Wrapper class | When registered |
|---|---|---|
| `analytics-google-overview` | `CmsFramework\GaOverviewWidget` | When the CMS framework's `AdminWidgetManager` is available. |
| `analytics-google-top-content` | `CmsFramework\GaTopContentWidget` | Same. |

---
Continue to [[Testing]] →
