<?php

/**
 * AnalyticsGoogle package configuration.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

return [

    /*
    |--------------------------------------------------------------------------
    | Client-side tracking (gtag.js)
    |--------------------------------------------------------------------------
    |
    | Client-side GA4 tracking runs without the base google package because
    | it does not need an OAuth token. Provide a measurement ID (G-XXXXXXX)
    | and the `@ga4Snippet` Blade directive will emit the standard gtag.js
    | snippet on any page it is included on.
    |
    */

    'tracking' => [

        // The GA4 measurement ID (e.g. G-XXXXXXX).
        'measurement_id' => env( 'GA4_MEASUREMENT_ID' ),

        // Whether client-side tracking is enabled. Defaults to true when
        // a measurement ID is present.
        'enabled' => env( 'GA4_TRACKING_ENABLED', true ),

        // Additional `gtag('config', ...)` options serialized to JSON on the
        // page. Keep to primitive values (strings, bools, numbers).
        //
        // @var array<string, mixed>
        'config' => [
            'anonymize_ip' => true,
        ],

        // When true, the client-side tracker defers page_view events until
        // the analytics parent's consent banner grants the 'analytics'
        // category. When the parent is not installed, tracking fires
        // immediately since there is no consent gate to defer to.
        'respect_consent' => true,

        /*
        |----------------------------------------------------------------------
        | Server-side forwarding (Measurement Protocol)
        |----------------------------------------------------------------------
        |
        | When the analytics parent is installed and `google-ga4` is listed in
        | its `active_providers`, page views and events it collects are
        | relayed to GA4 over the Measurement Protocol.
        |
        | This needs an API secret in addition to the measurement ID above.
        | Create one in GA4: Admin -> Data Streams -> (your stream) ->
        | Measurement Protocol API secrets. Forwarding stays off until both
        | are present.
        |
        | Worth knowing before you rely on it: Measurement Protocol hits do
        | not carry the automatic attribution a gtag.js tag provides. Referrer,
        | geography, device and session stitching are weaker or absent unless
        | explicitly supplied, so reports built on forwarded data will not look
        | identical to reports from a client-side tag.
        |
        */

        // The GA4 Measurement Protocol API secret.
        'api_secret' => env( 'GA4_API_SECRET' ),

        // Whether to relay the parent's page views and events to GA4.
        // Requires both a measurement ID and an API secret.
        'server_side' => env( 'GA4_SERVER_SIDE_TRACKING', true ),

        // Absolute base used to build `page_location`, which GA4 expects to
        // be a full URL rather than a path. Defaults to the app URL.
        'page_location_base' => env( 'GA4_PAGE_LOCATION_BASE' ),

        // Request timeout in seconds for Measurement Protocol calls. Kept
        // short: this runs on the ingest request path, and GA4 being slow
        // must not become the host application being slow.
        'timeout' => 3,

        // Send to GA4's validation endpoint instead of the live one. The
        // validation endpoint reports payload problems that the live endpoint
        // silently accepts, so it is the only way to debug a payload that
        // "sends fine" and never appears in reports.
        'debug' => env( 'GA4_MEASUREMENT_PROTOCOL_DEBUG', false ),

    ],

    /*
    |--------------------------------------------------------------------------
    | Server-side reporting (GA4 Data API)
    |--------------------------------------------------------------------------
    |
    | Server-side reporting talks to the GA4 Data API using the OAuth token
    | held by the base google package. This side of the package requires
    | `artisanpack-ui/google` to be installed.
    |
    */

    'reporting' => [

        // The GA4 property ID that the Data API queries target
        // (numeric property ID, e.g. "123456789").
        'property_id' => env( 'GA4_PROPERTY_ID' ),

        // Base URL for the GA4 Data API.
        'api_base' => 'https://analyticsdata.googleapis.com/v1beta',

        // Request timeout in seconds for Data API calls.
        'timeout' => 30,

        // How long (in seconds) to cache Data API responses. Set to 0 to
        // disable caching.
        'cache_ttl' => 300,

    ],

    /*
    |--------------------------------------------------------------------------
    | Provider registration
    |--------------------------------------------------------------------------
    |
    | The name this package registers under with the `artisanpack-ui/analytics`
    | parent. When the parent is installed and this name is added to
    | `artisanpack.analytics.active_providers`, tracking will run through the
    | parent's consent gate.
    |
    */

    'provider_name' => 'google-ga4',

    /*
    |--------------------------------------------------------------------------
    | OAuth scopes
    |--------------------------------------------------------------------------
    |
    | The Google OAuth scopes this package requires. Contributed to the
    | shared google base package's ScopeRegistry via the `ap.google.scopes`
    | filter hook.
    |
    */

    'scopes' => [
        'https://www.googleapis.com/auth/analytics.readonly',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP routes
    |--------------------------------------------------------------------------
    |
    | Configures the endpoint that backs the React and Vue GA overview
    | components. When 'enabled' is false the routes are not registered,
    | which is the correct choice for headless / API-only apps.
    |
    */

    'routes' => [

        'enabled'    => true,
        'prefix'     => 'analytics-google',
        'middleware' => [ 'web', 'auth' ],

    ],

];
