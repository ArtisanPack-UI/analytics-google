<script setup lang="ts">
import { onMounted } from 'vue'
import { installGa4Snippet, type Ga4Config } from '../shared/gtag'

const props = defineProps<{
    measurementId: string
    config?: Ga4Config
    respectConsent?: boolean
}>()

onMounted(() => {
    installGa4Snippet({
        measurementId: props.measurementId,
        config: props.config,
        respectConsent: props.respectConsent,
    })
})
</script>

<template>
    <!-- The snippet is injected imperatively into <head>; nothing to render. -->
</template>
