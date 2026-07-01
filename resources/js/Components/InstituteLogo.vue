<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
defineProps({
    size: { type: Number, default: 42 },
});
const logoUrl = computed(() => {
    const sitio = usePage().props.sitio;
    return sitio?.logo_url ?? '/inf513/grupo07sc/SegundoParcialTecnoWeb/public/images/logo.png';
});
</script>
<template>
    <img
        :src="logoUrl"
        alt="Logo"
        :style="{ width: size + 'px', height: size + 'px', objectFit: 'contain' }"
    />
</template>