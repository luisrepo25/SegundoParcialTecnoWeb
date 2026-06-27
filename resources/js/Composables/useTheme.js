// resources/js/Composables/useTheme.js
import { ref, watch, onMounted } from 'vue';

const theme = ref('adults');
const contrast = ref('normal');
const fontScale = ref(1.0);
const isInitialized = ref(false);

export function useTheme() {
    
    // Apply state to DOM
    const applyToDOM = () => {
        if (typeof document === 'undefined') return;
        
        const html = document.documentElement;
        html.setAttribute('data-theme', theme.value);
        html.setAttribute('data-contrast', contrast.value);
        html.style.setProperty('--font-scale', fontScale.value);
    };

    // Detección automática de Día/Noche según horario
    const checkAutoDayNight = () => {
        const hour = new Date().getHours();
        // Noche: de 7:00 PM a 6:00 AM.
        // Si es de noche, por defecto asignamos "youth" (tema oscuro), si es de día asignamos "adults".
        if (hour >= 19 || hour < 6) {
            return 'youth';
        }
        return 'adults';
    };

    const initialize = () => {
        if (isInitialized.value || typeof window === 'undefined') return;

        // Cargar preferencias guardadas, o aplicar tema automático por horario
        const savedTheme = localStorage.getItem('isp-theme');
        if (savedTheme) {
            theme.value = savedTheme;
        } else {
            theme.value = checkAutoDayNight();
        }

        const savedContrast = localStorage.getItem('isp-contrast');
        if (savedContrast) {
            contrast.value = savedContrast;
        }

        const savedFontScale = localStorage.getItem('isp-fontScale');
        if (savedFontScale) {
            fontScale.value = parseFloat(savedFontScale);
        }

        applyToDOM();
        isInitialized.value = true;
    };

    // Actions
    const setTheme = (newTheme) => {
        theme.value = newTheme;
        localStorage.setItem('isp-theme', newTheme);
        applyToDOM();
    };

    const toggleContrast = () => {
        contrast.value = contrast.value === 'high' ? 'normal' : 'high';
        localStorage.setItem('isp-contrast', contrast.value);
        applyToDOM();
    };

    const changeFontScale = (direction) => {
        let newScale = fontScale.value;
        if (direction === 'increase') {
            newScale = Math.min(newScale + 0.1, 2.0); // Máximo 200%
        } else if (direction === 'decrease') {
            newScale = Math.max(newScale - 0.1, 0.8); // Mínimo 80%
        } else {
            newScale = 1.0; // Reset
        }
        fontScale.value = parseFloat(newScale.toFixed(1));
        localStorage.setItem('isp-fontScale', fontScale.value);
        applyToDOM();
    };

    // Auto init on import if in browser, or onMounted
    if (typeof window !== 'undefined') {
        initialize();
    }

    return {
        theme,
        contrast,
        fontScale,
        setTheme,
        toggleContrast,
        changeFontScale,
        checkAutoDayNight
    };
}
