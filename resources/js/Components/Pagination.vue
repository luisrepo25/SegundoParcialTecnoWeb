<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    data: { type: Object, required: true },
    label: { type: String, default: 'registros' },
});

// Genera la lista de páginas con ellipsis intercalados
const pages = computed(() => {
    const current = props.data.current_page;
    const last = props.data.last_page;
    if (last <= 1) return [];

    const visible = new Set([1, last]);
    for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
        visible.add(i);
    }

    const sorted = [...visible].sort((a, b) => a - b);
    const result = [];
    let prev = null;

    for (const p of sorted) {
        if (prev !== null && p - prev > 1) {
            result.push({ type: 'ellipsis', key: `e${p}` });
        }
        result.push({ type: 'page', page: p, active: p === current, key: `p${p}` });
        prev = p;
    }

    return result;
});

function getPageUrl(page) {
    const link = (props.data.links ?? []).find(l => l.label == String(page) && l.url);
    if (link?.url) return link.url;
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        return url.toString();
    } catch {
        return '#';
    }
}

function navigate(page) {
    const url = getPageUrl(page);
    if (url && url !== '#') router.get(url);
}
</script>

<template>
    <div v-if="data.last_page > 1"
         class="flex flex-col sm:flex-row items-center justify-between gap-2 px-4 py-3 border-t"
         style="border-color: var(--border-color);">

        <p class="text-sm" style="color: var(--text-secondary);">
            Mostrando {{ data.from }}–{{ data.to }} de {{ data.total }} {{ label }}
        </p>

        <div class="flex items-center gap-1 flex-wrap">
            <!-- Anterior -->
            <button
                @click="navigate(data.current_page - 1)"
                :disabled="!data.prev_page_url"
                class="px-3 py-1 rounded text-sm border transition"
                :style="!data.prev_page_url
                    ? 'opacity:0.4; cursor:default; border-color:var(--border-color); color:var(--text-secondary); background-color:var(--card-bg);'
                    : 'background-color:var(--card-bg); color:var(--text-color); border-color:var(--border-color);'">
                ← Anterior
            </button>

            <template v-for="item in pages" :key="item.key">
                <span v-if="item.type === 'ellipsis'"
                      class="px-2 py-1 text-sm select-none"
                      style="color: var(--text-secondary);">…</span>
                <button v-else
                    @click="navigate(item.page)"
                    class="px-3 py-1 rounded text-sm border transition min-w-[2.25rem]"
                    :style="item.active
                        ? 'background-color:var(--primary-color); color:var(--primary-text); border-color:var(--primary-color); font-weight:700;'
                        : 'background-color:var(--card-bg); color:var(--text-color); border-color:var(--border-color);'">
                    {{ item.page }}
                </button>
            </template>

            <!-- Siguiente -->
            <button
                @click="navigate(data.current_page + 1)"
                :disabled="!data.next_page_url"
                class="px-3 py-1 rounded text-sm border transition"
                :style="!data.next_page_url
                    ? 'opacity:0.4; cursor:default; border-color:var(--border-color); color:var(--text-secondary); background-color:var(--card-bg);'
                    : 'background-color:var(--card-bg); color:var(--text-color); border-color:var(--border-color);'">
                Siguiente →
            </button>
        </div>
    </div>
</template>
