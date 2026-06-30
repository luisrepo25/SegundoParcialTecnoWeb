<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import EstudianteLayout from '@/Layouts/EstudianteLayout.vue';

const props = defineProps({
    matricula:    { type: Object, default: null },
    planCarrera:  { type: Object, default: null },
    pagosMateria: { type: Array,  default: () => [] },
});

const fmtFecha = (f) => f ? new Date(f + 'T12:00:00').toLocaleDateString('es-BO', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
const fmtMonto = (m) => m !== null && m !== undefined ? `Bs. ${Number(m).toFixed(2)}` : '—';

const ESTADO_COLOR = {
    pagado: '#22c55e', parcial: '#f59e0b', pendiente: '#f59e0b',
    reembolsado: '#ef4444', exonerado: '#3b82f6',
};

const totalPagado = computed(() => {
    let t = 0;
    if (props.matricula) t += Number(props.matricula.monto_pagado || 0);
    if (props.planCarrera) t += Number(props.planCarrera.monto_pagado || 0);
    t += props.pagosMateria.reduce((s, p) => s + Number(p.monto_pagado || 0), 0);
    return t;
});
</script>

<template>
    <Head title="Historial de Pagos" />
    <EstudianteLayout>
        <template #header>
            <span class="font-semibold text-lg" style="color: var(--text-color);">Historial de Pagos</span>
        </template>

        <div class="space-y-5">

            <div class="rounded-xl p-5 flex items-center justify-between"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Total pagado</p>
                    <p class="text-2xl font-bold" style="color: var(--text-color);">{{ fmtMonto(totalPagado) }}</p>
                </div>
                <span class="flex items-center justify-center w-12 h-12 rounded-lg shrink-0"
                      style="background-color: color-mix(in srgb, var(--primary-color) 12%, transparent);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" style="stroke: var(--primary-color);">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                    </svg>
                </span>
            </div>

            <!-- Matrícula -->
            <div>
                <h3 class="font-semibold text-sm mb-2" style="color: var(--text-color);">Matrícula Única</h3>
                <div class="rounded-xl p-5" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <div v-if="!matricula" class="text-sm" style="color: var(--text-secondary);">Aún no registras pago de matrícula.</div>
                    <div v-else class="flex items-center gap-3 flex-wrap">
                        <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold"
                              :style="`background-color: color-mix(in srgb, ${ESTADO_COLOR[matricula.estado] ?? '#9ca3af'} 15%, transparent); color: ${ESTADO_COLOR[matricula.estado] ?? '#6b7280'};`">
                            {{ matricula.estado }}
                        </span>
                        <span class="text-sm font-bold" style="color: var(--text-color);">{{ fmtMonto(matricula.monto_pagado) }}</span>
                        <span class="text-xs" style="color: var(--text-secondary);">{{ fmtFecha(matricula.fecha_pago) }}</span>
                        <span v-if="matricula.comprobante" class="text-xs ml-auto" style="color: var(--text-secondary);">Comprobante: {{ matricula.comprobante }}</span>
                    </div>
                </div>
            </div>

            <!-- Plan de carrera completa -->
            <div>
                <h3 class="font-semibold text-sm mb-2" style="color: var(--text-color);">Plan de Carrera Completa</h3>
                <div class="rounded-xl p-5" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <div v-if="!planCarrera" class="text-sm" style="color: var(--text-secondary);">No tienes un plan de pago de carrera completa contratado.</div>
                    <div v-else class="space-y-2">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold"
                                  :style="`background-color: color-mix(in srgb, ${ESTADO_COLOR[planCarrera.estado] ?? '#9ca3af'} 15%, transparent); color: ${ESTADO_COLOR[planCarrera.estado] ?? '#6b7280'};`">
                                {{ planCarrera.estado }}
                            </span>
                            <span class="text-xs font-medium uppercase" style="color: var(--text-secondary);">{{ planCarrera.forma_pago }}</span>
                            <span class="text-xs" style="color: var(--text-secondary);">Contrato: {{ fmtFecha(planCarrera.fecha_contrato) }}</span>
                        </div>
                        <div class="flex items-center gap-4 text-sm">
                            <span style="color: var(--text-secondary);">Pagado: <strong style="color: var(--text-color);">{{ fmtMonto(planCarrera.monto_pagado) }}</strong></span>
                            <span style="color: var(--text-secondary);">Total: <strong style="color: var(--text-color);">{{ fmtMonto(planCarrera.monto_total) }}</strong></span>
                            <span v-if="planCarrera.materias_cubiertas" style="color: var(--text-secondary);">Materias cubiertas: <strong style="color: var(--text-color);">{{ planCarrera.materias_cubiertas }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagos por materia -->
            <div>
                <h3 class="font-semibold text-sm mb-2" style="color: var(--text-color);">Pagos por Materia</h3>
                <div v-if="!pagosMateria.length" class="rounded-xl p-5" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <p class="text-sm" style="color: var(--text-secondary);">No tienes pagos de materias sueltas registrados.</p>
                </div>
                <div v-else class="rounded-xl overflow-hidden" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <div class="divide-y" style="border-color: var(--border-color);">
                        <div v-for="p in pagosMateria" :key="p.id_pago_materia" class="flex items-center gap-3 px-5 py-3.5 flex-wrap">
                            <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded shrink-0"
                                  style="background-color: color-mix(in srgb,var(--text-color) 8%,transparent); color: var(--text-secondary);">
                                {{ p.materia_codigo }}
                            </span>
                            <span class="font-medium text-sm flex-1 min-w-[140px]" style="color: var(--text-color);">{{ p.materia_nombre }}</span>
                            <span class="text-xs shrink-0" style="color: var(--text-secondary);">{{ fmtFecha(p.fecha_pago) }}</span>
                            <span class="text-sm font-bold shrink-0" style="color: var(--text-color);">{{ fmtMonto(p.monto_pagado) }}</span>
                            <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold w-24 text-center"
                                  :style="`background-color: color-mix(in srgb, ${ESTADO_COLOR[p.estado] ?? '#9ca3af'} 15%, transparent); color: ${ESTADO_COLOR[p.estado] ?? '#6b7280'};`">
                                {{ p.estado }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </EstudianteLayout>
</template>
