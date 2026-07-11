---
title: Home
---

# ArtisanPack UI Analytics Google Documentation

Welcome to the documentation for **ArtisanPack UI Analytics Google** — the Google Analytics 4 (GA4) provider for Laravel applications. It ships two independent surfaces you can adopt separately: a client-side `gtag.js` tracker and a server-side GA4 Data API reporting client.

Use the navigation below to explore topics. Links use the GitLab wiki page style, so you can jump between pages like [[Getting Started]] or [[Server-Side Reporting]].

- [[Getting Started]]
- [[Installation]]
- [[Client-Side Tracking]]
- [[Server-Side Reporting]]
- [[Components]]
- [[HTTP Endpoints]]
- [[Analytics Parent Integration]]
- [[Configuration]]
- [[API Reference]]
- [[Testing]]
- [[FAQ]]
- [[Changelog]]
- [[Contributing]]

If you're new here, start with [[Getting Started]].

## What this package does

`artisanpack-ui/analytics-google` is the Google Analytics 4 provider for the ArtisanPack UI analytics ecosystem. It provides:

- **Client-side tracking** via the `@ga4Snippet` Blade directive and framework-native `Ga4Snippet` components for React and Vue. Emits the standard Google-hosted `gtag.js` snippet.
- **Server-side reporting** via a typed `Ga4DataClient` that hits the GA4 Data API's `runReport` endpoint. Ships value objects (`ReportRequest`, `ReportResponse`, `DateRange`) plus two ready-to-use fetchers (`GaOverviewFetcher`, `GaTopContentFetcher`).
- **Ready-to-drop reporting components** for Livewire, React, and Vue rendering a headline metrics overview and a top-pages-plus-top-events surface. All three frameworks share one HTTP endpoint per surface so numbers cannot drift.
- **Analytics parent integration** — registers itself as the `google-ga4` provider with [`artisanpack-ui/analytics`](https://github.com/ArtisanPack-UI/analytics) so tracking runs through the parent's consent gate rather than firing unconditionally.
- **CMS framework widgets** — automatically registers the overview and top-content components as admin dashboard widgets when [`artisanpack-ui/cms-framework`](https://github.com/ArtisanPack-UI/cms-framework) is installed.

## The two surfaces

The two surfaces have different dependencies and can be adopted independently:

| Surface | What it does | Requires the base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google)? |
|---|---|---|
| **Client-side tracking** | Emits the `gtag.js` snippet on your pages. | No |
| **Server-side reporting** | Queries the GA4 Data API for sessions, users, top pages, top events. | Yes — for the OAuth token. |

If you only need tracking, install this package alone. If you need dashboards, install the base too. See [[Installation]] for the full matrix and [[Server-Side Reporting/Graceful Degradation|Graceful Degradation]] for what happens when the base is missing.

## What this package does not do

- It does **not** implement the OAuth flow. That lives in the base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package — see its [[Getting Started]] guide.
- It does **not** ship a server-side event tracker. Client-side gtag.js emits events from the browser; if you need Measurement-Protocol-backed server events, use the provider that ships with [`artisanpack-ui/analytics`](https://github.com/ArtisanPack-UI/analytics).
- It does **not** own the consent banner. When the analytics parent is installed, tracking defers to its consent gate. When it isn't, `respect_consent` still guards the initial `page_view` via the browser-side `window.__apAnalyticsConsent` flag.
