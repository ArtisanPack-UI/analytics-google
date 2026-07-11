---
title: Components
---

# Components

The package ships two reporting surfaces, each rendered in three frameworks:

| Surface | What it shows | Livewire | React | Vue |
|---|---|---|---|---|
| **GA Overview** | Sessions, users, page views, avg engagement + daily trend chart. | `<livewire:analytics-google::ga-overview />` | `<GaOverview />` | `<GaOverview />` |
| **Top Content** | Top pages by views + top events by count. | `<livewire:analytics-google::ga-top-content />` | `<GaTopContent />` | `<GaTopContent />` |

All three frameworks share one HTTP endpoint per surface (see [[HTTP Endpoints]]), so numbers cannot drift.

## Requirements

Both surfaces require:

- The base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package installed and a connected Google account for the current user.
- Server-side: `analytics-google.reporting.property_id` configured.
- Livewire surfaces additionally require [`livewire/livewire`](https://livewire.laravel.com/) `^3.6`.

When the base is missing, each surface renders an "install the base" prompt instead of blowing up. See [[Server-Side Reporting/Graceful Degradation|Graceful Degradation]].

## Component pages

- [[Components/GA Overview|GA Overview — Livewire, React, Vue]]
- [[Components/GA Top Content|GA Top Content — Livewire, React, Vue]]
- [[Components/CMS Framework Widgets|CMS Framework Admin Widgets]]

## Publishing the JS sources

For React and Vue, publish the source and point your bundler at the vendor-published copies:

```bash
php artisan vendor:publish --tag=analytics-google-js
```

The React and Vue components are shipped as source (`.tsx` / `.vue`), not as prebuilt assets — you compile them alongside your own app code.

## Publishing the Livewire views

```bash
php artisan vendor:publish --tag=analytics-google-views
```

Copies `livewire/ga-overview.blade.php` and `livewire/ga-top-content.blade.php` to `resources/views/vendor/analytics-google/`. Only needed if you want to customize the markup.

## Related

- [[HTTP Endpoints]] — response shapes, error codes, and query parameters.
- [[Server-Side Reporting]] — the fetchers behind every surface.
- [[Configuration]] — the `routes.*` keys that control endpoint mounting.
