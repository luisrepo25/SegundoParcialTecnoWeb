<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';

const props = defineProps({
    modelValue:  [String, Number],
    options:     Array,   // [{ value, label }]
    placeholder: { type: String, default: '— Selecciona —' },
    emptyLabel:  { type: String, default: '' },   // si se pasa, muestra opción vacía con este label
});

const emit = defineEmits(['update:modelValue']);

const open       = ref(false);
const search     = ref('');
const triggerRef = ref(null);
const searchRef  = ref(null);
const dropStyle  = ref({});
const dropId     = 'combo-drop-' + Math.random().toString(36).slice(2);

const selectedLabel = computed(() => {
    if (props.modelValue === '' || props.modelValue === null || props.modelValue === undefined) {
        return '';
    }
    const opt = props.options?.find(o => String(o.value) === String(props.modelValue));
    return opt?.label ?? '';
});

const filtered = computed(() => {
    if (!props.options) return [];
    if (!search.value) return props.options;
    const q = search.value.toLowerCase();
    return props.options.filter(o => o.label.toLowerCase().includes(q));
});

function toggle() {
    if (open.value) { close(); return; }
    open.value  = true;
    search.value = '';
    nextTick(() => {
        positionDropdown();
        searchRef.value?.focus();
    });
}

function close() {
    open.value   = false;
    search.value = '';
}

function positionDropdown() {
    if (!triggerRef.value) return;
    const rect       = triggerRef.value.getBoundingClientRect();
    const dropHeight = 264;
    const spaceBelow = window.innerHeight - rect.bottom - 8;
    const spaceAbove = rect.top - 8;

    const style = {
        position: 'fixed',
        left:  Math.max(8, rect.left) + 'px',
        width: rect.width + 'px',
        zIndex: 99999,
    };

    if (spaceBelow >= dropHeight || spaceBelow >= spaceAbove) {
        style.top    = (rect.bottom + 4) + 'px';
        style.maxHeight = Math.min(dropHeight, spaceBelow - 4) + 'px';
    } else {
        style.bottom    = (window.innerHeight - rect.top + 4) + 'px';
        style.maxHeight = Math.min(dropHeight, spaceAbove - 4) + 'px';
    }

    dropStyle.value = style;
}

function select(value) {
    emit('update:modelValue', value);
    close();
}

function onClickOutside(e) {
    const drop = document.getElementById(dropId);
    if (!triggerRef.value?.contains(e.target) && !drop?.contains(e.target)) {
        close();
    }
}

function onKeydown(e) {
    if (e.key === 'Escape') close();
}

onMounted(() => {
    document.addEventListener('mousedown', onClickOutside);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onClickOutside);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div class="combo-root" ref="triggerRef" @click="toggle">
        <div class="combo-trigger" :class="{ open }">
            <span v-if="selectedLabel" class="combo-selected">{{ selectedLabel }}</span>
            <span v-else class="combo-placeholder">{{ placeholder }}</span>
            <svg class="combo-arrow" :class="{ rotated: open }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
            </svg>
        </div>
    </div>

    <Teleport to="body">
        <div v-if="open" :id="dropId" class="combo-dropdown" :style="dropStyle">
            <div class="combo-search-wrap">
                <input ref="searchRef" v-model="search" type="text"
                    class="combo-search" placeholder="Buscar..." @click.stop />
            </div>
            <ul class="combo-list">
                <li v-if="emptyLabel !== ''"
                    class="combo-item"
                    :class="{ active: modelValue === '' || modelValue === null || modelValue === undefined }"
                    @click.stop="select('')">
                    {{ emptyLabel }}
                </li>
                <li v-for="opt in filtered" :key="opt.value"
                    class="combo-item"
                    :class="{ active: String(opt.value) === String(modelValue) }"
                    @click.stop="select(opt.value)">
                    {{ opt.label }}
                </li>
                <li v-if="filtered.length === 0" class="combo-empty">Sin resultados</li>
            </ul>
        </div>
    </Teleport>
</template>

<style scoped>
.combo-root { position: relative; cursor: pointer; user-select: none; }

.combo-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    width: 100%;
    border-radius: 0.5rem;
    border: 1px solid var(--border-color);
    background-color: var(--bg-color);
    color: var(--text-color);
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    transition: border-color 0.15s;
    min-height: 2.25rem;
}
.combo-trigger.open { border-color: var(--primary-color); }

.combo-selected   { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.combo-placeholder { flex: 1; color: var(--text-secondary); opacity: 0.7; }

.combo-arrow {
    width: 1rem; height: 1rem; flex-shrink: 0;
    color: var(--text-secondary);
    transition: transform 0.15s;
}
.combo-arrow.rotated { transform: rotate(180deg); }

/* Dropdown — rendered in body via Teleport */
.combo-dropdown {
    background-color: var(--card-bg, #fff);
    border: 1px solid var(--border-color, #ddd);
    border-radius: 0.625rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.combo-search-wrap {
    padding: 0.5rem;
    border-bottom: 1px solid var(--border-color, #ddd);
}
.combo-search {
    width: 100%;
    border-radius: 0.375rem;
    border: 1px solid var(--border-color, #ddd);
    background-color: var(--bg-color, #f9f9f9);
    color: var(--text-color, #111);
    padding: 0.375rem 0.625rem;
    font-size: 0.8125rem;
    outline: none;
}
.combo-search:focus { border-color: var(--primary-color); }

.combo-list {
    overflow-y: auto;
    flex: 1;
    list-style: none;
    margin: 0;
    padding: 0.25rem 0;
}

.combo-item {
    padding: 0.5rem 0.875rem;
    font-size: 0.875rem;
    cursor: pointer;
    color: var(--text-color, #111);
    transition: background-color 0.1s;
}
.combo-item:hover { background-color: color-mix(in srgb, var(--primary-color) 10%, transparent); }
.combo-item.active {
    background-color: color-mix(in srgb, var(--primary-color) 15%, transparent);
    color: var(--primary-color);
    font-weight: 600;
}

.combo-empty {
    padding: 0.75rem 0.875rem;
    font-size: 0.8125rem;
    color: var(--text-secondary);
    text-align: center;
}
</style>
