<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import EstudianteLayout from '@/Layouts/EstudianteLayout.vue';

const props = defineProps({
    notas: { type: Array, default: () => [] },
});

const filtroNivel   = ref('');
const filtroMateria = ref('');

const niveles = computed(() => {
    const map = new Map();
    props.notas.forEach(n => {
        const k = n.numero_nivel ?? 0;
        if (!map.has(k)) map.set(k, n.nivel_nombre ?? (n.numero_nivel ? `Nivel ${n.numero_nivel}` : 'Módulos'));
    });
    return [...map.entries()].sort((a, b) => a[0] - b[0]).map(([numero_nivel, nombre]) => ({ numero_nivel, nombre }));
});

const materias = computed(() => {
    const map = new Map();
    props.notas.forEach(n => { if (!map.has(n.id_materia)) map.set(n.id_materia, n.materia_nombre); });
    return [...map.entries()].map(([id_materia, nombre]) => ({ id_materia, nombre }));
});

const notasFiltradas = computed(() => props.notas.filter(n =>
    (filtroNivel.value === '' || (n.numero_nivel ?? 0) === Number(filtroNivel.value)) &&
    (filtroMateria.value === '' || n.id_materia === Number(filtroMateria.value))
));

const promedio = computed(() => {
    const aprobadas = notasFiltradas.value.filter(n => n.estado === 'aprobado' && n.calificacion_final !== null);
    if (!aprobadas.length) return null;
    return (aprobadas.reduce((s, n) => s + Number(n.calificacion_final), 0) / aprobadas.length).toFixed(1);
});

const fmtFecha = (f) => f ? new Date(f + 'T12:00:00').toLocaleDateString('es-BO', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

const ESTADO = {
    aprobado:  { label: 'Aprobada',  color: '#22c55e' },
    reprobado: { label: 'Reprobada', color: '#ef4444' },
};
</script>

<template>
    <Head title="Historial de Notas" />
    <EstudianteLayout>
        <template #header>
            <span class="font-semibold text-lg" style="color: var(--text-color);">Historial de Notas</span>
        </template>

        <div class="space-y-5">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="rounded-xl p-4 text-center" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <p class="text-xs font-semibold uppercase" style="color: var(--text-secondary);">Materias registradas</p>
                    <p class="text-2xl font-bold" style="color: var(--text-color);">{{ notasFiltradas.length }}</p>
                </div>
                <div class="rounded-xl p-4 text-center" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <p class="text-xs font-semibold uppercase" style="color: var(--text-secondary);">Aprobadas</p>
                    <p class="text-2xl font-bold" style="color: #22c55e;">{{ notasFiltradas.filter(n => n.estado === 'aprobado').length }}</p>
                </div>
                <div class="rounded-xl p-4 text-center" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <p class="text-xs font-semibold uppercase" style="color: var(--text-secondary);">Promedio</p>
                    <p class="text-2xl font-bold" style="color: var(--text-color);">{{ promedio ?? '—' }}</p>
                </div>
            </div>

            <div class="rounded-xl p-4 flex flex-wrap items-center gap-3" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <label class="text-sm font-medium" style="color: var(--text-secondary);">Nivel:</label>
                <select v-model="filtroNivel" class="rounded-lg text-sm px-3 py-1.5"
                        style="background-color: var(--input-bg, var(--card-bg)); border: 1px solid var(--border-color); color: var(--text-color);">
                    <option value="">Todos</option>
                    <option v-for="n in niveles" :key="n.numero_nivel" :value="n.numero_nivel">{{ n.nombre }}</option>
                </select>

                <label class="text-sm font-medium ml-2" style="color: var(--text-secondary);">Materia:</label>
                <select v-model="filtroMateria" class="rounded-lg text-sm px-3 py-1.5"
                        style="background-color: var(--input-bg, var(--card-bg)); border: 1px solid var(--border-color); color: var(--text-color);">
                    <option value="">Todas</option>
                    <option v-for="m in materias" :key="m.id_materia" :value="m.id_materia">{{ m.nombre }}</option>
                </select>

                <button v-if="filtroNivel || filtroMateria" @click="filtroNivel = ''; filtroMateria = ''"
                        class="text-xs font-medium ml-auto px-3 py-1.5 rounded-lg"
                        style="background-color: color-mix(in srgb,var(--text-color) 8%,transparent); color: var(--text-secondary);">
                    Limpiar filtros
                </button>
            </div>

            <div v-if="!notasFiltradas.length" class="rounded-xl p-8 text-center"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <p class="font-medium" style="color: var(--text-color);">No hay notas registradas con estos filtros.</p>
            </div>

            <div v-else class="rounded-xl overflow-hidden" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <div class="divide-y" style="border-color: var(--border-color);">
                    <div v-for="n in notasFiltradas" :key="n.id_inscripcion" class="flex items-center gap-3 px-5 py-3.5 flex-wrap">
                        <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded shrink-0"
                              style="background-color: color-mix(in srgb,var(--text-color) 8%,transparent); color: var(--text-secondary);">
                            {{ n.materia_codigo }}
                        </span>
                        <span class="font-medium text-sm flex-1 min-w-[140px]" style="color: var(--text-color);">{{ n.materia_nombre }}</span>
                        <span class="text-xs shrink-0" style="color: var(--text-secondary);">{{ n.periodo_nombre }}</span>
                        <span class="text-xs shrink-0 hidden sm:inline" style="color: var(--text-secondary);">{{ fmtFecha(n.fecha_aprobacion ?? n.fecha_inscripcion) }}</span>
                        <span class="text-sm font-bold shrink-0 w-10 text-center" :style="`color: ${ESTADO[n.estado]?.color}`">
                            {{ n.calificacion_final ?? '—' }}
                        </span>
                        <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold w-24 text-center"
                              :style="`background-color: color-mix(in srgb, ${ESTADO[n.estado]?.color} 15%, transparent); color: ${ESTADO[n.estado]?.color};`">
                            {{ ESTADO[n.estado]?.label }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </EstudianteLayout>
</template>
