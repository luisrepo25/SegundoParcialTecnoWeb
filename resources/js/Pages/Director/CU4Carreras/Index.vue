<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { errCodigo, errTexto, errEntero, errDecimal } from '@/utils/validacion.js';

const canEdit = computed(() => ['propietario', 'director'].includes(usePage().props.auth?.user?.role));

const props = defineProps({
    carreras: Object,
    filtros:  Object,
});

// ── Filtros ───────────────────────────────────────────────────────────────────
const buscar       = ref(props.filtros?.buscar ?? '');
const tipoFiltro   = ref(props.filtros?.tipo   ?? '');
const activoFiltro = ref(props.filtros?.activo ?? 'todos');

const TIPOS = [
    { value: 'tecnico',          label: 'Técnico' },
    { value: 'tecnico_superior', label: 'Técnico Superior' },
    { value: 'curso_libre',      label: 'Curso Libre' },
];

let timeout = null;
watch(buscar, () => { clearTimeout(timeout); timeout = setTimeout(aplicarFiltros, 400); });
watch([tipoFiltro, activoFiltro], aplicarFiltros);

function aplicarFiltros() {
    router.get(route('director.carreras.index'), {
        buscar: buscar.value || undefined,
        tipo:   tipoFiltro.value || undefined,
        activo: activoFiltro.value !== 'todos' ? activoFiltro.value : undefined,
    }, { preserveState: true, replace: true });
}

// ── Modal Crear / Editar ──────────────────────────────────────────────────────
const showModal   = ref(false);
const modoEdicion = ref(false);
const editandoId  = ref(null);

const MODALIDADES = [
    { value: 'semestral', label: 'Semestral (2 períodos/año)' },
    { value: 'anual',     label: 'Anual (1 período/año)' },
    { value: 'mensual',   label: 'Mensual' },
];

const MODALIDAD_LABELS = { semestral: 'Semestral', anual: 'Anual', mensual: 'Mensual' };

const MAX_SUGERIDO = { semestral: 5, anual: 10, mensual: 1 };

const form = useForm({
    codigo: '', nombre: '', descripcion: '',
    tipo: 'tecnico_superior', modalidad: 'semestral',
    duracion_unidad: 'anos',
    max_materias: 5, duracion_niveles: '', costo_carrera_completa: '',
});

const esCursoLibre = computed(() => form.tipo === 'curso_libre');

// Cuando cambia tipo: ajustar defaults
watch(() => form.tipo, (val) => {
    if (val === 'curso_libre') {
        form.modalidad       = '';
        form.duracion_unidad = 'meses';
        form.max_materias    = 6;
        if (!form.duracion_niveles) form.duracion_niveles = 6;
    } else {
        form.modalidad       = form.modalidad || 'semestral';
        form.duracion_unidad = 'anos';
    }
});

// Cuando cambia modalidad, sugerir max_materias automáticamente
watch(() => form.modalidad, (val) => {
    if (val && MAX_SUGERIDO[val]) form.max_materias = MAX_SUGERIDO[val];
});

function abrirCrear() {
    form.reset(); form.clearErrors();
    form.tipo = 'tecnico_superior';
    modoEdicion.value = false;
    editandoId.value  = null;
    showModal.value   = true;
}

function abrirEditar(c) {
    form.reset(); form.clearErrors();
    form.codigo                 = c.codigo;
    form.nombre                 = c.nombre;
    form.descripcion            = c.descripcion ?? '';
    form.tipo                   = c.tipo;
    form.modalidad              = c.modalidad ?? (c.tipo === 'curso_libre' ? '' : 'semestral');
    form.duracion_unidad        = c.duracion_unidad ?? (c.tipo === 'curso_libre' ? 'meses' : 'anos');
    form.max_materias           = c.max_materias ?? 5;
    form.duracion_niveles       = c.duracion_niveles;
    form.costo_carrera_completa = c.costo_carrera_completa ?? '';
    modoEdicion.value = true;
    editandoId.value  = c.id_carrera;
    showModal.value   = true;
}

const fe = ref({});

function validarCampos() {
    const e = {};
    const ec = errCodigo(form.codigo, 'El código');                                         if (ec) e.codigo    = ec;
    const en = errTexto(form.nombre, 'El nombre');                                          if (en) e.nombre    = en;
    const ed = errEntero(form.duracion_niveles, 'La duración', 1, esCursoLibre.value ? 60 : 20); if (ed) e.duracion_niveles = ed;
    const em = errEntero(form.max_materias, 'Las materias por período', 1, 30);             if (em) e.max_materias = em;
    if (!esCursoLibre.value && !form.modalidad) e.modalidad = 'La modalidad es obligatoria.';
    if (form.costo_carrera_completa !== '' && form.costo_carrera_completa !== null) {
        const ep = errDecimal(form.costo_carrera_completa, 'El costo total');               if (ep) e.costo_carrera_completa = ep;
    }
    fe.value = e;
    return Object.keys(e).length === 0;
}

watch(() => [form.codigo, form.nombre, form.duracion_niveles, form.max_materias, form.costo_carrera_completa, form.modalidad], () => {
    if (Object.keys(fe.value).length) validarCampos();
});

function cerrar() { showModal.value = false; form.reset(); form.clearErrors(); fe.value = {}; }

function guardar() {
    if (!validarCampos()) return;
    if (modoEdicion.value) {
        form.put(route('director.carreras.update', editandoId.value), { onSuccess: cerrar });
    } else {
        form.post(route('director.carreras.store'), { onSuccess: cerrar });
    }
}

function toggleActivo(c) {
    const accion = c.activo ? 'desactivar' : 'activar';
    if (!confirm(`¿Desea ${accion} la carrera "${c.nombre}"?`)) return;
    router.patch(route('director.carreras.toggle-activo', c.id_carrera));
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const TIPO_BADGE = {
    tecnico:          { label: 'Técnico',    color: 'badge-blue'   },
    tecnico_superior: { label: 'T. Superior', color: 'badge-purple' },
    curso_libre:      { label: 'C. Libre',   color: 'badge-yellow' },
};
function tipoBadge(tipo) { return TIPO_BADGE[tipo] ?? { label: tipo, color: 'badge-gray' }; }

function formatCosto(val) {
    if (!val) return '—';
    return 'Bs ' + parseFloat(val).toLocaleString('es-BO', { minimumFractionDigits: 2 });
}
</script>

<template>
    <Head title="Gestión de Carreras" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--text-color);">
                Gestión de Carreras
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

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
                    <div class="flex gap-2 flex-1 flex-wrap">
                        <input v-model="buscar" type="text" placeholder="Buscar por nombre o código..."
                            class="flex-1 min-w-[200px] rounded-lg px-3 py-2 text-sm focus:outline-none"
                            style="background-color: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color);" />
                        <select v-model="tipoFiltro"
                            class="rounded-lg px-3 py-2 text-sm focus:outline-none"
                            style="background-color: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color);">
                            <option value="">Todos los tipos</option>
                            <option v-for="t in TIPOS" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                        <select v-model="activoFiltro"
                            class="rounded-lg px-3 py-2 text-sm focus:outline-none"
                            style="background-color: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color);">
                            <option value="todos">Todos</option>
                            <option value="1">Activas</option>
                            <option value="0">Inactivas</option>
                        </select>
                    </div>
                    <button v-if="canEdit" @click="abrirCrear"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition"
                        style="background-color: var(--primary-color); color: var(--primary-text);">
                        + Nueva Carrera
                    </button>
                </div>

                <!-- Tabla -->
                <div class="overflow-hidden rounded-xl shadow" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr style="background-color: var(--bg-color);">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color: var(--text-secondary);">Código</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Carrera</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color: var(--text-secondary);">Tipo</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider whitespace-nowrap hidden md:table-cell" style="color: var(--text-secondary);">Modalidad</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider whitespace-nowrap hidden md:table-cell" style="color: var(--text-secondary);">Duración</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider whitespace-nowrap hidden lg:table-cell" style="color: var(--text-secondary);">Costo Total</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color: var(--text-secondary);">Estado</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color: var(--text-secondary);">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in carreras.data" :key="c.id_carrera"
                                class="border-t" style="border-color: var(--border-color);">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-mono text-sm font-semibold" style="color: var(--primary-color);">{{ c.codigo }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <Link :href="route('director.carreras.materias', c.id_carrera)"
                                        class="font-medium text-sm hover:underline"
                                        style="color: var(--primary-color);">
                                        {{ c.nombre }}
                                    </Link>
                                    <div v-if="c.descripcion" class="text-xs mt-0.5 line-clamp-1" style="color: var(--text-secondary);">{{ c.descripcion }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['badge', tipoBadge(c.tipo).color]">{{ tipoBadge(c.tipo).label }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm hidden md:table-cell" style="color: var(--text-color);">
                                    <span v-if="c.modalidad" class="text-xs font-semibold px-2 py-0.5 rounded"
                                          :style="c.modalidad === 'anual'
                                              ? 'background-color:color-mix(in srgb,#10b981 15%,transparent);color:#10b981;'
                                              : c.modalidad === 'mensual'
                                              ? 'background-color:color-mix(in srgb,#3b82f6 15%,transparent);color:#3b82f6;'
                                              : 'background-color:color-mix(in srgb,#8b5cf6 15%,transparent);color:#8b5cf6;'">
                                        {{ MODALIDAD_LABELS[c.modalidad] }}
                                    </span>
                                    <span v-else class="text-xs" style="color: var(--text-muted);">—</span>
                                </td>
                                <td class="px-4 py-3 text-sm hidden md:table-cell" style="color: var(--text-color);">
                                    {{ c.duracion_niveles }}
                                    <span class="text-xs" style="color: var(--text-secondary);">
                                        {{ c.duracion_unidad === 'meses' ? 'mes(es)' : 'año(s)' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm hidden lg:table-cell" style="color: var(--text-color);">
                                    {{ formatCosto(c.costo_carrera_completa) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['badge', c.activo ? 'badge-activo' : 'badge-inactivo']">
                                        {{ c.activo ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-1">
                                        <template v-if="canEdit">
                                            <button @click="abrirEditar(c)" class="btn-accion" style="color: #d97706;">Editar</button>
                                            <button @click="toggleActivo(c)" class="btn-accion"
                                                :style="c.activo ? 'color: #dc2626;' : 'color: #059669;'">
                                                {{ c.activo ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="carreras.data.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-sm" style="color: var(--text-secondary);">
                                    No se encontraron carreras.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>

                    <!-- Paginación -->
                    <Pagination :data="carreras" label="carreras" />
                </div>
            </div>
        </div>

        <!-- ── Modal Crear / Editar ────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="cerrar">
                <div class="modal-box">
                    <div class="modal-header">
                        <h3 class="modal-title">{{ modoEdicion ? 'Editar Carrera' : 'Nueva Carrera' }}</h3>
                        <button @click="cerrar" class="modal-close">&times;</button>
                    </div>

                    <form @submit.prevent="guardar" class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="field-label">Código *</label>
                                <input v-model="form.codigo" type="text" class="field-input" placeholder="Ej: ING-SIS" />
                                <p v-if="fe.codigo || form.errors.codigo" class="field-error">{{ fe.codigo || form.errors.codigo }}</p>
                            </div>
                            <div>
                                <label class="field-label">Tipo *</label>
                                <select v-model="form.tipo" class="field-input">
                                    <option v-for="t in TIPOS" :key="t.value" :value="t.value">{{ t.label }}</option>
                                </select>
                                <p v-if="form.errors.tipo" class="field-error">{{ form.errors.tipo }}</p>
                            </div>
                        </div>

                        <!-- Modalidad: solo para carreras/técnicos, no para cursos -->
                        <div v-if="!esCursoLibre">
                            <label class="field-label">Modalidad de períodos *</label>
                            <div class="grid grid-cols-3 gap-2 mt-1">
                                <button v-for="m in MODALIDADES" :key="m.value"
                                    type="button"
                                    @click="form.modalidad = m.value"
                                    class="px-2 py-2 rounded-lg text-xs font-semibold border transition text-center"
                                    :style="form.modalidad === m.value
                                        ? (m.value === 'anual'
                                            ? 'background-color:color-mix(in srgb,#10b981 20%,transparent);color:#10b981;border-color:#10b981;'
                                            : m.value === 'mensual'
                                            ? 'background-color:color-mix(in srgb,#3b82f6 20%,transparent);color:#3b82f6;border-color:#3b82f6;'
                                            : 'background-color:color-mix(in srgb,#8b5cf6 20%,transparent);color:#8b5cf6;border-color:#8b5cf6;')
                                        : 'background:transparent;color:var(--text-secondary);border-color:var(--border-color);'">
                                    {{ m.label }}
                                </button>
                            </div>
                            <p class="text-[11px] mt-1" style="color: var(--text-secondary);">
                                Define cuántos períodos se crean por año para esta carrera.
                            </p>
                            <p v-if="fe.modalidad || form.errors.modalidad" class="field-error">{{ fe.modalidad || form.errors.modalidad }}</p>
                        </div>

                        <!-- Cursos libres: info de lanzamiento manual -->
                        <div v-else class="rounded-lg border px-3 py-2.5 text-xs"
                             style="border-color:color-mix(in srgb,#f59e0b 35%,transparent); background-color:color-mix(in srgb,#f59e0b 8%,transparent); color:var(--text-secondary);">
                            <p class="font-semibold mb-0.5" style="color:#f59e0b;">Lanzamientos manuales</p>
                            Cada vez que abras el curso se crea un período con fechas propias. No hay ciclo fijo anual.
                            Podés clonar un período anterior para re-lanzar el mismo curso.
                        </div>

                        <div>
                            <label class="field-label">Nombre *</label>
                            <input v-model="form.nombre" type="text" class="field-input" placeholder="Ej: Ingeniería de Sistemas" />
                            <p v-if="fe.nombre || form.errors.nombre" class="field-error">{{ fe.nombre || form.errors.nombre }}</p>
                        </div>

                        <div>
                            <label class="field-label">Descripción</label>
                            <textarea v-model="form.descripcion" rows="3" class="field-input" placeholder="Descripción de la carrera..."></textarea>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="field-label">
                                    {{ esCursoLibre ? 'Duración (meses)' : 'Duración (años)' }} *
                                </label>
                                <input v-model="form.duracion_niveles" type="number"
                                       :min="1" :max="esCursoLibre ? 60 : 20"
                                       class="field-input"
                                       :placeholder="esCursoLibre ? 'Ej: 6' : 'Ej: 4'" />
                                <p class="text-[11px] mt-0.5" style="color: var(--text-secondary);">
                                    <template v-if="esCursoLibre">duración de un lanzamiento</template>
                                    <template v-else>años de la carrera</template>
                                </p>
                                <p v-if="fe.duracion_niveles || form.errors.duracion_niveles" class="field-error">{{ fe.duracion_niveles || form.errors.duracion_niveles }}</p>
                            </div>
                            <div>
                                <label class="field-label">Materias por período *</label>
                                <input v-model.number="form.max_materias" type="number" min="1" max="30" class="field-input" />
                                <p class="text-[11px] mt-0.5" style="color: var(--text-secondary);">
                                    <template v-if="esCursoLibre">materias en el curso</template>
                                    <template v-else>
                                        {{ form.modalidad === 'semestral' ? '× 2 sem/año' : form.modalidad === 'mensual' ? '× 12 mes/año' : '× 1 año' }}
                                        = {{ form.max_materias * (form.modalidad === 'semestral' ? 2 : form.modalidad === 'mensual' ? 12 : 1) }} mat/año por nivel
                                    </template>
                                </p>
                                <p v-if="fe.max_materias || form.errors.max_materias" class="field-error">{{ fe.max_materias || form.errors.max_materias }}</p>
                            </div>
                            <div>
                                <label class="field-label">Costo Total (Bs)</label>
                                <input v-model="form.costo_carrera_completa" type="number" min="0" step="0.01" class="field-input" placeholder="Ej: 15000.00" />
                                <p v-if="fe.costo_carrera_completa || form.errors.costo_carrera_completa" class="field-error">{{ fe.costo_carrera_completa || form.errors.costo_carrera_completa }}</p>
                            </div>
                        </div>

                        <div class="modal-footer border-t pt-4" style="border-color: var(--border-color);">
                            <button type="button" @click="cerrar" class="btn-secondary">Cancelar</button>
                            <button type="submit" :disabled="form.processing"
                                class="rounded-lg px-4 py-2 text-sm font-medium transition disabled:opacity-50"
                                style="background-color: var(--primary-color); color: var(--primary-text);">
                                {{ form.processing ? 'Guardando...' : (modoEdicion ? 'Actualizar' : 'Registrar Carrera') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
.modal-overlay {
    position: fixed; inset: 0; z-index: 50;
    display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,0.55); padding: 1rem;
}
.modal-box {
    width: 100%; max-width: 32rem; border-radius: 1rem; overflow: hidden;
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
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; }

.field-label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; color: var(--text-color); }
.field-input {
    width: 100%; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem;
    background-color: var(--bg-color); color: var(--text-color);
    border: 1px solid var(--border-color); outline: none;
}
.field-input:focus { border-color: var(--primary-color); }
.field-error  { margin-top: 0.25rem; font-size: 0.75rem; color: #ef4444; }

.btn-secondary {
    border-radius: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem;
    background: none; cursor: pointer; transition: opacity 0.15s;
    color: var(--text-color); border: 1px solid var(--border-color);
}
.btn-accion {
    padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500;
    background: none; border: none; cursor: pointer; transition: opacity 0.15s;
}
.btn-accion:hover { opacity: 0.7; }

.badge { display: inline-flex; border-radius: 9999px; padding: 0.125rem 0.625rem; font-size: 0.75rem; font-weight: 600; }
.badge-blue   { background: rgba(59,130,246,0.2);  color: #60a5fa; }
.badge-purple { background: rgba(139,92,246,0.2);  color: #a78bfa; }
.badge-yellow { background: rgba(245,158,11,0.2);  color: #fbbf24; }
.badge-gray   { background: rgba(107,114,128,0.2); color: #9ca3af; }
.badge-activo   { background: rgba(16,185,129,0.2); color: #34d399; }
.badge-inactivo { background: rgba(239,68,68,0.2);  color: #f87171; }

.line-clamp-1 { overflow: hidden; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; }
</style>
