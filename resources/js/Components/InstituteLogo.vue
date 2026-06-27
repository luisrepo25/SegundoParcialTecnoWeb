<script setup>
defineProps({
    size: { type: Number, default: 42 },
});
</script>

<template>
    <img
        src="/images/logo.png"
        alt="Instituto San Pablo del Oriente"
        :style="{ width: size + 'px', height: size + 'px', objectFit: 'contain' }"
    />
</template>
