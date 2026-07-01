<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

const query    = ref('');
const results  = ref([]);
const loading  = ref(false);
const open     = ref(false);
const inputRef = ref(null);

let debounceTimer = null;

const colores = {
    'Carrera':    { bg: 'var(--primary-color)',  text: 'var(--primary-text)' },
    'Materia':    { bg: '#0d9488',               text: '#fff' },
    'Estudiante': { bg: '#7c3aed',               text: '#fff' },
    'Profesor':   { bg: '#b45309',               text: '#fff' },
};

function onInput() {
    clearTimeout(debounceTimer);
    if (query.value.length < 2) { results.value = []; open.value = false; return; }
    loading.value = true;
    debounceTimer = setTimeout(async () => {
        try {
            const url = route('buscar') + '?q=' + encodeURIComponent(query.value);
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            results.value = await res.json();
            open.value = results.value.length > 0;
        } catch {
            results.value = [];
        } finally {
            loading.value = false;
        }
    }, 300);
}

function seleccionar(item) {
    open.value = false;
    query.value = '';
    results.value = [];
    router.visit(item.url);
}

function cerrar(e) {
    if (!inputRef.value?.closest('.header-search-wrap')?.contains(e.target)) {
        open.value = false;
    }
}

onMounted(() => document.addEventListener('click', cerrar));
onUnmounted(() => document.removeEventListener('click', cerrar));
</script>

<template>
    <div class="header-search-wrap" style="position: relative; flex: 1; max-width: 340px;">

        <!-- Input -->
        <div style="position: relative; display: flex; align-items: center;">
            <svg style="position: absolute; left: 10px; width: 15px; height: 15px; color: var(--text-secondary); pointer-events: none;"
                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
            </svg>
            <input
                ref="inputRef"
                v-model="query"
                @input="onInput"
                @keydown.escape="open = false; query = ''"
                type="text"
                placeholder="Buscar carreras, materias, estudiantes..."
                style="
                    width: 100%;
                    padding: 6px 10px 6px 32px;
                    font-size: 0.8rem;
                    border-radius: 8px;
                    border: 1px solid var(--border-color);
                    background: var(--bg-color);
                    color: var(--text-color);
                    outline: none;
                    transition: border-color 0.15s;
                "
                @focus="open = results.length > 0"
            />
            <svg v-if="loading"
                 style="position: absolute; right: 10px; width: 14px; height: 14px; animation: spin 1s linear infinite; color: var(--primary-color);"
                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="30 70" />
            </svg>
        </div>

        <!-- Dropdown resultados -->
        <div v-if="open && results.length"
             style="
                position: absolute;
                top: calc(100% + 6px);
                left: 0;
                right: 0;
                z-index: 9999;
                border-radius: 10px;
                border: 1px solid var(--border-color);
                background: var(--card-bg);
                box-shadow: 0 8px 24px rgba(0,0,0,0.2);
                overflow: hidden;
                max-height: 380px;
                overflow-y: auto;
             ">
            <button
                v-for="(item, i) in results"
                :key="i"
                @click="seleccionar(item)"
                @mouseover="$event.currentTarget.style.backgroundColor='color-mix(in srgb, var(--primary-color) 8%, transparent)'"
                @mouseout="$event.currentTarget.style.backgroundColor='transparent'"
                style="width:100%;display:flex;align-items:center;gap:10px;padding:9px 12px;text-align:left;border:none;background:transparent;cursor:pointer;border-bottom:1px solid var(--border-color);transition:background 0.12s;"
            >
                <!-- Badge tipo -->
                <span style="
                    flex-shrink: 0;
                    font-size: 0.6rem;
                    font-weight: 700;
                    padding: 2px 7px;
                    border-radius: 99px;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    "
                    :style="{
                        backgroundColor: colores[item.tipo]?.bg ?? 'var(--primary-color)',
                        color: colores[item.tipo]?.text ?? 'var(--primary-text)',
                    }"
                >
                    {{ item.tipo }}
                </span>
                <!-- Texto -->
                <div style="flex: 1; min-width: 0;">
                    <p style="margin: 0; font-size: 0.82rem; font-weight: 600; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ item.texto }}
                    </p>
                    <p style="margin: 0; font-size: 0.7rem; color: var(--text-secondary);">{{ item.subtexto }}</p>
                </div>
                <!-- Flecha -->
                <svg style="flex-shrink: 0; width: 12px; height: 12px; color: var(--text-secondary);"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6" />
                </svg>
            </button>

            <p v-if="!results.length && !loading"
               style="padding: 12px; font-size: 0.78rem; color: var(--text-secondary); text-align: center; margin: 0;">
                Sin resultados para "{{ query }}"
            </p>
        </div>
    </div>
</template>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
