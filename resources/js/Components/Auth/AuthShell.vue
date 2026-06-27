<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="auth-screen">
        <div class="auth-shell">
            <section class="auth-visual">
                <Link href="/" class="auth-brand">
                    <span class="auth-brand-mark">ISP</span>
                    <span class="auth-brand-copy">
                        <strong>Instituto San Pablo del Oriente</strong>
                        <span>Acceso institucional por roles</span>
                    </span>
                </Link>

                <div class="auth-hero">
                    <slot name="hero">
                        <p class="auth-eyebrow">Plataforma académica</p>
                        <h1>{{ title }}</h1>
                        <p>{{ subtitle }}</p>
                    </slot>
                </div>

                <slot name="visual-footer" />
            </section>

            <main class="auth-panel">
                <slot />
            </main>
        </div>
    </div>
</template>