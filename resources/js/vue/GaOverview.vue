<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
import {
    fetchGaOverview,
    GaOverviewError,
    type FetchOverviewOptions,
    type GaOverviewData,
} from '../shared/overview'

const props = withDefaults(
    defineProps<{
        initialDays?: number
        propertyId?: string | null
        baseUrl?: string
        fetchImpl?: FetchOverviewOptions['fetchImpl']
    }>(),
    { initialDays: 30, propertyId: null },
)

const RANGE_OPTIONS = [7, 14, 30, 90]

const days = ref<number>(props.initialDays)
const data = ref<GaOverviewData | null>(null)
const errorMessage = ref<string | null>(null)
const errorCode = ref<string | null>(null)
const loading = ref<boolean>(false)

let activeController: AbortController | null = null

async function load(nextDays: number) {
    activeController?.abort()
    const controller = new AbortController()
    activeController = controller

    loading.value = true
    errorMessage.value = null
    errorCode.value = null

    try {
        const result = await fetchGaOverview({
            days: nextDays,
            propertyId: props.propertyId,
            baseUrl: props.baseUrl,
            fetchImpl: props.fetchImpl,
            signal: controller.signal,
        })
        if (controller.signal.aborted) return
        data.value = result
    } catch (e) {
        if (controller.signal.aborted) return
        if (e instanceof GaOverviewError) {
            errorCode.value = e.code
            errorMessage.value = e.message
        } else if (e instanceof Error) {
            errorMessage.value = e.message
        }
    } finally {
        if (!controller.signal.aborted) {
            loading.value = false
        }
    }
}

onMounted(() => load(days.value))
onUnmounted(() => activeController?.abort())

watch(days, (next) => load(next))

function formatInt(value: number | undefined): string {
    return new Intl.NumberFormat().format(Math.round(value ?? 0))
}
function formatMinutes(value: number | undefined): string {
    return ((value ?? 0) / 60).toFixed(1)
}
function maxSessions(): number {
    return data.value?.trend.reduce((max, row) => Math.max(max, row.sessions), 0) || 1
}
</script>

<template>
    <div v-if="errorCode === 'base_not_installed'" class="ap-ga-overview__missing-base" role="alert">
        <p>{{ errorMessage }}</p>
        <pre><code>composer require artisanpack-ui/google</code></pre>
    </div>

    <div v-else class="ap-ga-overview">
        <header class="ap-ga-overview__header">
            <h2 class="ap-ga-overview__title">Google Analytics overview</h2>
            <label class="ap-ga-overview__range">
                <span class="ap-ga-overview__range-label">Range</span>
                <select v-model.number="days" :disabled="loading">
                    <option v-for="option in RANGE_OPTIONS" :key="option" :value="option">
                        Last {{ option }} days
                    </option>
                </select>
            </label>
        </header>

        <div v-if="errorMessage" class="ap-ga-overview__error" role="alert">
            {{ errorMessage }}
        </div>

        <template v-else>
            <dl class="ap-ga-overview__totals">
                <div class="ap-ga-overview__tile">
                    <dt>Sessions</dt>
                    <dd>{{ formatInt(data?.totals.sessions) }}</dd>
                </div>
                <div class="ap-ga-overview__tile">
                    <dt>Users</dt>
                    <dd>{{ formatInt(data?.totals.users) }}</dd>
                </div>
                <div class="ap-ga-overview__tile">
                    <dt>Page views</dt>
                    <dd>{{ formatInt(data?.totals.page_views) }}</dd>
                </div>
                <div class="ap-ga-overview__tile">
                    <dt>Avg. engagement</dt>
                    <dd>{{ formatMinutes(data?.totals.avg_engagement_seconds) }} min</dd>
                </div>
            </dl>

            <figure class="ap-ga-overview__chart" aria-label="Daily sessions and users trend">
                <ol v-if="data && data.trend.length > 0" class="ap-ga-overview__bars">
                    <li
                        v-for="row in data.trend"
                        :key="row.date"
                        class="ap-ga-overview__bar"
                        :style="{ '--ap-bar-height': `${Math.min(100, Math.round((row.sessions / maxSessions()) * 100))}%` }"
                        :title="`${row.date}: ${formatInt(row.sessions)} sessions`"
                    >
                        <span class="visually-hidden">
                            {{ row.date }} — {{ formatInt(row.sessions) }} sessions
                        </span>
                    </li>
                </ol>
                <p v-else class="ap-ga-overview__empty">No data for this range yet.</p>
            </figure>
        </template>
    </div>
</template>
