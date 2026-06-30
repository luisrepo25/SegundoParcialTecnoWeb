<script setup>
import { Head } from '@inertiajs/vue3';
import EstudianteLayout from '@/Layouts/EstudianteLayout.vue';

const props = defineProps({
    carrera: { type: Object, default: null },
    niveles: { type: Array,  default: () => [] },
});

const ESTADO = {
    aprobado:  { label: 'Aprobada',  icon: '✓', color: '#22c55e' },
    activo:    { label: 'Cursando',  icon: '⏳', color: '#f59e0b' },
    reprobado: { label: 'Reprobada', icon: '✕', color: '#ef4444' },
    pendiente: { label: '—',         icon: '',  color: '#9ca3af' },
};

const fmtFecha = (f) => f ? new Date(f + 'T12:00:00').toLocaleDateString('es-BO', { day: '2-digit', month: 'short', year: 'numeric' }) : '';
</script>

<template>
    <Head title="Mi Malla Académica" />
    <EstudianteLayout>
        <template #header>
            <span class="font-semibold text-lg" style="color: var(--text-color);">Mi Malla Académica</span>
        </template>

        <div class="space-y-5">

            <div v-if="!carrera" class="rounded-xl p-8 text-center"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <p class="font-medium" style="color: var(--text-color);">No tienes carrera asignada.</p>
            </div>

            <template v-else>
                <div class="rounded-xl px-5 py-4" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Carrera</p>
                    <p class="font-bold text-lg" style="color: var(--text-color);">{{ carrera.nombre }}</p>
                </div>

                <!-- Leyenda -->
                <div class="flex flex-wrap gap-3 text-xs">
                    <span v-for="(v, k) in ESTADO" :key="k" v-show="k !== 'pendiente'"
                          class="flex items-center gap-1.5 px-2.5 py-1 rounded-full font-medium"
                          :style="`background-color: color-mix(in srgb, ${v.color} 12%, transparent); color: ${v.color};`">
                        <span>{{ v.icon }}</span>{{ v.label }}
                    </span>
                    <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-full font-medium"
                          style="background-color: color-mix(in srgb, #9ca3af 12%, transparent); color: #6b7280;">
                        Pendiente
                    </span>
                </div>

                <div v-if="!niveles.length" class="rounded-xl p-8 text-center"
                     style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <p class="font-medium" style="color: var(--text-color);">Tu carrera todavía no tiene malla curricular cargada.</p>
                </div>

                <div v-else class="space-y-5">
                    <div v-for="nivel in niveles" :key="nivel.numero_nivel ?? 0">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold shrink-0"
                                 style="background-color: var(--primary-color); color: var(--primary-text);">
                                {{ nivel.numero_nivel || '★' }}
                            </div>
                            <h3 class="font-semibold text-sm" style="color: var(--text-color);">{{ nivel.nivel_nombre }}</h3>
                            <div class="flex-1 h-px" style="background-color: var(--border-color);"></div>
                        </div>

                        <div class="rounded-xl overflow-hidden" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                            <div class="divide-y" style="border-color: var(--border-color);">
                                <div v-for="mat in nivel.materias" :key="mat.id_materia"
                                     class="flex items-center gap-3 px-5 py-3.5">
                                    <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded shrink-0"
                                          style="background-color: color-mix(in srgb,var(--text-color) 8%,transparent); color: var(--text-secondary);">
                                        {{ mat.codigo }}
                                    </span>
                                    <span class="font-medium text-sm flex-1 min-w-0 truncate" style="color: var(--text-color);">
                                        {{ mat.nombre }}
                                    </span>
                                    <span v-if="mat.fecha" class="text-xs shrink-0 hidden sm:inline" style="color: var(--text-secondary);">
                                        {{ fmtFecha(mat.fecha) }}
                                    </span>
                                    <span v-if="mat.calificacion_final !== null" class="text-sm font-bold shrink-0 w-10 text-center"
                                          :style="`color: ${ESTADO[mat.estado]?.color ?? '#6b7280'}`">
                                        {{ mat.calificacion_final }}
                                    </span>
                                    <span class="shrink-0 flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold w-28 justify-center"
                                          :style="`background-color: color-mix(in srgb, ${ESTADO[mat.estado]?.color ?? '#9ca3af'} 15%, transparent); color: ${ESTADO[mat.estado]?.color ?? '#6b7280'};`">
                                        <span v-if="ESTADO[mat.estado]?.icon">{{ ESTADO[mat.estado].icon }}</span>
                                        {{ ESTADO[mat.estado]?.label ?? '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </EstudianteLayout>
</template>
