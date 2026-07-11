<?php

declare( strict_types=1 );

use ArtisanPackUI\AnalyticsGoogle\Reporting\ReportResponse;

it( 'unpacks a dimensioned response into associative rows', function (): void {
    $raw = [
        'dimensionHeaders' => [ [ 'name' => 'date' ] ],
        'metricHeaders'    => [
            [ 'name' => 'sessions' ],
            [ 'name' => 'totalUsers' ],
        ],
        'rows' => [
            [
                'dimensionValues' => [ [ 'value' => '20260101' ] ],
                'metricValues'    => [ [ 'value' => '100' ], [ 'value' => '80' ] ],
            ],
            [
                'dimensionValues' => [ [ 'value' => '20260102' ] ],
                'metricValues'    => [ [ 'value' => '150' ], [ 'value' => '120' ] ],
            ],
        ],
    ];

    $rows = ( new ReportResponse( $raw ) )->rows();

    expect( $rows )->toHaveCount( 2 )
        ->and( $rows[0] )->toBe( [
            'date'       => '20260101',
            'sessions'   => '100',
            'totalUsers' => '80',
        ] );
} );

it( 'sums totalFor across every row', function (): void {
    $raw = [
        'dimensionHeaders' => [ [ 'name' => 'date' ] ],
        'metricHeaders'    => [ [ 'name' => 'sessions' ] ],
        'rows'             => [
            [ 'dimensionValues' => [ [ 'value' => '2026-01-01' ] ], 'metricValues' => [ [ 'value' => '10' ] ] ],
            [ 'dimensionValues' => [ [ 'value' => '2026-01-02' ] ], 'metricValues' => [ [ 'value' => '25' ] ] ],
            [ 'dimensionValues' => [ [ 'value' => '2026-01-03' ] ], 'metricValues' => [ [ 'value' => '5' ] ] ],
        ],
    ];

    expect( ( new ReportResponse( $raw ) )->totalFor( 'sessions' ) )->toBe( 40.0 );
} );

it( 'handles empty and malformed responses without erroring', function (): void {
    expect( ( new ReportResponse( [] ) )->rows() )->toBe( [] )
        ->and( ( new ReportResponse( [ 'rows' => 'not-an-array' ] ) )->rows() )->toBe( [] )
        ->and( ( new ReportResponse( [] ) )->totalFor( 'sessions' ) )->toBe( 0.0 );
} );
