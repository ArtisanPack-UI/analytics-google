<?php

declare( strict_types=1 );

use ArtisanPackUI\AnalyticsGoogle\Http\Controllers\GaOverviewController;
use ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled;
use Illuminate\Http\Request;

/*
 * Exercises the HTTP endpoint that powers the React and Vue overview
 * components. We call the controller directly so tests are not
 * dependent on how / whether the parent app has mounted the route
 * group.
 */

it( 'returns 501 when the base package is reported absent', function (): void {
    BaseInstalled::setForTesting( false );

    $response = app( GaOverviewController::class )( Request::create( '/analytics-google/overview' ) );

    expect( $response->status() )->toBe( 501 );
    expect( $response->getData( true ) )->toMatchArray( [
        'error'         => 'base_not_installed',
        'baseInstalled' => false,
    ] );
} );

it( 'returns 401 when no user is authenticated on the request', function (): void {
    BaseInstalled::setForTesting( true );

    // Request::create() produces a request with no resolved user; the
    // controller's $request->user() then returns null.
    $response = app( GaOverviewController::class )( Request::create( '/analytics-google/overview' ) );

    expect( $response->status() )->toBe( 401 );
    expect( $response->getData( true )['error'] ?? '' )->toBe( 'unauthenticated' );
} );
