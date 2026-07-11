<?php

declare( strict_types=1 );

use ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\ReportRequest;

it( 'serialises the basic runReport payload shape', function (): void {
    $request = ReportRequest::make(
        range: DateRange::lastDays( 30 ),
        metrics: [ 'sessions', 'totalUsers' ],
    );

    $payload = $request->toApiPayload();

    expect( $payload )->toHaveKey( 'dateRanges' )
        ->and( $payload['dateRanges'] )->toBe( [
            [ 'startDate' => '29daysAgo', 'endDate' => 'today' ],
        ] )
        ->and( $payload['metrics'] )->toBe( [
            [ 'name' => 'sessions' ],
            [ 'name' => 'totalUsers' ],
        ] )
        ->and( $payload )->not->toHaveKey( 'dimensions' );
} );

it( 'includes dimensions, limit, and offset when supplied', function (): void {
    $request = ReportRequest::make(
        range: new DateRange( '2026-01-01', '2026-01-31' ),
        metrics: [ 'sessions' ],
        dimensions: [ 'date' ],
        limit: 100,
        offset: 25,
    );

    $payload = $request->toApiPayload();

    expect( $payload['dimensions'] )->toBe( [ [ 'name' => 'date' ] ] )
        ->and( $payload['limit'] )->toBe( 100 )
        ->and( $payload['offset'] )->toBe( 25 );
} );
