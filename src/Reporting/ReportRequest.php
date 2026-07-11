<?php

/**
 * Immutable description of a GA4 runReport request.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Reporting;

/**
 * Builder-friendly value object describing the metrics, dimensions,
 * and date range a caller wants to pull from the GA4 Data API. The
 * shape maps directly onto the runReport POST body.
 *
 * @since 1.0.0
 */
final class ReportRequest
{
    /**
     * @param  list<DateRange>  $dateRanges
     * @param  list<string>  $metrics
     * @param  list<string>  $dimensions
     * @param  list<array{metric?: string, dimension?: string, desc?: bool}>  $orderBys
     * @param  int|null  $limit
     * @param  int|null  $offset
     */
    public function __construct(
        public readonly array $dateRanges,
        public readonly array $metrics,
        public readonly array $dimensions = [],
        public readonly array $orderBys = [],
        public readonly ?int $limit = null,
        public readonly ?int $offset = null,
    ) {
    }

    /**
     * Convenience constructor for the common single-range case.
     *
     * @since 1.0.0
     *
     * @param  list<string>  $metrics
     * @param  list<string>  $dimensions
     * @param  list<array{metric?: string, dimension?: string, desc?: bool}>  $orderBys
     */
    public static function make(
        DateRange $range,
        array $metrics,
        array $dimensions = [],
        array $orderBys = [],
        ?int $limit = null,
        ?int $offset = null,
    ): self {
        return new self(
            dateRanges: [ $range ],
            metrics: $metrics,
            dimensions: $dimensions,
            orderBys: $orderBys,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * Serialize to the JSON payload the GA4 Data API's runReport endpoint expects.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function toApiPayload(): array
    {
        $payload = [
            'dateRanges' => array_map( fn ( DateRange $r ): array => $r->toArray(), $this->dateRanges ),
            'metrics'    => array_map( static fn ( string $name ): array => [ 'name' => $name ], $this->metrics ),
        ];

        if ( [] !== $this->dimensions ) {
            $payload['dimensions'] = array_map(
                static fn ( string $name ): array => [ 'name' => $name ],
                $this->dimensions,
            );
        }

        if ( [] !== $this->orderBys ) {
            $payload['orderBys'] = array_values( array_filter( array_map(
                static function ( array $spec ): ?array {
                    $desc = (bool) ( $spec['desc'] ?? false );

                    if ( isset( $spec['metric'] ) ) {
                        return [ 'metric' => [ 'metricName' => (string) $spec['metric'] ], 'desc' => $desc ];
                    }

                    if ( isset( $spec['dimension'] ) ) {
                        return [ 'dimension' => [ 'dimensionName' => (string) $spec['dimension'] ], 'desc' => $desc ];
                    }

                    return null;
                },
                $this->orderBys,
            ) ) );
        }

        if ( null !== $this->limit ) {
            $payload['limit'] = $this->limit;
        }

        if ( null !== $this->offset ) {
            $payload['offset'] = $this->offset;
        }

        return $payload;
    }
}
