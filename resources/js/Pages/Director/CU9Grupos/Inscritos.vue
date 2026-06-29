<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    grupo:     { type: Object, default: () => ({}) },
    inscritos: { type: Array,  default: () => [] },
});

const fmtFecha = (f) => {
    if (!f) return '';
    return new Date(f + 'T12:00:00').toLocaleDateString('es-BO', { day: '2-digit', month: 'short', year: 'numeric' });
};

const fmtHora = (h) => h ? h.slice(0, 5) : '';

const estadoBadge = (estado, aprobado) => {
    if (aprobado) return { label: 'Aprobado', color: '#10b981' };
    const map = {
        activo:             { label: 'Activo',          color: '#6366f1' },
        aprobado:           { label: 'Aprobado',        color: '#10b981' },
        pendiente_matricula:{ label: 'Pend. pago',      color: '#f59e0b' },
        pendiente_pago:     { label: 'Pend. pago',      color: '#f59e0b' },
    };
    return map[estado] ?? { label: estado, color: '#6b7280' };
};

const activos   = computed(() => props.inscritos.filter(i => i.estado === 'activo').length);
const aprobados = computed(() => props.inscritos.filter(i => i.aprobado).length);
const pendientes= computed(() => props.inscritos.filter(i => i.estado?.startsWith('pendiente')).length);
</script>

<template>
    <AdminLayout>
        <template #header>
            <div class="flex items-center gap-3 min-w-0">
                <button @click="router.visit(route('director.grupos.index'))"
                    class="text-sm px-3 py-1.5 rounded-lg border transition"
                    style="border-color: var(--border-color); color: var(--text-secondary);">
                    ← Volver
                </button>
                <h1 class="text-lg font-semibold truncate" style="color: var(--text-color);">
                    Inscritos — {{ grupo.codigo_grupo ?? grupo.materia_codigo }}
                </h1>
            </div>
        </template>

        <!-- Info grupo -->
        <div class="rounded-xl border p-5 mb-6"
             style="background-color: var(--card-bg); border-color: var(--border-color);">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide mb-0.5" style="color: var(--text-secondary);">Materia</p>
                    <p class="text-sm font-medium" style="color: var(--text-color);">{{ grupo.materia_nombre }}</p>
                    <p class="text-xs" style="color: var(--text-secondary);">{{ grupo.materia_codigo }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide mb-0.5" style="color: var(--text-secondary);">Período</p>
                    <p class="text-sm font-medium" style="color: var(--text-color);">{{ grupo.periodo_nombre }}</p>
                    <p class="text-xs" style="color: var(--text-secondary);">{{ fmtFecha(grupo.fecha_inicio) }} — {{ fmtFecha(grupo.fecha_fin) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide mb-0.5" style="color: var(--text-secondary);">Horario</p>
                    <p class="text-sm font-medium capitalize" style="color: var(--text-color);">{{ grupo.dia_semana }}</p>
                    <p class="text-xs" style="color: var(--text-secondary);">{{ fmtHora(grupo.hora_inicio) }} – {{ fmtHora(grupo.hora_fin) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide mb-0.5" style="color: var(--text-secondary);">Vacantes</p>
                    <p class="text-sm font-bold" style="color: var(--primary-color);">
                        {{ grupo.vacantes_ocupadas ?? 0 }} / {{ grupo.vacantes_max }}
                    </p>
                    <p class="text-xs" style="color: var(--text-secondary);">{{ grupo.aula_nombre }}</p>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 mb-5">
            <div v-for="s in [
                { label: 'Activos',    value: activos,    color: '#6366f1' },
                { label: 'Aprobados',  value: aprobados,  color: '#10b981' },
                { label: 'Pendientes', value: pendientes, color: '#f59e0b' },
            ]" :key="s.label"
                class="rounded-xl p-4 border text-center"
                style="background-color: var(--card-bg); border-color: var(--border-color);">
                <p class="text-2xl font-bold" :style="{ color: s.color }">{{ s.value }}</p>
                <p class="text-xs mt-0.5" style="color: var(--text-secondary);">{{ s.label }}</p>
            </div>
        </div>

        <!-- Tabla -->
        <div class="rounded-xl border overflow-hidden"
             style="background-color: var(--card-bg); border-color: var(--border-color);">
            <div v-if="inscritos.length === 0" class="p-10 text-center">
                <p class="text-4xl mb-3">👥</p>
                <p class="font-medium" style="color: var(--text-color);">Sin inscritos en este grupo.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background-color: color-mix(in srgb,var(--text-color) 4%,transparent); border-bottom: 1px solid var(--border-color);">
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Legajo</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Estudiante</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Carrera</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">F. Inscripción</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Estado</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Nota</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="ins in inscritos" :key="ins.id_inscripcion"
                            class="transition-colors"
                            style="border-bottom: 1px solid var(--border-color);"
                            onmouseover="this.style.backgroundColor='color-mix(in srgb,var(--text-color) 3%,transparent)'"
                            onmouseout="this.style.backgroundColor='transparent'">

                            <td class="px-4 py-3">
                                <code class="text-xs px-1.5 py-0.5 rounded font-bold"
                                      style="background-color: color-mix(in srgb,var(--primary-color) 12%,transparent); color: var(--primary-color);">
                                    {{ ins.legajo }}
                                </code>
                            </td>

                            <td class="px-4 py-3">
                                <p class="font-medium text-xs" style="color: var(--text-color);">{{ ins.estudiante_nombre }}</p>
                                <p class="text-[11px]" style="color: var(--text-secondary);">{{ ins.email }}</p>
                            </td>

                            <td class="px-4 py-3">
                                <p class="text-xs" style="color: var(--text-secondary);">{{ ins.carrera_nombre ?? '—' }}</p>
                            </td>

                            <td class="px-4 py-3">
                                <p class="text-xs" style="color: var(--text-secondary);">{{ fmtFecha(ins.fecha_inscripcion?.slice(0,10)) }}</p>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="text-[11px] font-medium px-2 py-0.5 rounded-full"
                                      :style="`background-color: color-mix(in srgb,${estadoBadge(ins.estado, ins.aprobado).color} 15%,transparent); color:${estadoBadge(ins.estado, ins.aprobado).color};`">
                                    {{ estadoBadge(ins.estado, ins.aprobado).label }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span v-if="ins.calificacion_final !== null"
                                      class="text-sm font-bold"
                                      :style="{ color: ins.aprobado ? '#10b981' : '#ef4444' }">
                                    {{ ins.calificacion_final }}
                                </span>
                                <span v-else class="text-xs" style="color: var(--text-secondary);">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
