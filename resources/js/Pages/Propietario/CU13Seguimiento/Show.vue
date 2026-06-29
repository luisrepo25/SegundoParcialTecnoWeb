<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    estudiante: Object,
    carrera:    { type: Object,  default: null },
    historial:  { type: Array,   default: () => [] },
    resumen:    Object,
});

// ── Modal abandono ────────────────────────────────────────────────────────────
const showAbandono = ref(false);
const formAbandono = useForm({ motivo: '' });

function registrarAbandono() {
    formAbandono.post(route('propietario.seguimiento.abandono', props.estudiante.id_usuario), {
        onSuccess: () => { showAbandono.value = false; formAbandono.reset(); },
    });
}

// ── Validar recursada ─────────────────────────────────────────────────────────
const recursoResult  = ref({});   // { [id_materia]: { loading, resultado } }

async function verificarRecurso(idMateria) {
    recursoResult.value[idMateria] = { loading: true, resultado: null };
    try {
        const res = await fetch(route('propietario.seguimiento.recurso', {
            id:         props.estudiante.id_usuario,
            idMateria,
        }));
        recursoResult.value[idMateria] = { loading: false, resultado: await res.json() };
    } catch {
        recursoResult.value[idMateria] = { loading: false, resultado: { puede_recursar: false, mensaje: 'Error al consultar.' } };
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const nombreCompleto = computed(() => `${props.estudiante.apellido}, ${props.estudiante.nombre}`);

function formatFecha(f) {
    if (!f) return '—';
    return new Date(f + 'T12:00:00').toLocaleDateString('es-BO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function estadoEfectivo(ins) {
    if (ins.aprobado)                        return { label: 'Aprobado', bg: 'rgba(52,211,153,0.15)',  color: '#34d399' };
    if (ins.calificacion_final !== null)     return { label: 'Reprobado', bg: 'rgba(248,113,113,0.15)', color: '#f87171' };
    if (ins.estado === 'activo')             return { label: 'En curso',  bg: 'rgba(99,102,241,0.15)',  color: '#818cf8' };
    return { label: 'Pendiente', bg: 'rgba(156,163,175,0.15)', color: '#9ca3af' };
}

const TIPO_LABEL = { parcial1: 'P1', parcial2: 'P2', final: 'Fin', otros: 'Ext' };
</script>

<template>
    <Head :title="`Historial — ${estudiante.apellido} ${estudiante.nombre}`" />

    <AdminLayout>
        <template #header>
            <h2 class="text-base font-semibold" style="color: var(--text-color);">
                CU13 — Seguimiento Académico
            </h2>
        </template>

        <!-- Volver -->
        <div class="mb-5">
            <Link :href="route('propietario.seguimiento.index')"
                class="inline-flex items-center gap-1.5 text-sm font-medium transition-opacity hover:opacity-70"
                style="color: var(--text-secondary);">
                ← Volver al listado
            </Link>
        </div>

        <!-- Encabezado del estudiante -->
        <div class="rounded-xl p-5 mb-5 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4"
             style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold shrink-0"
                     style="background-color: var(--primary-color); color: var(--primary-text);">
                    {{ (estudiante.nombre[0] ?? '') + (estudiante.apellido[0] ?? '') }}
                </div>
                <div>
                    <h1 class="text-lg font-bold" style="color: var(--text-color);">{{ nombreCompleto }}</h1>
                    <p class="text-sm" style="color: var(--text-secondary);">{{ estudiante.email }}</p>
                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                        <span v-if="estudiante.legajo" class="text-xs font-mono px-2 py-0.5 rounded"
                              style="background-color: color-mix(in srgb,var(--primary-color) 12%,transparent); color: var(--primary-color);">
                            {{ estudiante.legajo }}
                        </span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                              :style="estudiante.activo
                                ? 'background-color:rgba(52,211,153,0.15);color:#34d399;'
                                : 'background-color:rgba(248,113,113,0.15);color:#f87171;'">
                            {{ estudiante.activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                </div>
            </div>
            <button @click="showAbandono = true"
                class="self-start px-4 py-2 rounded-lg text-sm font-medium border"
                style="background-color:rgba(248,113,113,0.1);color:#f87171;border-color:rgba(248,113,113,0.3);">
                Registrar abandono
            </button>
        </div>

        <!-- Datos personales + carrera/indicadores -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">

            <!-- Datos personales -->
            <div class="rounded-xl p-5" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-3" style="color: var(--text-secondary);">Datos personales</p>
                <dl class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <dt style="color: var(--text-secondary);">DNI / CI</dt>
                        <dd class="font-medium" style="color: var(--text-color);">{{ estudiante.dni ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt style="color: var(--text-secondary);">Teléfono</dt>
                        <dd style="color: var(--text-color);">{{ estudiante.telefono ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt style="color: var(--text-secondary);">Inscripción inicial</dt>
                        <dd style="color: var(--text-color);">{{ formatFecha(estudiante.fecha_inicio) }}</dd>
                    </div>
                    <div v-if="estudiante.tutor_nombre" class="flex justify-between text-sm">
                        <dt style="color: var(--text-secondary);">Tutor</dt>
                        <dd style="color: var(--text-color);">
                            {{ estudiante.tutor_nombre }}
                            <span v-if="estudiante.tutor_telefono" style="color: var(--text-secondary);"> ({{ estudiante.tutor_telefono }})</span>
                        </dd>
                    </div>
                </dl>
                <div v-if="estudiante.observaciones" class="mt-4 pt-4 border-t" style="border-color: var(--border-color);">
                    <p class="text-[11px] font-semibold uppercase tracking-widest mb-2" style="color: var(--text-secondary);">Observaciones</p>
                    <p class="text-xs whitespace-pre-line" style="color: var(--text-color);">{{ estudiante.observaciones }}</p>
                </div>
            </div>

            <!-- Carrera + indicadores -->
            <div class="rounded-xl p-5" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-1" style="color: var(--text-secondary);">Carrera actual</p>
                <p class="text-base font-semibold mb-4" style="color: var(--text-color);">
                    {{ carrera?.nombre ?? 'Sin carrera asignada' }}
                </p>

                <div class="grid grid-cols-2 gap-2">
                    <div v-for="ind in [
                        { label: 'Materias cursadas', value: resumen.total_materias_cursadas, color: 'var(--primary-color)' },
                        { label: 'Aprobadas',          value: resumen.materias_aprobadas,      color: '#34d399' },
                        { label: 'Reprobadas',         value: resumen.materias_reprobadas,     color: '#f87171' },
                        { label: 'Promedio general',   value: resumen.promedio_general ?? '—', color: 'var(--text-color)' },
                        { label: '% Aprobación',       value: resumen.tasa_aprobacion != null ? resumen.tasa_aprobacion + '%' : '—', color: '#f59e0b' },
                        { label: 'Progreso carrera',   value: resumen.progreso_carrera != null ? resumen.progreso_carrera + '%' : '—', color: '#6366f1' },
                    ]" :key="ind.label"
                        class="rounded-lg p-3 text-center"
                        style="background-color: color-mix(in srgb,var(--text-color) 4%,transparent);">
                        <p class="text-xl font-bold" :style="{ color: ind.color }">{{ ind.value }}</p>
                        <p class="text-[10px] uppercase tracking-wide mt-0.5" style="color: var(--text-secondary);">{{ ind.label }}</p>
                    </div>
                </div>

                <!-- Barra de progreso carrera -->
                <div v-if="resumen.progreso_carrera !== null" class="mt-4">
                    <div class="w-full rounded-full h-2" style="background-color:color-mix(in srgb,var(--text-color) 8%,transparent);">
                        <div class="h-2 rounded-full transition-all"
                             style="background-color:#6366f1;"
                             :style="{ width: Math.min(resumen.progreso_carrera, 100) + '%' }">
                        </div>
                    </div>
                    <p class="text-[10px] mt-1 text-right" style="color:var(--text-secondary);">
                        {{ resumen.materias_aprobadas }} de {{ Math.round(resumen.materias_aprobadas / (resumen.progreso_carrera / 100)) }} materias en malla
                    </p>
                </div>
            </div>
        </div>

        <!-- Historial de materias -->
        <div class="rounded-xl overflow-hidden mb-5" style="border: 1px solid var(--border-color);">
            <div class="px-5 py-4 flex items-center justify-between"
                 style="background-color: var(--card-bg); border-bottom: 1px solid var(--border-color);">
                <p class="text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">
                    Historial de materias cursadas
                </p>
                <span class="text-xs" style="color: var(--text-secondary);">
                    {{ resumen.total_materias_cursadas }} materia{{ resumen.total_materias_cursadas !== 1 ? 's' : '' }}
                </span>
            </div>

            <div v-if="historial.length === 0"
                 class="px-5 py-10 text-center"
                 style="background-color: var(--card-bg);">
                <p class="text-2xl mb-2">📚</p>
                <p class="text-sm font-medium mb-1" style="color: var(--text-color);">Sin materias registradas</p>
                <p class="text-xs" style="color: var(--text-secondary);">El estudiante no tiene inscripciones aún.</p>
            </div>

            <div v-else class="overflow-x-auto" style="background-color: var(--card-bg);">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background-color:color-mix(in srgb,var(--text-color) 4%,transparent);border-bottom:1px solid var(--border-color);">
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:var(--text-secondary);">Materia</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:var(--text-secondary);">Período</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:var(--text-secondary);">Nota final</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:var(--text-secondary);">Evaluaciones</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:var(--text-secondary);">Estado</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:var(--text-secondary);">Recursada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="ins in historial" :key="ins.id_inscripcion"
                            class="border-t" style="border-color:var(--border-color);"
                            onmouseover="this.style.backgroundColor='color-mix(in srgb,var(--text-color) 2%,transparent)'"
                            onmouseout="this.style.backgroundColor='transparent'">

                            <!-- Materia -->
                            <td class="px-4 py-3">
                                <p class="font-medium text-sm" style="color:var(--text-color);">{{ ins.materia }}</p>
                                <p class="text-xs" style="color:var(--text-secondary);">{{ ins.materia_codigo }} · {{ ins.codigo_grupo }}</p>
                            </td>

                            <!-- Período -->
                            <td class="px-4 py-3">
                                <p class="text-sm" style="color:var(--text-color);">{{ ins.periodo }}</p>
                                <p class="text-xs" style="color:var(--text-secondary);">{{ formatFecha(ins.fecha_inicio) }}</p>
                            </td>

                            <!-- Nota final -->
                            <td class="px-4 py-3 text-center">
                                <span v-if="ins.calificacion_final !== null"
                                    class="text-sm font-bold"
                                    :style="ins.aprobado ? 'color:#34d399;' : 'color:#f87171;'">
                                    {{ ins.calificacion_final }}
                                </span>
                                <span v-else class="text-xs" style="color:var(--text-secondary);">—</span>
                            </td>

                            <!-- Evaluaciones -->
                            <td class="px-4 py-3 text-center">
                                <div class="flex flex-wrap justify-center gap-1">
                                    <span v-for="ev in ins.evaluaciones" :key="ev.id_evaluacion"
                                        class="inline-block text-[11px] font-mono px-1.5 py-0.5 rounded"
                                        style="background-color:color-mix(in srgb,var(--primary-color) 12%,transparent);color:var(--primary-color);">
                                        {{ TIPO_LABEL[ev.tipo] ?? ev.tipo }}: {{ ev.calificacion }}
                                    </span>
                                    <span v-if="!ins.evaluaciones?.length" class="text-xs" style="color:var(--text-secondary);">—</span>
                                </div>
                            </td>

                            <!-- Estado -->
                            <td class="px-4 py-3 text-center">
                                <span class="text-[11px] font-medium px-2 py-0.5 rounded-full"
                                    :style="`background-color:${estadoEfectivo(ins).bg};color:${estadoEfectivo(ins).color};`">
                                    {{ estadoEfectivo(ins).label }}
                                </span>
                            </td>

                            <!-- Recursada -->
                            <td class="px-4 py-3 text-right">
                                <template v-if="!ins.aprobado">
                                    <button v-if="!recursoResult[ins.id_materia]"
                                        @click="verificarRecurso(ins.id_materia)"
                                        class="text-xs px-2 py-1 rounded border transition"
                                        style="border-color:var(--border-color);color:var(--text-secondary);">
                                        ¿Puede recursar?
                                    </button>
                                    <span v-else-if="recursoResult[ins.id_materia].loading"
                                        class="text-xs" style="color:var(--text-secondary);">
                                        Consultando…
                                    </span>
                                    <span v-else
                                        class="text-[11px] font-medium px-2 py-0.5 rounded-full"
                                        :style="recursoResult[ins.id_materia].resultado?.puede_recursar
                                            ? 'background-color:rgba(52,211,153,0.15);color:#34d399;'
                                            : 'background-color:rgba(248,113,113,0.15);color:#f87171;'">
                                        {{ recursoResult[ins.id_materia].resultado?.mensaje }}
                                    </span>
                                </template>
                                <span v-else class="text-xs" style="color:var(--text-secondary);">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </AdminLayout>

    <!-- ── Modal Registrar Abandono ────────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="showAbandono"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background:rgba(0,0,0,0.5);"
             @click.self="showAbandono = false">
            <div class="rounded-xl p-6 w-full max-w-md"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <h3 class="text-base font-semibold mb-1" style="color: var(--text-color);">Registrar abandono de carrera</h3>
                <p class="text-xs mb-4" style="color: var(--text-secondary);">
                    {{ nombreCompleto }} — {{ carrera?.nombre ?? 'sin carrera' }}
                </p>

                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Motivo del abandono</label>
                <textarea v-model="formAbandono.motivo" rows="4"
                    placeholder="Describe el motivo del abandono…"
                    class="w-full rounded-lg px-3 py-2 text-sm resize-none outline-none"
                    style="background-color:color-mix(in srgb,var(--card-bg) 60%,var(--border-color));color:var(--text-color);border:1px solid var(--border-color);">
                </textarea>
                <p v-if="formAbandono.errors.motivo" class="text-xs mt-1" style="color: #f87171;">
                    {{ formAbandono.errors.motivo }}
                </p>

                <div class="flex justify-end gap-3 mt-5">
                    <button @click="showAbandono = false"
                        class="px-4 py-2 rounded-lg text-sm font-medium border"
                        style="border-color:var(--border-color);color:var(--text-secondary);">
                        Cancelar
                    </button>
                    <button @click="registrarAbandono"
                        :disabled="formAbandono.processing || !formAbandono.motivo.trim()"
                        class="px-4 py-2 rounded-lg text-sm font-medium disabled:opacity-50"
                        style="background-color:#f87171;color:#fff;">
                        Confirmar abandono
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
