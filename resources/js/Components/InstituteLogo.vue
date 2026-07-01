<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps({
    size: { type: Number, default: 42 },
});

const logoUrl = computed(() => {
    const sitio = usePage().props.sitio;
    return sitio?.logo_url ?? '/images/logo.png';
});
</script>

<template>
    <img
        :src="logoUrl"
        alt="Instituto San Pablo del Oriente"
        :style="{ width: size + 'px', height: size + 'px', objectFit: 'contain' }"
    />
</template>