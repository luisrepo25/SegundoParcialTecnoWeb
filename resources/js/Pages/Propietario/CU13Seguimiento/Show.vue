<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    estudiante: Object,
    carrera:    Object,
    historial:  Array,
    resumen:    Object,
    pendiente:  Object,
});

// ── Modal abandono ────────────────────────────────────────────────────────────
const showAbandono = ref(false);
const formAbandono = useForm({ motivo: '' });

function registrarAbandono() {
    formAbandono.post(route('propietario.seguimiento.abandono', props.estudiante.id_usuario), {
        onSuccess: () => { showAbandono.value = false; formAbandono.reset(); },
    });
}

// ── Helpers de display ────────────────────────────────────────────────────────
const nombreCompleto = computed(() => `${props.estudiante.apellido}, ${props.estudiante.nombre}`);

function formatFecha(f) {
    if (!f) return '—';
    return new Date(f).toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });
}

const ESTADO_COLOR = {
    aprobado:    'background-color: rgba(52,211,153,0.15); color: #34d399;',
    reprobado:   'background-color: rgba(248,113,113,0.15); color: #f87171;',
    cursando:    'background-color: rgba(99,102,241,0.15); color: #818cf8;',
    retirado:    'background-color: rgba(156,163,175,0.15); color: #9ca3af;',
};
</script>

<template>
    <Head :title="`Historial — ${estudiante.apellido} ${estudiante.nombre}`" />

    <AdminLayout>
        <template #header>
            <h2 class="text-base font-semibold" style="color: var(--text-color);">Seguimiento Académico</h2>
        </template>

        <!-- Volver -->
        <div class="mb-6">
            <Link :href="route('propietario.seguimiento.index')"
                class="inline-flex items-center gap-1.5 text-sm font-medium transition-opacity hover:opacity-70"
                style="color: var(--text-secondary);">
                ← Volver al listado
            </Link>
        </div>

        <!-- Encabezado del estudiante -->
        <div class="rounded-xl p-6 mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4"
             style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
            <div class="flex items-center gap-4">
                <!-- Avatar -->
                <div class="w-14 h-14 rounded-full flex items-center justify-center text-xl font-bold shrink-0"
                     style="background-color: var(--primary-color); color: var(--primary-text);">
                    {{ (estudiante.nombre[0] ?? '') + (estudiante.apellido[0] ?? '') }}
                </div>
                <div>
                    <h1 class="text-xl font-bold" style="color: var(--text-color);">{{ nombreCompleto }}</h1>
                    <p class="text-sm" style="color: var(--text-secondary);">{{ estudiante.email }}</p>
                    <div class="flex items-center gap-3 mt-1 flex-wrap">
                        <span v-if="estudiante.legajo" class="text-xs font-mono px-2 py-0.5 rounded"
                              style="background-color: color-mix(in srgb, var(--primary-color) 12%, transparent); color: var(--primary-color);">
                            Legajo: {{ estudiante.legajo }}
                        </span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                              :style="estudiante.activo
                                ? 'background-color: rgba(52,211,153,0.15); color: #34d399;'
                                : 'background-color: rgba(248,113,113,0.15); color: #f87171;'">
                            {{ estudiante.activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                </div>
            </div>
            <!-- Botón abandono -->
            <button @click="showAbandono = true"
                class="self-start px-4 py-2 rounded-lg text-sm font-medium transition-opacity hover:opacity-80"
                style="background-color: rgba(248,113,113,0.15); color: #f87171; border: 1px solid rgba(248,113,113,0.3);">
                Registrar abandono
            </button>
        </div>

        <!-- Datos personales + carrera -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

            <!-- Datos personales -->
            <div class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-4" style="color: var(--text-secondary);">Datos personales</p>
                <dl class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <dt style="color: var(--text-secondary);">DNI</dt>
                        <dd style="color: var(--text-color);">{{ estudiante.dni ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt style="color: var(--text-secondary);">Teléfono</dt>
                        <dd style="color: var(--text-color);">{{ estudiante.telefono ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt style="color: var(--text-secondary);">Fecha de inicio</dt>
                        <dd style="color: var(--text-color);">{{ formatFecha(estudiante.fecha_inicio) }}</dd>
                    </div>
                    <div v-if="estudiante.tutor_nombre" class="flex justify-between text-sm">
                        <dt style="color: var(--text-secondary);">Tutor</dt>
                        <dd style="color: var(--text-color);">{{ estudiante.tutor_nombre }} {{ estudiante.tutor_telefono ? `(${estudiante.tutor_telefono})` : '' }}</dd>
                    </div>
                </dl>
                <div v-if="estudiante.observaciones" class="mt-4 pt-4 border-t" style="border-color: var(--border-color);">
                    <p class="text-[11px] font-semibold uppercase tracking-widest mb-2" style="color: var(--text-secondary);">Observaciones</p>
                    <p class="text-xs whitespace-pre-line" style="color: var(--text-color);">{{ estudiante.observaciones }}</p>
                </div>
            </div>

            <!-- Carrera + resumen -->
            <div class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-4" style="color: var(--text-secondary);">Carrera actual</p>
                <p class="text-base font-semibold mb-4" style="color: var(--text-color);">
                    {{ carrera?.nombre ?? 'Sin carrera asignada' }}
                </p>

                <!-- Resumen de indicadores -->
                <div v-if="!pendiente.inscripciones && !pendiente.evaluaciones" class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg p-3 text-center" style="background-color: color-mix(in srgb, var(--card-bg) 60%, var(--border-color));">
                        <p class="text-2xl font-light" style="color: var(--text-color);">{{ resumen.materias_aprobadas }}</p>
                        <p class="text-[10px] uppercase tracking-wide mt-0.5" style="color: #34d399;">Aprobadas</p>
                    </div>
                    <div class="rounded-lg p-3 text-center" style="background-color: color-mix(in srgb, var(--card-bg) 60%, var(--border-color));">
                        <p class="text-2xl font-light" style="color: var(--text-color);">{{ resumen.materias_reprobadas }}</p>
                        <p class="text-[10px] uppercase tracking-wide mt-0.5" style="color: #f87171;">Reprobadas</p>
                    </div>
                    <div class="rounded-lg p-3 text-center" style="background-color: color-mix(in srgb, var(--card-bg) 60%, var(--border-color));">
                        <p class="text-2xl font-light" style="color: var(--text-color);">
                            {{ resumen.promedio_general ?? '—' }}
                        </p>
                        <p class="text-[10px] uppercase tracking-wide mt-0.5" style="color: var(--text-secondary);">Promedio</p>
                    </div>
                    <div class="rounded-lg p-3 text-center" style="background-color: color-mix(in srgb, var(--card-bg) 60%, var(--border-color));">
                        <p class="text-2xl font-light" style="color: var(--text-color);">
                            {{ resumen.tasa_aprobacion != null ? resumen.tasa_aprobacion + '%' : '—' }}
                        </p>
                        <p class="text-[10px] uppercase tracking-wide mt-0.5" style="color: var(--text-secondary);">Aprobación</p>
                    </div>
                </div>

                <!-- Pendiente aviso -->
                <div v-else class="rounded-lg p-4 text-center opacity-60"
                     style="background-color: color-mix(in srgb, var(--card-bg) 60%, var(--border-color)); border: 1px dashed var(--border-color);">
                    <p class="text-xs font-medium mb-1" style="color: var(--text-color);">Indicadores pendientes</p>
                    <p class="text-[11px]" style="color: var(--text-secondary);">
                        Disponibles cuando CU6 (Inscripciones) y CU12 (Evaluaciones) estén implementados
                    </p>
                </div>
            </div>

        </div>

        <!-- Historial de materias -->
        <div class="rounded-xl overflow-hidden mb-6" style="border: 1px solid var(--border-color);">
            <div class="px-6 py-4 flex items-center justify-between"
                 style="background-color: var(--card-bg); border-bottom: 1px solid var(--border-color);">
                <p class="text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">
                    Historial de materias cursadas
                </p>
                <span class="text-[11px]" style="color: var(--text-secondary);">
                    {{ resumen.total_materias_cursadas }} materia{{ resumen.total_materias_cursadas !== 1 ? 's' : '' }}
                </span>
            </div>

            <!-- Pendiente -->
            <div v-if="pendiente.inscripciones || pendiente.evaluaciones"
                 class="px-6 py-12 text-center"
                 style="background-color: var(--card-bg);">
                <p class="text-sm font-medium mb-2" style="color: var(--text-color);">Sin datos de historial aún</p>
                <p class="text-xs" style="color: var(--text-secondary);">
                    El historial estará disponible cuando los módulos de
                    <span v-if="pendiente.inscripciones">Inscripciones (CU6)</span>
                    <span v-if="pendiente.inscripciones && pendiente.evaluaciones"> y </span>
                    <span v-if="pendiente.evaluaciones">Evaluaciones (CU12)</span>
                    sean implementados.
                </p>
            </div>

            <!-- Tabla historial -->
            <table v-else class="w-full text-sm" style="background-color: var(--card-bg);">
                <thead>
                    <tr style="background-color: color-mix(in srgb, var(--card-bg) 80%, var(--border-color));">
                        <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">Materia</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">Periodo</th>
                        <th class="text-center px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">Promedio</th>
                        <th class="text-center px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">Estado</th>
                        <th class="text-right px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">Evaluaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!historial.length">
                        <td colspan="5" class="text-center py-8 text-sm" style="color: var(--text-secondary);">
                            Sin materias registradas
                        </td>
                    </tr>
                    <tr v-for="ins in historial" :key="ins.id_inscripcion"
                        class="border-t" style="border-color: var(--border-color);">
                        <td class="px-4 py-3 font-medium" style="color: var(--text-color);">{{ ins.materia }}</td>
                        <td class="px-4 py-3 text-xs" style="color: var(--text-secondary);">{{ ins.periodo }}</td>
                        <td class="px-4 py-3 text-center font-mono font-bold" style="color: var(--text-color);">
                            {{ ins.promedio ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                  :style="ESTADO_COLOR[ins.estado] ?? ESTADO_COLOR['cursando']">
                                {{ ins.estado }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span v-for="ev in (ins.evaluaciones ?? [])" :key="ev.numero_evaluacion"
                                  class="inline-block text-[11px] font-mono px-1.5 py-0.5 rounded ml-1"
                                  style="background-color: color-mix(in srgb, var(--primary-color) 12%, transparent); color: var(--primary-color);">
                                E{{ ev.numero_evaluacion }}: {{ ev.nota ?? '—' }}
                            </span>
                            <span v-if="!ins.evaluaciones?.length" class="text-xs" style="color: var(--text-secondary);">—</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal Registrar Abandono -->
        <div v-if="showAbandono"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
             @click.self="showAbandono = false">
            <div class="rounded-xl p-6 w-full max-w-md mx-4"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <h3 class="text-base font-semibold mb-1" style="color: var(--text-color);">Registrar abandono de carrera</h3>
                <p class="text-xs mb-4" style="color: var(--text-secondary);">
                    {{ nombreCompleto }} — {{ carrera?.nombre ?? 'sin carrera' }}
                </p>

                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Motivo del abandono</label>
                <textarea v-model="formAbandono.motivo" rows="4"
                    placeholder="Describe el motivo del abandono…"
                    class="w-full rounded-lg px-3 py-2 text-sm resize-none focus:outline-none"
                    style="background-color: color-mix(in srgb, var(--card-bg) 60%, var(--border-color)); color: var(--text-color); border: 1px solid var(--border-color);">
                </textarea>
                <p v-if="formAbandono.errors.motivo" class="text-xs mt-1" style="color: #f87171;">
                    {{ formAbandono.errors.motivo }}
                </p>

                <div class="flex justify-end gap-3 mt-5">
                    <button @click="showAbandono = false"
                        class="px-4 py-2 rounded-lg text-sm font-medium"
                        style="color: var(--text-secondary); border: 1px solid var(--border-color);">
                        Cancelar
                    </button>
                    <button @click="registrarAbandono"
                        :disabled="formAbandono.processing || !formAbandono.motivo.trim()"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-opacity disabled:opacity-50"
                        style="background-color: #f87171; color: #fff;">
                        Confirmar abandono
                    </button>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>
