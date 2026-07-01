<script setup>
import { useTheme } from '@/Composables/useTheme';

const props = defineProps({
    // Cambia el color activo del pill con cualquier color CSS válido
    activeColor:    { type: String, default: 'var(--primary-color)' },
    activeTextColor:{ type: String, default: 'var(--primary-text)' },
    // Ocultar secciones si no se necesitan
    showFontControls:   { type: Boolean, default: true },
    showContrastToggle: { type: Boolean, default: true },
    // Modo compacto: solo iconos sin labels
    compact: { type: Boolean, default: false },
});

const { theme, contrast, isDark, setTheme, toggleDarkMode, toggleContrast, changeFontScale } = useTheme();

const themes = [
    { value: 'adults', label: 'Adultos' },
    { value: 'youth',  label: 'Jóvenes' },
    { value: 'kids',   label: 'Niños'   },
];
</script>

<template>
    <div class="flex items-center gap-2">

        <!-- Selector de tema: pill buttons -->
        <div class="flex items-center rounded-lg p-0.5 gap-0.5"
            style="background-color: var(--bg-color); border: 1px solid var(--border-color);">
            <button
                v-for="t in themes"
                :key="t.value"
                @click="setTheme(t.value)"
                :title="t.label"
                class="flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-all duration-200"
                :style="theme === t.value
                    ? `background-color: ${activeColor}; color: ${activeTextColor};`
                    : 'color: var(--text-secondary);'"
            >
                <span>{{ t.label }}</span>
            </button>
        </div>

        <!-- Tamaño de fuente -->
        <div v-if="showFontControls"
            class="flex items-center rounded-lg overflow-hidden"
            style="border: 1px solid var(--border-color); background-color: var(--bg-color);">
            <button @click="changeFontScale('decrease')"
                title="Reducir texto"
                class="px-2 py-1.5 text-xs font-bold transition-opacity hover:opacity-70"
                style="color: var(--text-secondary);">A−</button>
            <button @click="changeFontScale('reset')"
                title="Tamaño normal"
                class="px-2 py-1.5 text-sm font-bold transition-opacity hover:opacity-70"
                style="color: var(--text-color); border-left: 1px solid var(--border-color); border-right: 1px solid var(--border-color);">A</button>
            <button @click="changeFontScale('increase')"
                title="Aumentar texto"
                class="px-2 py-1.5 text-sm font-bold transition-opacity hover:opacity-70"
                style="color: var(--text-secondary);">A+</button>
        </div>

        <!-- Modo oscuro (día / noche) -->
        <button
            @click="toggleDarkMode"
            :title="isDark ? 'Cambiar a modo día' : 'Cambiar a modo noche'"
            class="flex items-center justify-center w-8 h-8 rounded-lg transition-all duration-200"
            :style="isDark
                ? `background-color: ${activeColor}; color: ${activeTextColor};`
                : 'background-color: var(--bg-color); color: var(--text-secondary); border: 1px solid var(--border-color);'">
            <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 3v1m0 16v1m8.66-9h-1M4.34 12h-1m15.07-6.07l-.71.71M5.64 18.36l-.71.71m12.02 0l-.71-.71M5.64 5.64l-.71-.71M12 7a5 5 0 100 10A5 5 0 0012 7z"/>
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </button>

        <!-- Alto contraste -->
        <button v-if="showContrastToggle"
            @click="toggleContrast"
            :title="contrast === 'high' ? 'Desactivar alto contraste' : 'Alto contraste'"
            class="flex items-center justify-center w-8 h-8 rounded-lg transition-all duration-200"
            :style="contrast === 'high'
                ? `background-color: ${activeColor}; color: ${activeTextColor};`
                : 'background-color: var(--bg-color); color: var(--text-secondary); border: 1px solid var(--border-color);'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3a9 9 0 100 18A9 9 0 0012 3zm0 0v18" />
            </svg>
        </button>

    </div>
</template>
