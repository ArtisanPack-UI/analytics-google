---
title: CMS Framework Widgets
---

# CMS Framework Admin Widgets

When [`artisanpack-ui/cms-framework`](https://github.com/ArtisanPack-UI/cms-framework) is installed, this package automatically registers both reporting components as admin dashboard widgets. End users can add them to their dashboard through the CMS's normal widget UI — no additional configuration needed.

## The two widgets

| Widget key | Wrapper class | Underlying Livewire component |
|---|---|---|
| `analytics-google-overview` | `ArtisanPackUI\AnalyticsGoogle\CmsFramework\GaOverviewWidget` | `<livewire:analytics-google::ga-overview />` |
| `analytics-google-top-content` | `ArtisanPackUI\AnalyticsGoogle\CmsFramework\GaTopContentWidget` | `<livewire:analytics-google::ga-top-content />` |

Each wrapper extends the underlying Livewire component and adds the CMS framework's `AdminWidgetInterface`. That means instantiating the widget through the CMS's `AdminWidgetManager` renders the exact same UI as the raw Livewire tag — so dashboard widgets and standalone embeds cannot drift.

## `getWidgetInfo()`

### `GaOverviewWidget`

```php
[
    'title'           => __( 'Google Analytics overview' ),
    'description'     => __( 'Sessions, users, page views, and average engagement time over a configurable date range.' ),
    'capability'      => 'view_analytics',
    'default_options' => [
        'days'       => 30,
        'propertyId' => null,
    ],
]
```

### `GaTopContentWidget`

```php
[
    'title'           => __( 'Top pages and events' ),
    'description'     => __( 'The busiest pages by view count and the most-fired events for a configurable date range.' ),
    'capability'      => 'view_analytics',
    'default_options' => [
        'days'       => 30,
        'limit'      => 10,
        'propertyId' => null,
    ],
]
```

## Capability gate

Both widgets are guarded behind the `view_analytics` capability. The CMS framework's `AdminWidgetManager` refuses to render the widget for a user who lacks the capability, so dashboards for lower-privilege roles simply omit the widget rather than surfacing an error.

Grant `view_analytics` to the roles that should see reporting on the dashboard. Rename the capability by extending the widget class and overriding `getWidgetInfo()`.

## Registration guards

The service provider skips widget registration when any of these are missing:

- `\ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Contracts\AdminWidgetInterface`
- `\ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Services\AdminWidgetManager`
- `\Livewire\Livewire`

The wrappers extend `GaOverview` / `GaTopContent`, which extend `Livewire\Component`, so Livewire is a hard requirement for this integration. Apps running the CMS framework without Livewire would fail to autoload the wrappers.

When both the CMS framework and Livewire are present, the widget registration also re-registers the wrappers as Livewire components under distinct names so the CMS dashboard renders them without conflicting with the standalone Livewire components:

- `analytics-google::cms-ga-overview-widget`
- `analytics-google::cms-ga-top-content-widget`

You should not use those names directly — they exist only so the CMS's `AdminWidgetManager` can render the wrapper subclasses. Use `analytics-google::ga-overview` / `analytics-google::ga-top-content` for standalone Livewire embeds.

## Related

- [GA Overview](Components-GA-Overview)
- [GA Top Content](Components-GA-Top-Content)
- The [`artisanpack-ui/cms-framework`](https://github.com/ArtisanPack-UI/cms-framework) admin widgets documentation covers how end users add widgets to their dashboards.
