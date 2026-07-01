<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const visitas = computed(() => page.props.visitas_pagina ?? 0);
</script>

<template>
    <footer class="mt-auto py-3 px-4 border-t text-center text-xs"
        style="background-color: var(--nav-bg); border-color: var(--border-color); color: var(--text-secondary);">
        <span>
            Esta página ha sido visitada
            <strong style="color: var(--primary-color);">{{ visitas.toLocaleString('es-BO') }}</strong>
            {{ visitas === 1 ? 'vez' : 'veces' }}
        </span>
        <span class="mx-2 opacity-40">·</span>
        <span>Instituto San Pablo del Oriente</span>
    </footer>
</template>
