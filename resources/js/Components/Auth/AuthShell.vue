<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

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
    <PublicLayout>
        <div class="auth-page">
            <div class="auth-page-inner">
                <div class="auth-head">
                    <slot name="hero">
                        <p class="auth-eyebrow"><span class="auth-eyebrow-dot"></span>Plataforma académica</p>
                        <h1 class="auth-head-title">{{ title }}</h1>
                        <p class="auth-head-sub">{{ subtitle }}</p>
                    </slot>
                </div>

                <slot />
            </div>
        </div>
    </PublicLayout>
</template>
