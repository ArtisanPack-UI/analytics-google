<?php

/**
 * Parsed GA4 runReport response.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Reporting;

/**
 * Read-only wrapper over the raw JSON body returned by the GA4 Data
 * API's runReport endpoint. Exposes convenience accessors for the
 * common data-shape cases so callers do not need to walk deep nested
 * arrays.
 *
 * @since 1.0.0
 */
final class ReportResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly array $raw,
    ) {
    }

    /**
     * The dimension header names in the order they appear in each row.
     *
     * @since 1.0.0
     *
     * @return list<string>
     */
    public function dimensionHeaders(): array
    {
        $headers = $this->raw['dimensionHeaders'] ?? [];

        if ( ! is_array( $headers ) ) {
            return [];
        }

        return array_values( array_map(
            static fn ( array $header ): string => (string) ( $header['name'] ?? '' ),
            array_filter( $headers, 'is_array' ),
        ) );
    }

    /**
     * The metric header names in the order they appear in each row.
     *
     * @since 1.0.0
     *
     * @return list<string>
     */
    public function metricHeaders(): array
    {
        $headers = $this->raw['metricHeaders'] ?? [];

        if ( ! is_array( $headers ) ) {
            return [];
        }

        return array_values( array_map(
            static fn ( array $header ): string => (string) ( $header['name'] ?? '' ),
            array_filter( $headers, 'is_array' ),
        ) );
    }

    /**
     * Iterate the response rows as `[dimensionName => value, metricName => value]`
     * associative arrays for direct display use.
     *
     * @since 1.0.0
     *
     * @return list<array<string, string>>
     */
    public function rows(): array
    {
        $rows = $this->raw['rows'] ?? [];

        if ( ! is_array( $rows ) ) {
            return [];
        }

        $dimensionHeaders = $this->dimensionHeaders();
        $metricHeaders    = $this->metricHeaders();
        $result           = [];

        foreach ( $rows as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }

            $mapped = [];

            $dimensionValues = is_array( $row['dimensionValues'] ?? null ) ? $row['dimensionValues'] : [];

            foreach ( $dimensionHeaders as $index => $header ) {
                $mapped[ $header ] = (string) ( $dimensionValues[ $index ][ 'value' ] ?? '' );
            }

            $metricValues = is_array( $row['metricValues'] ?? null ) ? $row['metricValues'] : [];

            foreach ( $metricHeaders as $index => $header ) {
                $mapped[ $header ] = (string) ( $metricValues[ $index ][ 'value' ] ?? '' );
            }

            $result[] = $mapped;
        }

        return $result;
    }

    /**
     * Sum the values of a single metric across every row.
     *
     * Useful for pulling top-level totals like "sessions" out of a
     * dimensioned report without walking the raw shape.
     *
     * @since 1.0.0
     */
    public function totalFor( string $metric ): float
    {
        $total = 0.0;

        foreach ( $this->rows() as $row ) {
            if ( isset( $row[ $metric ] ) && is_numeric( $row[ $metric ] ) ) {
                $total += (float) $row[ $metric ];
            }
        }

        return $total;
    }
}
