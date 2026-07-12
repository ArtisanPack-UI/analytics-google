---
title: Google Cloud Setup
---

# Google Cloud Setup

Server-side reporting queries the **GA4 Data API** on a Google Cloud project. This page walks through the GA4-specific setup that sits on top of the base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package's Cloud Console walkthrough — the base package owns the OAuth client, this package adds an API and a scope.

If you haven't already, follow the base package's [Google Cloud setup](Installation-Google-Cloud-Setup) first to create the OAuth client. Everything below assumes that's done.

## 1. Enable the Google Analytics Data API

In the Cloud Console:

1. Open **APIs & Services → Library**.
2. Search for **Google Analytics Data API**.
3. Click **Enable**.

Enabling the API is free. Billing on the project is only relevant if you exceed the [Data API free quota](https://developers.google.com/analytics/devguides/reporting/data/v1/quotas).

## 2. Add the `analytics.readonly` scope to the consent screen

Under **APIs & Services → OAuth consent screen → Scopes**, add:

```
https://www.googleapis.com/auth/analytics.readonly
```

This is the exact scope this package registers via the `ap.google.scopes` filter hook. If the scope isn't listed on the consent screen, Google will reject the authorize request with `invalid_scope` — even though the code adds it correctly.

## 3. Find your GA4 property ID

Server-side reporting targets a specific GA4 property. In [Google Analytics](https://analytics.google.com/):

1. Open the property you want to query.
2. Click **Admin** (gear icon, bottom-left).
3. Under **Property** → **Property details**, note the **PROPERTY ID** — a numeric string like `123456789`.

Save this as `GA4_PROPERTY_ID` in `.env`:

```env
GA4_PROPERTY_ID=123456789
```

Not the measurement ID (`G-XXXXXXX`) — that one is for [client-side tracking](Client-Side-Tracking) only. GA4 uses two distinct IDs on purpose, and mixing them up is the single most common config bug.

## 4. Find your GA4 measurement ID (tracking only)

For client-side tracking, you need the measurement ID instead. In GA4:

1. **Admin → Property → Data Streams**.
2. Click your **Web** stream.
3. Copy the **Measurement ID** — starts with `G-`.

Save as `GA4_MEASUREMENT_ID`:

```env
GA4_MEASUREMENT_ID=G-XXXXXXX
```

## 5. Grant access on the GA4 property

The Google account that will connect via OAuth (the "connected user") needs at least **Viewer** access on the GA4 property to run reports.

In GA4 → **Admin → Property → Property access management**:

- Click **+** in the top right.
- Add the Google account email.
- Assign the **Viewer** role.

Users without property access will hit an HTTP 403 from the Data API when the app runs its first report — which the package surfaces as [`ReportingException::apiError()`](API-Reference-Exceptions) with a `PERMISSION_DENIED` message body.

## 6. Verify the connection

Once the base package's OAuth flow is set up and the user has connected:

```php
use ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Reporting\ReportRequest;

$client   = app( Ga4DataClient::class );
$response = $client->runReport(
    ReportRequest::make(
        range: DateRange::lastDays( 7 ),
        metrics: [ 'sessions' ],
    ),
    $user->googleConnection,
);

dd( $response->totalFor( 'sessions' ) );
```

A number (even `0`) means the whole pipeline works. An exception surfaces which step failed — see [Graceful Degradation](Server-Side-Reporting-Graceful-Degradation) for the map from exception to fix.

## Troubleshooting

- **`redirect_uri_mismatch`** on the connect step — a base package OAuth client issue; see the base's [Google Cloud setup](Installation-Google-Cloud-Setup).
- **`invalid_scope`** — you didn't add `analytics.readonly` in step 2.
- **HTTP 403 with `PERMISSION_DENIED`** — the connected user doesn't have GA4 property access (step 5) or the Data API isn't enabled (step 1).
- **HTTP 404 with `Property … does not exist`** — the property ID is wrong. Double-check the numeric ID from step 3.
