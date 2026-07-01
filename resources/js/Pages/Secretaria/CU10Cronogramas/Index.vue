<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { errTexto, errFecha, errFechaFin } from '@/utils/validacion.js';

const canEdit = computed(() => ['propietario', 'director'].includes(usePage().props.auth?.user?.role));

const props = defineProps({
    cronogramas: Array,
    filtros:     Object,
});

// ── Constantes de etiquetas ───────────────────────────────────────────────────
const TIPO_LABELS = {
    inscripcion: 'Inscripción',
    clases:      'Clases',
    examenes:    'Exámenes',
    receso:      'Receso',
};

const TIPO_COLORS = {
    inscripcion: { bg: 'color-mix(in srgb, #3b82f6 12%, transparent)', color: '#3b82f6' },
    clases:      { bg: 'color-mix(in srgb, #10b981 12%, transparent)', color: '#10b981' },
    examenes:    { bg: 'color-mix(in srgb, #f59e0b 12%, transparent)', color: '#f59e0b' },
    receso:      { bg: 'color-mix(in srgb, #8b5cf6 12%, transparent)', color: '#8b5cf6' },
};

const MODALIDAD_LABELS = {
    semestral:  'Semestral',
    mensual:    'Mensual',
    anual:      'Anual',
    intensivo:  'Intensivo',
};

const MODALIDAD_COLORS = {
    semestral:  { bg: 'color-mix(in srgb,#8b5cf6 12%,transparent)', color: '#8b5cf6' },
    mensual:    { bg: 'color-mix(in srgb,#ec4899 12%,transparent)', color: '#ec4899' },
    anual:      { bg: 'color-mix(in srgb,#10b981 12%,transparent)', color: '#10b981' },
    intensivo:  { bg: 'color-mix(in srgb,#f59e0b 12%,transparent)', color: '#f59e0b' },
};

const ESTADO_CONFIG = {
    ABIERTA:  { label: 'Abierta',  bg: 'color-mix(in srgb,#22c55e 12%,transparent)', color: '#22c55e' },
    PRÓXIMA:  { label: 'Próxima',  bg: 'color-mix(in srgb,#f59e0b 12%,transparent)', color: '#f59e0b' },
    CERRADA:  { label: 'Cerrada',  bg: 'color-mix(in srgb,#ef4444 12%,transparent)', color: '#ef4444' },
    INACTIVO: { label: 'Inactivo', bg: 'color-mix(in srgb,#6b7280 12%,transparent)', color: '#6b7280' },
};

const tipoStyle      = (t) => TIPO_COLORS[t]      ?? { bg: 'var(--bg-color)', color: 'var(--text-secondary)' };
const modalidadStyle = (m) => MODALIDAD_COLORS[m]  ?? { bg: 'var(--bg-color)', color: 'var(--text-secondary)' };
const estadoStyle    = (e) => ESTADO_CONFIG[e]     ?? ESTADO_CONFIG['INACTIVO'];

const fmtFecha = (f) => {
    if (!f) return '—';
    // Extrae solo YYYY-MM-DD para evitar doble sufijo cuando Carbon serializa con 'T...'
    const raw = String(f).slice(0, 10);
    const [y, m, d] = raw.split('-').map(Number);
    if (!y || !m || !d) return '—';
    return new Date(y, m - 1, d).toLocaleDateString('es-BO', { day: '2-digit', month: 'short', year: 'numeric' });
};

// ── Filtros ───────────────────────────────────────────────────────────────────
const buscar          = ref(props.filtros?.buscar    ?? '');
const faseFiltro      = ref(props.filtros?.fase      ?? 'todas');
const modalidadFiltro = ref(props.filtros?.modalidad ?? 'todas');
const tipoFiltro      = ref(props.filtros?.tipo      ?? 'todos');

let buscarTimeout = null;
watch(buscar, () => {
    clearTimeout(buscarTimeout);
    buscarTimeout = setTimeout(aplicarFiltros, 400);
});
watch([faseFiltro, modalidadFiltro, tipoFiltro], aplicarFiltros);

function aplicarFiltros() {
    router.get(route('secretaria.cronogramas.index'), {
        buscar:     buscar.value          || undefined,
        fase:       faseFiltro.value      !== 'todas' ? faseFiltro.value      : undefined,
        modalidad:  modalidadFiltro.value !== 'todas' ? modalidadFiltro.value : undefined,
        tipo:       tipoFiltro.value      !== 'todos' ? tipoFiltro.value      : undefined,
    }, { preserveState: true, replace: true });
}

// ── Contadores resumen ────────────────────────────────────────────────────────
const totalGlobal   = computed(() => props.cronogramas.filter(c => !c.modalidad).length);
const totalEspecif  = computed(() => props.cronogramas.filter(c =>  c.modalidad).length);
const totalAbierta  = computed(() => props.cronogramas.filter(c => c.estado === 'ABIERTA').length);

// ── Modal Crear ───────────────────────────────────────────────────────────────
const showCrear    = ref(false);
const loadingCrear = ref(false);
const formCrear    = ref({
    nombre:       '',
    tipo_periodo: 'inscripcion',
    alcance:      'global',
    modalidad:    '',
    fecha_inicio: '',
    fecha_fin:    '',
    errors:       {},
});

function abrirCrear() {
    formCrear.value = { nombre: '', tipo_periodo: 'inscripcion', alcance: 'global', modalidad: '', fecha_inicio: '', fecha_fin: '', errors: {} };
    showCrear.value = true;
}

function validarCrear() {
    const e = {};
    const en = errTexto(formCrear.value.nombre, 'El nombre');                              if (en) e.nombre        = en;
    const ei = errFecha(formCrear.value.fecha_inicio, 'La fecha de inicio');               if (ei) e.fecha_inicio  = ei;
    const ef = errFechaFin(formCrear.value.fecha_fin, formCrear.value.fecha_inicio, 'La fecha de fin'); if (ef) e.fecha_fin = ef;
    formCrear.value.errors = e;
    return Object.keys(e).length === 0;
}

function submitCrear() {
    if (loadingCrear.value) return;
    if (!validarCrear()) return;
    loadingCrear.value = true;
    const data = {
        nombre:       formCrear.value.nombre,
        tipo_periodo: formCrear.value.tipo_periodo,
        fecha_inicio: formCrear.value.fecha_inicio,
        fecha_fin:    formCrear.value.fecha_fin,
        modalidad:    formCrear.value.alcance === 'global' ? null : formCrear.value.modalidad,
    };
    router.post(route('secretaria.cronogramas.store'), data, {
        onSuccess: () => { showCrear.value = false; },
        onError:   (e) => { formCrear.value.errors = e; },
        onFinish:  () => { loadingCrear.value = false; },
    });
}

// ── Modal Editar ──────────────────────────────────────────────────────────────
const showEditar       = ref(false);
const loadingEditar    = ref(false);
const cronogramaEdit   = ref(null);
const formEditar       = ref({
    nombre:       '',
    tipo_periodo: 'inscripcion',
    alcance:      'global',
    modalidad:    '',
    fecha_inicio: '',
    fecha_fin:    '',
    errors:       {},
});

function abrirEditar(c) {
    cronogramaEdit.value = c;
    formEditar.value = {
        nombre:       c.nombre,
        tipo_periodo: c.tipo_periodo,
        alcance:      c.modalidad ? 'modalidad' : 'global',
        modalidad:    c.modalidad ?? '',
        fecha_inicio: c.fecha_inicio ? String(c.fecha_inicio).slice(0, 10) : '',
        fecha_fin:    c.fecha_fin    ? String(c.fecha_fin).slice(0, 10)    : '',
        errors:       {},
    };
    showEditar.value = true;
}

function validarEditar() {
    const e = {};
    const en = errTexto(formEditar.value.nombre, 'El nombre');                                if (en) e.nombre        = en;
    const ei = errFecha(formEditar.value.fecha_inicio, 'La fecha de inicio');                 if (ei) e.fecha_inicio  = ei;
    const ef = errFechaFin(formEditar.value.fecha_fin, formEditar.value.fecha_inicio, 'La fecha de fin'); if (ef) e.fecha_fin = ef;
    formEditar.value.errors = e;
    return Object.keys(e).length === 0;
}

function submitEditar() {
    if (loadingEditar.value) return;
    if (!validarEditar()) return;
    loadingEditar.value = true;
    const data = {
        nombre:       formEditar.value.nombre,
        tipo_periodo: formEditar.value.tipo_periodo,
        fecha_inicio: formEditar.value.fecha_inicio,
        fecha_fin:    formEditar.value.fecha_fin,
        modalidad:    formEditar.value.alcance === 'global' ? null : formEditar.value.modalidad,
    };
    router.put(route('secretaria.cronogramas.update', cronogramaEdit.value.id_cronograma), data, {
        onSuccess: () => { showEditar.value = false; },
        onError:   (e) => { formEditar.value.errors = e; },
        onFinish:  () => { loadingEditar.value = false; },
    });
}

// ── Acciones rápidas ──────────────────────────────────────────────────────────
function toggleActivo(id) {
    router.patch(route('secretaria.cronogramas.toggle-activo', id));
}

function eliminar(c) {
    if (!confirm(`¿Eliminar el cronograma "${c.nombre}"? Esta acción no se puede deshacer.`)) return;
    router.delete(route('secretaria.cronogramas.destroy', c.id_cronograma));
}
</script>

<template>
    <Head title="Cronogramas Académicos" />
    <AdminLayout>
        <template #header>
            <span class="font-semibold text-base" style="color: var(--text-color);">Cronogramas Académicos</span>
        </template>

        <div class="space-y-5">

            <!-- Flash -->
            <div v-if="$page.props.flash?.success"
                 class="rounded-lg px-4 py-3 text-sm font-medium"
                 style="background-color:color-mix(in srgb,#22c55e 12%,transparent); color:#15803d; border:1px solid color-mix(in srgb,#22c55e 30%,transparent);">
                {{ $page.props.flash.success }}
            </div>

            <!-- Encabezado + botón -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4 text-sm" style="color: var(--text-secondary);">
                    <span><strong style="color:var(--text-color);">{{ cronogramas.length }}</strong> cronogramas</span>
                    <span class="opacity-40">·</span>
                    <span><strong style="color:#3b82f6;">{{ totalGlobal }}</strong> globales</span>
                    <span class="opacity-40">·</span>
                    <span><strong style="color:#8b5cf6;">{{ totalEspecif }}</strong> por modalidad</span>
                    <span class="opacity-40">·</span>
                    <span><strong style="color:#22c55e;">{{ totalAbierta }}</strong> abiertos</span>
                </div>
                <button v-if="canEdit" @click="abrirCrear"
                        class="shrink-0 px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                        style="background-color: var(--primary-color); color: var(--primary-text);">
                    + Nuevo Cronograma
                </button>
            </div>

            <!-- Filtros -->
            <div class="rounded-xl p-4 flex flex-wrap gap-3"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <input v-model="buscar" type="text" placeholder="Buscar cronograma..."
                       class="flex-1 min-w-[180px] bg-transparent border rounded-lg px-3 py-2 text-sm outline-none"
                       style="border-color: var(--border-color); color: var(--text-color);" />

                <!-- Tipo de fase -->
                <select v-model="tipoFiltro"
                        class="border rounded-lg px-3 py-2 text-sm outline-none cursor-pointer"
                        style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);">
                    <option value="todos"       class="bg-[var(--card-bg)]">Todos los tipos</option>
                    <option value="inscripcion" class="bg-[var(--card-bg)]">Inscripción</option>
                    <option value="clases"      class="bg-[var(--card-bg)]">Clases</option>
                    <option value="examenes"    class="bg-[var(--card-bg)]">Exámenes</option>
                    <option value="receso"      class="bg-[var(--card-bg)]">Receso</option>
                </select>

                <!-- Modalidad -->
                <select v-model="modalidadFiltro"
                        class="border rounded-lg px-3 py-2 text-sm outline-none cursor-pointer"
                        style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);">
                    <option value="todas"     class="bg-[var(--card-bg)]">Todas las modalidades</option>
                    <option value="global"    class="bg-[var(--card-bg)]">Solo globales</option>
                    <option value="semestral" class="bg-[var(--card-bg)]">Semestral</option>
                    <option value="mensual"   class="bg-[var(--card-bg)]">Mensual</option>
                    <option value="anual"     class="bg-[var(--card-bg)]">Anual</option>
                    <option value="intensivo" class="bg-[var(--card-bg)]">Intensivo</option>
                </select>

                <!-- Estado / fase -->
                <select v-model="faseFiltro"
                        class="border rounded-lg px-3 py-2 text-sm outline-none cursor-pointer"
                        style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);">
                    <option value="todas"    class="bg-[var(--card-bg)]">Todas las fases</option>
                    <option value="abierta"  class="bg-[var(--card-bg)]">Abiertas</option>
                    <option value="proxima"  class="bg-[var(--card-bg)]">Próximas</option>
                    <option value="cerrada"  class="bg-[var(--card-bg)]">Cerradas</option>
                    <option value="inactiva" class="bg-[var(--card-bg)]">Inactivos</option>
                </select>
            </div>

            <!-- Tabla -->
            <div class="rounded-xl overflow-hidden"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr style="background-color:color-mix(in srgb,var(--text-color) 4%,transparent); border-bottom:1px solid var(--border-color);">
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--text-secondary);">Nombre</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--text-secondary);">Fase</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--text-secondary);">Fechas</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--text-secondary);">Modalidad</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider" style="color:var(--text-secondary);">Estado</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color:var(--text-secondary);">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="cronogramas.length === 0">
                                <td colspan="6" class="px-5 py-12 text-center text-sm" style="color:var(--text-secondary);">
                                    No se encontraron cronogramas.
                                </td>
                            </tr>
                            <tr v-for="c in cronogramas" :key="c.id_cronograma"
                                style="border-top:1px solid var(--border-color);"
                                onmouseover="this.style.backgroundColor='color-mix(in srgb,var(--text-color) 3%,transparent)'"
                                onmouseout="this.style.backgroundColor='transparent'">

                                <!-- Nombre -->
                                <td class="px-5 py-4">
                                    <span class="text-sm font-medium" style="color:var(--text-color);">{{ c.nombre }}</span>
                                </td>

                                <!-- Fase (tipo_periodo) -->
                                <td class="px-5 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold"
                                          :style="`background-color:${tipoStyle(c.tipo_periodo).bg}; color:${tipoStyle(c.tipo_periodo).color};`">
                                        {{ TIPO_LABELS[c.tipo_periodo] ?? c.tipo_periodo }}
                                    </span>
                                </td>

                                <!-- Fechas -->
                                <td class="px-5 py-4">
                                    <div class="text-xs space-y-0.5" style="color:var(--text-secondary);">
                                        <div><span class="font-medium" style="color:var(--text-color);">Inicio:</span> {{ fmtFecha(c.fecha_inicio) }}</div>
                                        <div><span class="font-medium" style="color:var(--text-color);">Fin:</span>    {{ fmtFecha(c.fecha_fin) }}</div>
                                    </div>
                                </td>

                                <!-- Modalidad (alcance) -->
                                <td class="px-5 py-4">
                                    <span v-if="!c.modalidad"
                                          class="px-2.5 py-1 rounded-full text-xs font-bold"
                                          style="background-color:color-mix(in srgb,#3b82f6 12%,transparent); color:#3b82f6;">
                                        GLOBAL
                                    </span>
                                    <span v-else
                                          class="px-2.5 py-1 rounded-full text-xs font-semibold"
                                          :style="`background-color:${modalidadStyle(c.modalidad).bg}; color:${modalidadStyle(c.modalidad).color};`">
                                        {{ MODALIDAD_LABELS[c.modalidad] ?? c.modalidad }}
                                    </span>
                                </td>

                                <!-- Estado -->
                                <td class="px-5 py-4 text-center">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                          :style="`background-color:${estadoStyle(c.estado).bg}; color:${estadoStyle(c.estado).color};`">
                                        {{ estadoStyle(c.estado).label }}
                                    </span>
                                </td>

                                <!-- Acciones -->
                                <td class="px-5 py-4">
                                    <div v-if="canEdit" class="flex items-center justify-end gap-2">
                                        <button @click="toggleActivo(c.id_cronograma)"
                                                class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-all"
                                                :style="c.activo
                                                    ? 'border-color:var(--border-color); color:var(--text-secondary);'
                                                    : 'border-color:#22c55e; color:#22c55e;'">
                                            {{ c.activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                        <button @click="abrirEditar(c)"
                                                class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-all"
                                                style="border-color:var(--border-color); color:var(--text-color);">
                                            Editar
                                        </button>
                                        <button @click="eliminar(c)"
                                                class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-all"
                                                style="border-color:color-mix(in srgb,#ef4444 40%,transparent); color:#ef4444;">
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── MODAL CREAR ─────────────────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showCrear"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 style="background-color:rgba(0,0,0,0.5);"
                 @click.self="showCrear = false">
                <div class="w-full max-w-md rounded-xl border shadow-xl"
                     style="background-color:var(--card-bg); border-color:var(--border-color);">

                    <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color:var(--border-color);">
                        <h2 class="font-bold text-base" style="color:var(--text-color);">Nuevo Cronograma</h2>
                        <button @click="showCrear = false" class="text-lg leading-none" style="color:var(--text-secondary);">&times;</button>
                    </div>

                    <div class="px-6 py-5 space-y-4">

                        <!-- Nombre -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color:var(--text-secondary);">Nombre *</label>
                            <input v-model="formCrear.nombre" type="text" maxlength="100"
                                   placeholder="Ej: Inscripciones Semestre I 2026"
                                   class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                   style="background-color:var(--card-bg); border-color:var(--border-color); color:var(--text-color);" />
                            <p v-if="formCrear.errors.nombre" class="text-xs mt-1" style="color:#ef4444;">{{ formCrear.errors.nombre }}</p>
                        </div>

                        <!-- Fase (tipo_periodo) -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color:var(--text-secondary);">Fase académica *</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button v-for="(label, key) in TIPO_LABELS" :key="key"
                                        type="button"
                                        @click="formCrear.tipo_periodo = key"
                                        class="py-2 rounded-lg text-xs font-semibold border transition-all"
                                        :style="formCrear.tipo_periodo === key
                                            ? `background-color:${tipoStyle(key).bg}; color:${tipoStyle(key).color}; border-color:${tipoStyle(key).color};`
                                            : 'background-color:transparent; color:var(--text-secondary); border-color:var(--border-color);'">
                                    {{ label }}
                                </button>
                            </div>
                            <p v-if="formCrear.errors.tipo_periodo" class="text-xs mt-1" style="color:#ef4444;">{{ formCrear.errors.tipo_periodo }}</p>
                        </div>

                        <!-- Modalidad (alcance) -->
                        <div>
                            <label class="block text-xs font-semibold mb-2" style="color:var(--text-secondary);">Modalidad *</label>
                            <div class="flex rounded-lg overflow-hidden border" style="border-color:var(--border-color);">
                                <button type="button" @click="formCrear.alcance = 'global'"
                                        class="flex-1 py-2 text-xs font-semibold transition-all"
                                        :style="formCrear.alcance === 'global'
                                            ? 'background-color:#3b82f6; color:white;'
                                            : 'background-color:transparent; color:var(--text-secondary);'">
                                    Global
                                </button>
                                <button type="button" @click="formCrear.alcance = 'modalidad'"
                                        class="flex-1 py-2 text-xs font-semibold transition-all"
                                        :style="formCrear.alcance === 'modalidad'
                                            ? 'background-color:#8b5cf6; color:white;'
                                            : 'background-color:transparent; color:var(--text-secondary);'">
                                    Por modalidad
                                </button>
                            </div>
                            <!-- Selector de modalidad específica -->
                            <div v-if="formCrear.alcance === 'modalidad'" class="grid grid-cols-2 gap-2 mt-2">
                                <button v-for="(label, key) in MODALIDAD_LABELS" :key="key"
                                        type="button"
                                        @click="formCrear.modalidad = key"
                                        class="py-2 rounded-lg text-xs font-semibold border transition-all"
                                        :style="formCrear.modalidad === key
                                            ? `background-color:${modalidadStyle(key).bg}; color:${modalidadStyle(key).color}; border-color:${modalidadStyle(key).color};`
                                            : 'background-color:transparent; color:var(--text-secondary); border-color:var(--border-color);'">
                                    {{ label }}
                                </button>
                            </div>
                            <p v-if="formCrear.errors.modalidad" class="text-xs mt-1" style="color:#ef4444;">{{ formCrear.errors.modalidad }}</p>
                        </div>

                        <!-- Fechas -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color:var(--text-secondary);">Fecha inicio *</label>
                                <input v-model="formCrear.fecha_inicio" type="date"
                                       class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                       style="background-color:var(--bg-color); border-color:var(--border-color); color:var(--text-color);" />
                                <p v-if="formCrear.errors.fecha_inicio" class="text-xs mt-1" style="color:#ef4444;">{{ formCrear.errors.fecha_inicio }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color:var(--text-secondary);">Fecha fin *</label>
                                <input v-model="formCrear.fecha_fin" type="date"
                                       class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                       style="background-color:var(--bg-color); border-color:var(--border-color); color:var(--text-color);" />
                                <p v-if="formCrear.errors.fecha_fin" class="text-xs mt-1" style="color:#ef4444;">{{ formCrear.errors.fecha_fin }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 px-6 py-4 border-t" style="border-color:var(--border-color);">
                        <button type="button" @click="showCrear = false"
                                class="px-4 py-2 text-sm font-medium rounded-lg border"
                                style="border-color:var(--border-color); color:var(--text-secondary);">
                            Cancelar
                        </button>
                        <button type="button" @click="submitCrear" :disabled="loadingCrear"
                                class="px-5 py-2 text-sm font-semibold rounded-lg transition-opacity"
                                :style="loadingCrear
                                    ? 'background-color:var(--primary-color); color:var(--primary-text); opacity:0.6; cursor:not-allowed;'
                                    : 'background-color:var(--primary-color); color:var(--primary-text);'">
                            {{ loadingCrear ? 'Guardando...' : 'Crear Cronograma' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── MODAL EDITAR ────────────────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showEditar"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 style="background-color:rgba(0,0,0,0.5);"
                 @click.self="showEditar = false">
                <div class="w-full max-w-md rounded-xl border shadow-xl"
                     style="background-color:var(--card-bg); border-color:var(--border-color);">

                    <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color:var(--border-color);">
                        <h2 class="font-bold text-base" style="color:var(--text-color);">Editar Cronograma</h2>
                        <button @click="showEditar = false" class="text-lg leading-none" style="color:var(--text-secondary);">&times;</button>
                    </div>

                    <div class="px-6 py-5 space-y-4">

                        <!-- Nombre -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color:var(--text-secondary);">Nombre *</label>
                            <input v-model="formEditar.nombre" type="text" maxlength="100"
                                   class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                   style="background-color:var(--card-bg); border-color:var(--border-color); color:var(--text-color);" />
                            <p v-if="formEditar.errors.nombre" class="text-xs mt-1" style="color:#ef4444;">{{ formEditar.errors.nombre }}</p>
                        </div>

                        <!-- Fase -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color:var(--text-secondary);">Fase académica *</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button v-for="(label, key) in TIPO_LABELS" :key="key"
                                        type="button"
                                        @click="formEditar.tipo_periodo = key"
                                        class="py-2 rounded-lg text-xs font-semibold border transition-all"
                                        :style="formEditar.tipo_periodo === key
                                            ? `background-color:${tipoStyle(key).bg}; color:${tipoStyle(key).color}; border-color:${tipoStyle(key).color};`
                                            : 'background-color:transparent; color:var(--text-secondary); border-color:var(--border-color);'">
                                    {{ label }}
                                </button>
                            </div>
                            <p v-if="formEditar.errors.tipo_periodo" class="text-xs mt-1" style="color:#ef4444;">{{ formEditar.errors.tipo_periodo }}</p>
                        </div>

                        <!-- Modalidad -->
                        <div>
                            <label class="block text-xs font-semibold mb-2" style="color:var(--text-secondary);">Modalidad *</label>
                            <div class="flex rounded-lg overflow-hidden border" style="border-color:var(--border-color);">
                                <button type="button" @click="formEditar.alcance = 'global'"
                                        class="flex-1 py-2 text-xs font-semibold transition-all"
                                        :style="formEditar.alcance === 'global'
                                            ? 'background-color:#3b82f6; color:white;'
                                            : 'background-color:transparent; color:var(--text-secondary);'">
                                    Global
                                </button>
                                <button type="button" @click="formEditar.alcance = 'modalidad'"
                                        class="flex-1 py-2 text-xs font-semibold transition-all"
                                        :style="formEditar.alcance === 'modalidad'
                                            ? 'background-color:#8b5cf6; color:white;'
                                            : 'background-color:transparent; color:var(--text-secondary);'">
                                    Por modalidad
                                </button>
                            </div>
                            <div v-if="formEditar.alcance === 'modalidad'" class="grid grid-cols-2 gap-2 mt-2">
                                <button v-for="(label, key) in MODALIDAD_LABELS" :key="key"
                                        type="button"
                                        @click="formEditar.modalidad = key"
                                        class="py-2 rounded-lg text-xs font-semibold border transition-all"
                                        :style="formEditar.modalidad === key
                                            ? `background-color:${modalidadStyle(key).bg}; color:${modalidadStyle(key).color}; border-color:${modalidadStyle(key).color};`
                                            : 'background-color:transparent; color:var(--text-secondary); border-color:var(--border-color);'">
                                    {{ label }}
                                </button>
                            </div>
                            <p v-if="formEditar.errors.modalidad" class="text-xs mt-1" style="color:#ef4444;">{{ formEditar.errors.modalidad }}</p>
                        </div>

                        <!-- Fechas -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color:var(--text-secondary);">Fecha inicio *</label>
                                <input v-model="formEditar.fecha_inicio" type="date"
                                       class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                       style="background-color:var(--bg-color); border-color:var(--border-color); color:var(--text-color);" />
                                <p v-if="formEditar.errors.fecha_inicio" class="text-xs mt-1" style="color:#ef4444;">{{ formEditar.errors.fecha_inicio }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color:var(--text-secondary);">Fecha fin *</label>
                                <input v-model="formEditar.fecha_fin" type="date"
                                       class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                       style="background-color:var(--bg-color); border-color:var(--border-color); color:var(--text-color);" />
                                <p v-if="formEditar.errors.fecha_fin" class="text-xs mt-1" style="color:#ef4444;">{{ formEditar.errors.fecha_fin }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 px-6 py-4 border-t" style="border-color:var(--border-color);">
                        <button type="button" @click="showEditar = false"
                                class="px-4 py-2 text-sm font-medium rounded-lg border"
                                style="border-color:var(--border-color); color:var(--text-secondary);">
                            Cancelar
                        </button>
                        <button type="button" @click="submitEditar" :disabled="loadingEditar"
                                class="px-5 py-2 text-sm font-semibold rounded-lg transition-opacity"
                                :style="loadingEditar
                                    ? 'background-color:var(--primary-color); color:var(--primary-text); opacity:0.6; cursor:not-allowed;'
                                    : 'background-color:var(--primary-color); color:var(--primary-text);'">
                            {{ loadingEditar ? 'Guardando...' : 'Guardar cambios' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AdminLayout>
</template>
