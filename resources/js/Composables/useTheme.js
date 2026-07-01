// resources/js/Composables/useTheme.js
import { ref } from 'vue';

const theme = ref('adults');
const contrast = ref('normal');
const fontScale = ref(1.0);
const isDark = ref(false);
const isInitialized = ref(false);

export function useTheme() {

    const applyToDOM = () => {
        if (typeof document === 'undefined') return;
        const html = document.documentElement;
        html.setAttribute('data-theme', theme.value);
        html.setAttribute('data-contrast', contrast.value);
        html.setAttribute('data-darkmode', isDark.value ? 'on' : 'off');
        html.style.setProperty('--font-scale', fontScale.value);
    };

    const isNightTime = () => {
        const hour = new Date().getHours();
        return hour >= 19 || hour < 6;
    };

    const initialize = () => {
        if (isInitialized.value || typeof window === 'undefined') return;

        const savedTheme = localStorage.getItem('isp-theme');
        theme.value = savedTheme ?? 'adults';

        const savedDark = localStorage.getItem('isp-darkmode');
        if (savedDark !== null) {
            isDark.value = savedDark === 'on';
        } else {
            // Auto: noche → dark mode ON, día → dark mode OFF
            isDark.value = isNightTime();
            // Si era de noche y no hay tema guardado, arrancar con youth por compatibilidad
            if (!savedTheme && isDark.value) theme.value = 'youth';
        }

        const savedContrast = localStorage.getItem('isp-contrast');
        if (savedContrast) contrast.value = savedContrast;

        const savedFontScale = localStorage.getItem('isp-fontScale');
        if (savedFontScale) fontScale.value = parseFloat(savedFontScale);

        applyToDOM();
        isInitialized.value = true;
    };

    const setTheme = (newTheme) => {
        theme.value = newTheme;
        localStorage.setItem('isp-theme', newTheme);
        applyToDOM();
    };

    const toggleDarkMode = () => {
        isDark.value = !isDark.value;
        localStorage.setItem('isp-darkmode', isDark.value ? 'on' : 'off');
        applyToDOM();
    };

    const toggleContrast = () => {
        contrast.value = contrast.value === 'high' ? 'normal' : 'high';
        localStorage.setItem('isp-contrast', contrast.value);
        applyToDOM();
    };

    const changeFontScale = (direction) => {
        let newScale = fontScale.value;
        if (direction === 'increase') newScale = Math.min(newScale + 0.1, 2.0);
        else if (direction === 'decrease') newScale = Math.max(newScale - 0.1, 0.8);
        else newScale = 1.0;
        fontScale.value = parseFloat(newScale.toFixed(1));
        localStorage.setItem('isp-fontScale', fontScale.value);
        applyToDOM();
    };

    if (typeof window !== 'undefined') {
        initialize();
    }

    return {
        theme,
        contrast,
        fontScale,
        isDark,
        setTheme,
        toggleDarkMode,
        toggleContrast,
        changeFontScale,
    };
}
