<?php

declare( strict_types=1 );

/*
 * The CMS-framework wrapper classes DECLARE `implements
 * AdminWidgetInterface` at class-definition time. When the CMS
 * framework is not installed the interface is missing, so referencing
 * either wrapper via `use` or an unqualified type-hint would fatal
 * before the test could even skip. We resolve them via string
 * literals inside the test body and skip when the interface is not
 * autoloadable — matching how the ServiceProvider guards
 * registration in the real boot flow.
 */

beforeEach( function (): void {
    if ( ! interface_exists( 'ArtisanPackUI\\CMSFramework\\Modules\\AdminWidgets\\Contracts\\AdminWidgetInterface' ) ) {
        $this->markTestSkipped( 'cms-framework not installed in this test environment' );
    }
} );

it( 'implements the AdminWidget contract on both wrapper widgets', function (): void {
    expect( is_subclass_of( 'ArtisanPackUI\\AnalyticsGoogle\\CmsFramework\\GaOverviewWidget', 'ArtisanPackUI\\CMSFramework\\Modules\\AdminWidgets\\Contracts\\AdminWidgetInterface' ) )->toBeTrue()
        ->and( is_subclass_of( 'ArtisanPackUI\\AnalyticsGoogle\\CmsFramework\\GaTopContentWidget', 'ArtisanPackUI\\CMSFramework\\Modules\\AdminWidgets\\Contracts\\AdminWidgetInterface' ) )->toBeTrue();
} );

it( 'exposes widget info with a title, description, capability, and default options for both widgets', function (): void {
    $overview = ( 'ArtisanPackUI\\AnalyticsGoogle\\CmsFramework\\GaOverviewWidget' )::getWidgetInfo();
    $top      = ( 'ArtisanPackUI\\AnalyticsGoogle\\CmsFramework\\GaTopContentWidget' )::getWidgetInfo();

    expect( $overview )->toHaveKeys( [ 'title', 'description', 'capability', 'default_options' ] )
        ->and( $overview['default_options'] )->toMatchArray( [ 'days' => 30, 'propertyId' => null ] );

    expect( $top )->toHaveKeys( [ 'title', 'description', 'capability', 'default_options' ] )
        ->and( $top['default_options'] )->toMatchArray( [ 'days' => 30, 'limit' => 10, 'propertyId' => null ] );
} );
