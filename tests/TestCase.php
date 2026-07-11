<?php

declare( strict_types=1 );

namespace Tests;

use ArtisanPackUI\AnalyticsGoogle\AnalyticsGoogleServiceProvider;
use ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled;
use ArtisanPackUI\Google\GoogleServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * Base test case for the AnalyticsGoogle package.
 *
 * @since 1.0.0
 */
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        BaseInstalled::reset();
    }

    protected function tearDown(): void
    {
        BaseInstalled::reset();

        parent::tearDown();
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders( $app ): array
    {
        return [
            GoogleServiceProvider::class,
            AnalyticsGoogleServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment( $app ): void
    {
        $app[ 'config' ]->set( 'app.key', 'base64:' . base64_encode( random_bytes( 32 ) ) );

        $app[ 'config' ]->set( 'database.default', 'testbench' );
        $app[ 'config' ]->set( 'database.connections.testbench', [
            'driver'                  => 'sqlite',
            'database'                => ':memory:',
            'prefix'                  => '',
            'foreign_key_constraints' => true,
        ] );
    }
}
