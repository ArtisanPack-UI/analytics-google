<?php

declare( strict_types=1 );

use ArtisanPackUI\AnalyticsGoogle\Http\Controllers\GaTopContentController;
use ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled;
use Illuminate\Http\Request;

it( 'returns 501 for top-content when the base package is reported absent', function (): void {
    BaseInstalled::setForTesting( false );

    $response = app( GaTopContentController::class )( Request::create( '/analytics-google/top-content' ) );

    expect( $response->status() )->toBe( 501 );
    expect( $response->getData( true ) )->toMatchArray( [
        'error'         => 'base_not_installed',
        'baseInstalled' => false,
    ] );
} );

it( 'returns 401 when no user is authenticated on the top-content request', function (): void {
    BaseInstalled::setForTesting( true );

    $response = app( GaTopContentController::class )( Request::create( '/analytics-google/top-content' ) );

    expect( $response->status() )->toBe( 401 );
    expect( $response->getData( true )['error'] ?? '' )->toBe( 'unauthenticated' );
} );
