<?php

declare( strict_types=1 );

use ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange;

it( 'accepts YYYY-MM-DD calendar dates', function (): void {
    $range = new DateRange( '2026-01-01', '2026-01-31' );

    expect( $range->toArray() )->toBe( [
        'startDate' => '2026-01-01',
        'endDate'   => '2026-01-31',
    ] );
} );

it( 'accepts GA4 relative aliases', function (): void {
    $range = new DateRange( '30daysAgo', 'today' );

    expect( $range->toArray() )->toBe( [
        'startDate' => '30daysAgo',
        'endDate'   => 'today',
    ] );
} );

it( 'builds a lastDays range', function (): void {
    $range = DateRange::lastDays( 7 );

    expect( $range->toArray() )->toBe( [
        'startDate' => '6daysAgo',
        'endDate'   => 'today',
    ] );
} );

it( 'rejects an empty string', function (): void {
    expect( fn () => new DateRange( '', 'today' ) )->toThrow( InvalidArgumentException::class );
} );

it( 'rejects an unparseable calendar date', function (): void {
    expect( fn () => new DateRange( 'not a date', 'today' ) )->toThrow( InvalidArgumentException::class );
} );

it( 'rejects lastDays with a non-positive count', function (): void {
    expect( fn () => DateRange::lastDays( 0 ) )->toThrow( InvalidArgumentException::class );
} );

it( 'clamps lastDays to MAX_DAYS so an authenticated user cannot force a decade-wide query', function (): void {
    $range = DateRange::lastDays( 100000 );

    expect( $range->startDate )->toBe( ( DateRange::MAX_DAYS - 1 ) . 'daysAgo' );
    expect( $range->endDate )->toBe( 'today' );
} );
