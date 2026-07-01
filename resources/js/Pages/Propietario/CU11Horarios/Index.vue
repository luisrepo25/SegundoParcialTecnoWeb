<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { errHora, errHoraFin } from '@/utils/validacion.js';

const canEdit = computed(() => ['propietario', 'director'].includes(usePage().props.auth?.user?.role));

const dashboardRoute = computed(() => {
    const role = usePage().props.auth?.user?.role;
    if (role === 'director') return 'dashboard.director';
    if (role === 'secretaria') return 'secretaria.dashboard';
    return 'dashboard.propietario';
});

const props = defineProps({
    horarios: Object,
    dias:     Array,
    filtros:  Object,
});

// ── Filtros ───────────────────────────────────────────────────────────────────
const diaFiltro    = ref(props.filtros?.dia    ?? '');
const activoFiltro = ref(props.filtros?.activo ?? 'todos');

watch([diaFiltro, activoFiltro], aplicarFiltros);

function aplicarFiltros() {
    router.get(route('propietario.horarios.index'), {
        dia:    diaFiltro.value    || undefined,
        activo: activoFiltro.value !== 'todos' ? activoFiltro.value : undefined,
    }, { preserveState: true, replace: true });
}

// ── Modal Crear / Editar ──────────────────────────────────────────────────────
const showModal   = ref(false);
const modoEdicion = ref(false);
const editandoId  = ref(null);

const form = useForm({
    dia_semana: '', hora_inicio: '', hora_fin: '',
});

function abrirCrear() {
    form.reset(); form.clearErrors();
    modoEdicion.value = false;
    editandoId.value  = null;
    showModal.value   = true;
}

function abrirEditar(h) {
    form.reset(); form.clearErrors();
    form.dia_semana  = h.dia_semana;
    form.hora_inicio = h.hora_inicio.substring(0, 5);
    form.hora_fin    = h.hora_fin.substring(0, 5);
    modoEdicion.value = true;
    editandoId.value  = h.id_horario;
    showModal.value   = true;
}

const fe = ref({});

function validarCampos() {
    const e = {};
    if (!form.dia_semana) e.dia_semana = 'El día de la semana es obligatorio.';
    const ei = errHora(form.hora_inicio, 'La hora de inicio');  if (ei) e.hora_inicio = ei;
    const ef = errHoraFin(form.hora_fin, form.hora_inicio);     if (ef) e.hora_fin    = ef;
    fe.value = e;
    return Object.keys(e).length === 0;
}

watch(() => [form.dia_semana, form.hora_inicio, form.hora_fin], () => {
    if (Object.keys(fe.value).length) validarCampos();
});

function cerrar() { showModal.value = false; form.reset(); form.clearErrors(); fe.value = {}; }

function guardar() {
    if (!validarCampos()) return;
    if (modoEdicion.value) {
        form.put(route('propietario.horarios.update', editandoId.value), { onSuccess: cerrar });
    } else {
        form.post(route('propietario.horarios.store'), { onSuccess: cerrar });
    }
}

function toggleActivo(h) {
    const accion = h.activo ? 'desactivar' : 'activar';
    if (!confirm(`¿Desea ${accion} el horario ${h.dia_semana} ${h.hora_inicio.substring(0,5)}–${h.hora_fin.substring(0,5)}?`)) return;
    router.patch(route('propietario.horarios.toggle-activo', h.id_horario));
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const DIA_COLOR = {
    lunes:     'badge-blue',
    martes:    'badge-purple',
    miercoles: 'badge-green',
    jueves:    'badge-yellow',
    viernes:   'badge-orange',
    sabado:    'badge-pink',
    domingo:   'badge-red',
};
const DIA_LABEL = {
    lunes: 'Lunes', martes: 'Martes', miercoles: 'Miércoles',
    jueves: 'Jueves', viernes: 'Viernes', sabado: 'Sábado', domingo: 'Domingo',
};

function formatHora(t) { return t ? t.substring(0, 5) : '—'; }
</script>

<template>
    <Head title="Gestión de Horarios" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--text-color);">
                Gestión de Horarios
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

                <!-- Volver -->
                <div class="mb-5">
                    <Link :href="route(dashboardRoute)"
                        class="inline-flex items-center gap-1.5 text-sm font-medium transition-opacity hover:opacity-70"
                        style="color: var(--text-secondary);">
                        ← Volver al Dashboard
                    </Link>
                </div>

                <!-- Flash -->
                <div v-if="$page.props.flash?.success" class="mb-4 rounded-lg p-4 text-sm font-medium"
                     style="background-color: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3);">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error" class="mb-4 rounded-lg p-4 text-sm font-medium"
                     style="background-color: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3);">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Barra superior -->
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex gap-2 flex-wrap">
                        <select v-model="diaFiltro"
                            class="rounded-lg px-3 py-2 text-sm focus:outline-none"
                            style="background-color: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color);">
                            <option value="">Todos los días</option>
                            <option v-for="d in dias" :key="d" :value="d">{{ DIA_LABEL[d] ?? d }}</option>
                        </select>
                        <select v-model="activoFiltro"
                            class="rounded-lg px-3 py-2 text-sm focus:outline-none"
                            style="background-color: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color);">
                            <option value="todos">Todos</option>
                            <option value="1">Activos</option>
                            <option value="0">Inactivos</option>
                        </select>
                    </div>
                    <button v-if="canEdit" @click="abrirCrear"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition"
                        style="background-color: var(--primary-color); color: var(--primary-text);">
                        + Nuevo Horario
                    </button>
                </div>

                <!-- Tabla -->
                <div class="overflow-hidden rounded-xl shadow" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr style="background-color: var(--bg-color);">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Día</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Hora Inicio</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Hora Fin</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Duración</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Estado</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="h in horarios.data" :key="h.id_horario"
                                class="border-t" style="border-color: var(--border-color);">
                                <td class="px-4 py-3">
                                    <span :class="['badge', DIA_COLOR[h.dia_semana] ?? 'badge-gray']">
                                        {{ DIA_LABEL[h.dia_semana] ?? h.dia_semana }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-mono font-medium" style="color: var(--text-color);">
                                    {{ formatHora(h.hora_inicio) }}
                                </td>
                                <td class="px-4 py-3 text-sm font-mono font-medium" style="color: var(--text-color);">
                                    {{ formatHora(h.hora_fin) }}
                                </td>
                                <td class="px-4 py-3 text-sm" style="color: var(--text-secondary);">
                                    {{ calcDuracion(h.hora_inicio, h.hora_fin) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['badge', h.activo ? 'badge-activo' : 'badge-inactivo']">
                                        {{ h.activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-1">
                                        <template v-if="canEdit">
                                            <button @click="abrirEditar(h)" class="btn-accion" style="color: #d97706;">Editar</button>
                                            <button @click="toggleActivo(h)" class="btn-accion"
                                                :style="h.activo ? 'color: #dc2626;' : 'color: #059669;'">
                                                {{ h.activo ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="horarios.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-sm" style="color: var(--text-secondary);">
                                    No se encontraron horarios.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="horarios.last_page > 1" class="flex items-center justify-between gap-2 px-4 py-3 border-t"
                         style="border-color: var(--border-color);">
                        <p class="text-sm" style="color: var(--text-secondary);">
                            Mostrando {{ horarios.from }}–{{ horarios.to }} de {{ horarios.total }} horarios
                        </p>
                        <div class="flex gap-1">
                            <template v-for="link in horarios.links" :key="link.label">
                                <button v-if="link.url" @click="router.get(link.url)"
                                    class="px-3 py-1 rounded text-sm border"
                                    :style="link.active
                                        ? 'background-color: var(--primary-color); color: var(--primary-text); border-color: var(--primary-color);'
                                        : 'background-color: var(--card-bg); color: var(--text-color); border-color: var(--border-color);'"
                                    v-html="link.label" />
                                <span v-else class="px-3 py-1 rounded text-sm border"
                                    style="color: var(--text-secondary); border-color: var(--border-color); opacity: 0.5;"
                                    v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Modal Crear / Editar ────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="cerrar">
                <div class="modal-box">
                    <div class="modal-header">
                        <h3 class="modal-title">{{ modoEdicion ? 'Editar Horario' : 'Nuevo Horario' }}</h3>
                        <button @click="cerrar" class="modal-close">&times;</button>
                    </div>

                    <form @submit.prevent="guardar" class="p-6 space-y-4">
                        <div>
                            <label class="field-label">Día de la semana *</label>
                            <select v-model="form.dia_semana" class="field-input">
                                <option value="">Seleccione un día</option>
                                <option v-for="d in dias" :key="d" :value="d">{{ DIA_LABEL[d] ?? d }}</option>
                            </select>
                            <p v-if="fe.dia_semana || form.errors.dia_semana" class="field-error">{{ fe.dia_semana || form.errors.dia_semana }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="field-label">Hora inicio *</label>
                                <input v-model="form.hora_inicio" type="time" class="field-input" />
                                <p v-if="fe.hora_inicio || form.errors.hora_inicio" class="field-error">{{ fe.hora_inicio || form.errors.hora_inicio }}</p>
                            </div>
                            <div>
                                <label class="field-label">Hora fin *</label>
                                <input v-model="form.hora_fin" type="time" class="field-input" />
                                <p v-if="fe.hora_fin || form.errors.hora_fin" class="field-error">{{ fe.hora_fin || form.errors.hora_fin }}</p>
                            </div>
                        </div>

                        <!-- Vista previa -->
                        <div v-if="form.dia_semana && form.hora_inicio && form.hora_fin"
                             class="rounded-lg p-3 text-sm text-center font-medium"
                             style="background-color: var(--bg-color); color: var(--text-color);">
                            {{ form.dia_semana }} · {{ form.hora_inicio }} – {{ form.hora_fin }}
                        </div>

                        <div class="flex justify-end gap-2 pt-2 border-t" style="border-color: var(--border-color);">
                            <button type="button" @click="cerrar" class="btn-secondary">Cancelar</button>
                            <button type="submit" :disabled="form.processing"
                                class="rounded-lg px-4 py-2 text-sm font-medium transition disabled:opacity-50"
                                style="background-color: var(--primary-color); color: var(--primary-text);">
                                {{ form.processing ? 'Guardando...' : (modoEdicion ? 'Actualizar' : 'Registrar') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<script>
// Calcula duración en horas y minutos
function calcDuracion(inicio, fin) {
    if (!inicio || !fin) return '—';
    const [h1, m1] = inicio.split(':').map(Number);
    const [h2, m2] = fin.split(':').map(Number);
    const mins = (h2 * 60 + m2) - (h1 * 60 + m1);
    if (mins <= 0) return '—';
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return h > 0 ? `${h}h${m > 0 ? ` ${m}min` : ''}` : `${m}min`;
}
</script>

<style scoped>
.modal-overlay {
    position: fixed; inset: 0; z-index: 50;
    display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,0.55); padding: 1rem;
}
.modal-box {
    width: 100%; max-width: 26rem; border-radius: 1rem; overflow: hidden;
    background-color: var(--card-bg); border: 1px solid var(--border-color);
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4);
}
.modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color);
}
.modal-title  { font-size: 1rem; font-weight: 600; color: var(--text-color); }
.modal-close  { font-size: 1.5rem; line-height: 1; color: var(--text-secondary); background: none; border: none; cursor: pointer; }
.modal-close:hover { color: var(--text-color); }

.field-label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; color: var(--text-color); }
.field-input {
    width: 100%; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem;
    background-color: var(--bg-color); color: var(--text-color);
    border: 1px solid var(--border-color); outline: none;
}
.field-input:focus { border-color: var(--primary-color); }
.field-error { margin-top: 0.25rem; font-size: 0.75rem; color: #ef4444; }

.btn-secondary {
    border-radius: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem;
    background: none; cursor: pointer; color: var(--text-color); border: 1px solid var(--border-color);
}
.btn-accion {
    padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500;
    background: none; border: none; cursor: pointer; transition: opacity 0.15s;
}
.btn-accion:hover { opacity: 0.7; }

.badge { display: inline-flex; border-radius: 9999px; padding: 0.125rem 0.625rem; font-size: 0.75rem; font-weight: 600; }
.badge-blue   { background: rgba(59,130,246,0.2);   color: #60a5fa; }
.badge-purple { background: rgba(139,92,246,0.2);   color: #a78bfa; }
.badge-green  { background: rgba(16,185,129,0.2);   color: #34d399; }
.badge-yellow { background: rgba(245,158,11,0.2);   color: #fbbf24; }
.badge-orange { background: rgba(249,115,22,0.2);   color: #fb923c; }
.badge-pink   { background: rgba(236,72,153,0.2);   color: #f472b6; }
.badge-red    { background: rgba(239,68,68,0.2);    color: #f87171; }
.badge-gray   { background: rgba(107,114,128,0.2);  color: #9ca3af; }
.badge-activo   { background: rgba(16,185,129,0.2); color: #34d399; }
.badge-inactivo { background: rgba(239,68,68,0.2);  color: #f87171; }
</style>
