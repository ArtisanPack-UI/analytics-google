---
title: GaTopContentFetcher
---

# `GaTopContentFetcher`

`ArtisanPackUI\AnalyticsGoogle\Reporting\GaTopContentFetcher` — pulls the top pages by view count and the top events by count for the top-content surface. Shared by the Livewire component and the HTTP endpoint.

## Constants

- **`DEFAULT_LIMIT = 10`** — the default number of rows per list.

## Constructor

```php
public function __construct(
    protected Ga4DataClient $client,
)
```

## Methods

### `fetch( GoogleConnection $connection, DateRange $range, ?string $propertyId = null, int $limit = self::DEFAULT_LIMIT ): GaTopContentData`

Runs two `runReport` calls:

1. **Pages** — metrics `screenPageViews`, `totalUsers`. Dimensions: `pagePath`, `pageTitle`. Ordered by `screenPageViews` descending.
2. **Events** — metrics `eventCount`, `totalUsers`. Dimension: `eventName`. Ordered by `eventCount` descending.

The `$limit` is clamped internally to `1..100` — callers can pass any int without needing to defensively bound it.

Returns a [[API Reference/GA Top Content Data|`GaTopContentData`]] with `topPages` sorted by views descending and `topEvents` sorted by count descending.

**Throws** — every exception `Ga4DataClient::runReport()` throws.

## Example

```php
use ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Reporting\GaTopContentFetcher;

$client  = app( Ga4DataClient::class );
$fetcher = new GaTopContentFetcher( $client );
$data    = $fetcher->fetch( $connection, DateRange::lastDays( 7 ), null, 5 );

$data->topPages;   // list<{path, title, views, users}>
$data->topEvents;  // list<{event, count, users}>
```

## Related

- [[API Reference/GA Top Content Data|`GaTopContentData`]]
- [[Components/GA Top Content|GA Top Content component]]
