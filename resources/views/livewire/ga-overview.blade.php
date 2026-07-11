<div
    class="ap-ga-overview"
    wire:poll.60s="refresh"
>
    @unless ($baseInstalled)
        <div class="ap-ga-overview__missing-base" role="alert">
            <p>
                {{ __( 'The GA4 overview requires the base google package. Install it with:' ) }}
            </p>
            <pre><code>composer require artisanpack-ui/google</code></pre>
        </div>
    @else
        <header class="ap-ga-overview__header">
            <h2 class="ap-ga-overview__title">{{ __( 'Google Analytics overview' ) }}</h2>

            <label class="ap-ga-overview__range">
                <span class="ap-ga-overview__range-label">{{ __( 'Range' ) }}</span>
                <select wire:model.change="days">
                    <option value="7">{{ __( 'Last 7 days' ) }}</option>
                    <option value="14">{{ __( 'Last 14 days' ) }}</option>
                    <option value="30">{{ __( 'Last 30 days' ) }}</option>
                    <option value="90">{{ __( 'Last 90 days' ) }}</option>
                </select>
            </label>
        </header>

        @if ($errorMessage)
            <div class="ap-ga-overview__error" role="alert">
                {{ $errorMessage }}
            </div>
        @else
            <dl class="ap-ga-overview__totals">
                <div class="ap-ga-overview__tile">
                    <dt>{{ __( 'Sessions' ) }}</dt>
                    <dd>{{ number_format( $totals['sessions'] ?? 0 ) }}</dd>
                </div>
                <div class="ap-ga-overview__tile">
                    <dt>{{ __( 'Users' ) }}</dt>
                    <dd>{{ number_format( $totals['users'] ?? 0 ) }}</dd>
                </div>
                <div class="ap-ga-overview__tile">
                    <dt>{{ __( 'Page views' ) }}</dt>
                    <dd>{{ number_format( $totals['page_views'] ?? 0 ) }}</dd>
                </div>
                <div class="ap-ga-overview__tile">
                    <dt>{{ __( 'Avg. engagement' ) }}</dt>
                    <dd>
                        {{ number_format( ( $totals['avg_engagement_seconds'] ?? 0 ) / 60, 1 ) }}
                        {{ __( 'min' ) }}
                    </dd>
                </div>
            </dl>

            <figure
                class="ap-ga-overview__chart"
                aria-label="{{ __( 'Daily sessions and users trend' ) }}"
                data-trend="{{ json_encode( $trend, JSON_UNESCAPED_SLASHES ) }}"
            >
                @if (count( $trend ) > 0)
                    @php
                        $maxSessions = max( array_map( fn ( $row ) => (float) $row['sessions'], $trend ) ) ?: 1;
                    @endphp
                    <ol class="ap-ga-overview__bars" role="list">
                        @foreach ($trend as $row)
                            @php $height = min( 100, (int) round( $row['sessions'] / $maxSessions * 100 ) ); @endphp
                            <li
                                class="ap-ga-overview__bar"
                                style="--ap-bar-height: {{ $height }}%"
                                title="{{ $row['date'] }}: {{ number_format( $row['sessions'] ) }} sessions"
                            >
                                <span class="visually-hidden">
                                    {{ $row['date'] }} — {{ number_format( $row['sessions'] ) }} sessions
                                </span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="ap-ga-overview__empty">{{ __( 'No data for this range yet.' ) }}</p>
                @endif
            </figure>
        @endif
    @endunless
</div>
