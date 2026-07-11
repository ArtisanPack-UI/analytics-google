---
title: Requirements
---

# Requirements

## PHP

- **PHP 8.2 or newer.** The package uses constructor property promotion, readonly properties, and typed enums throughout.

## Laravel

- **Laravel 10.x, 11.x, 12.x, or 13.x.** The package requires `illuminate/support`, `illuminate/config`, `illuminate/http`, `illuminate/cache`, and `illuminate/routing` on the matching majors. The service provider is auto-discovered by Laravel's package discovery on every supported version.

## Required ArtisanPack packages

- **`artisanpack-ui/core` ^1.0** — pulled in transitively as a hard dependency.

## Optional peer packages

| Package | Version | What it enables |
|---|---|---|
| [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) | `^1.0` | Server-side reporting. Provides the OAuth token manager and `GoogleConnection` model that `Ga4DataClient` reads from. **Without it, tracking still works but reporting throws [[API Reference/Exceptions|`BaseNotInstalledException`]].** |
| [`artisanpack-ui/hooks`](https://github.com/ArtisanPack-UI/hooks) | `^1.2` | Required only if you want the package to contribute the `analytics.readonly` scope to the base's [[Server-Side Reporting|`ap.google.scopes`]] filter hook. Pulled in transitively by the base — you rarely install it explicitly. |
| [`artisanpack-ui/analytics`](https://github.com/ArtisanPack-UI/analytics) | any | Registers this package as the `google-ga4` provider so tracking runs behind the parent's consent gate. See [[Analytics Parent Integration]]. |
| [`livewire/livewire`](https://livewire.laravel.com/) | `^3.6` | The `<livewire:analytics-google::ga-overview />` and `<livewire:analytics-google::ga-top-content />` components. |
| [`artisanpack-ui/cms-framework`](https://github.com/ArtisanPack-UI/cms-framework) | any | Registers the two reporting components as admin dashboard widgets. See [[Components/CMS Framework Widgets|CMS framework widgets]]. |
| [`guzzlehttp/guzzle`](https://github.com/guzzle/guzzle) | `^7.5` | Backs Laravel's HTTP client, used by `Ga4DataClient` to call the GA4 Data API. Laravel apps almost always have Guzzle installed already. |

None of these are strictly required. Each one unlocks a capability; missing peers are detected and skipped silently.

## Google Analytics 4

For **client-side tracking** you need:

- A GA4 property with a Web data stream that exposes a measurement ID of the form `G-XXXXXXX` (GA4 → Admin → Data Streams → your web stream).

For **server-side reporting** you need, in addition:

- The numeric GA4 **property ID** (GA4 → Admin → Property Settings).
- The **Google Analytics Data API** enabled on the Google Cloud project backing your OAuth client.
- An OAuth-connected Google account with at least **Viewer** access on the GA4 property, granted the `https://www.googleapis.com/auth/analytics.readonly` scope during consent.

Full walkthrough: [[Installation/Google Cloud Setup|Google Cloud setup]].

## Database

This package does not ship its own tables. Server-side reporting reads the `google_connections` table owned by the base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package.

## Cache

`Ga4DataClient` writes responses to Laravel's default cache store (`cache.store`) when `analytics-google.reporting.cache_ttl` is greater than zero. Any Laravel cache driver works — file, database, redis, memcached. Set the TTL to `0` in config to skip caching entirely (recommended in tests).

## Session driver

Not directly required by this package. The OAuth flow that produces a valid connection lives in the base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package, which needs a session driver for the OAuth `state` / PKCE `code_verifier` handshake.

## Frontend runtime (JS components only)

The React and Vue components are shipped as source (`.tsx` / `.vue`). If you use them:

- **React**: 18+ (uses `useEffect`, `useState`, `useCallback`, `AbortController`).
- **Vue**: 3.4+ (uses the Composition API with `<script setup>`).
- A bundler (Vite, Webpack, Turbopack, …) that can compile TS/TSX/Vue files.

The shared TypeScript clients under `resources/js/shared/` use only browser-native `fetch` and `AbortController` APIs; no runtime peer beyond React or Vue itself is required. `package.json` declares both frameworks as optional peers so installing only one does not warn.

The base package works without a frontend build — Livewire renders the tracker and both reporting components server-side.
