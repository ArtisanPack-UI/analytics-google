<?php

declare( strict_types=1 );

use Illuminate\Support\Facades\Blade;

it( 'renders the @ga4Snippet directive to the gtag script', function (): void {
    config()->set( 'analytics-google.tracking.measurement_id', 'G-BLADE12345' );
    config()->set( 'analytics-google.tracking.respect_consent', false );

    $rendered = Blade::render( '@ga4Snippet' );

    expect( $rendered )->toContain( 'https://www.googletagmanager.com/gtag/js?id=G-BLADE12345' )
        ->and( $rendered )->toContain( "gtag('config','G-BLADE12345'" );
} );

it( 'renders @ga4Snippet as an empty string when no measurement ID is configured', function (): void {
    config()->set( 'analytics-google.tracking.measurement_id', null );

    expect( trim( Blade::render( '@ga4Snippet' ) ) )->toBe( '' );
} );
