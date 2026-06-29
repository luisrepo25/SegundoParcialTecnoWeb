<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    grupo:       { type: Object,  default: () => ({}) },
    inscritos:   { type: Array,   default: () => [] },
    cronograma:  { type: Object,  default: null },
    actaCerrada: { type: Boolean, default: false },
    hoy:         { type: String,  default: '' },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

// ── Tipos de evaluación ───────────────────────────────────────────────────────
const TIPOS = [
    { key: 'parcial1', label: '1er Parcial',  color: '#6366f1', porcentaje: 25 },
    { key: 'parcial2', label: '2do Parcial',  color: '#8b5cf6', porcentaje: 25 },
    { key: 'final',    label: 'Examen Final', color: '#10b981', porcentaje: 40 },
    { key: 'otros',    label: null,           color: '#f59e0b', porcentaje: 10 },
];

// ── Helpers ───────────────────────────────────────────────────────────────────
const fmtFecha = (f) => {
    if (!f) return '';
    return new Date(f + 'T12:00:00').toLocaleDateString('es-BO', { day: '2-digit', month: 'short', year: 'numeric' });
};
const fmtHora = (h) => h ? h.slice(0, 5) : '';

const estadoBadge = (estado, aprobado) => {
    if (aprobado) return { label: 'Aprobado', color: '#10b981' };
    const map = {
        activo:              { label: 'Activo',     color: '#6366f1' },
        aprobado:            { label: 'Aprobado',   color: '#10b981' },
        pendiente_matricula: { label: 'Pend. pago', color: '#f59e0b' },
        pendiente_pago:      { label: 'Pend. pago', color: '#f59e0b' },
    };
    return map[estado] ?? { label: estado, color: '#6b7280' };
};

function getEval(ins, tipo) {
    return ins.evaluaciones?.find(e => e.tipo === tipo) ?? null;
}
function notaColor(nota) {
    if (nota === null || nota === undefined) return '';
    return nota >= 51 ? 'color:#10b981;' : 'color:#ef4444;';
}

// ── Stats ─────────────────────────────────────────────────────────────────────
const activos        = computed(() => props.inscritos.filter(i => i.estado === 'activo').length);
const aprobados      = computed(() => props.inscritos.filter(i => i.aprobado).length);
const pendientes     = computed(() => props.inscritos.filter(i => i.estado?.startsWith('pendiente')).length);
const calificados    = computed(() => props.inscritos.filter(i => i.calificacion_final !== null).length);

// ── Nombre editable de la nota extra ─────────────────────────────────────────
const nombreExtra = ref((() => {
    for (const ins of props.inscritos) {
        const d = getEval(ins, 'otros')?.descripcion;
        if (d) return d;
    }
    return 'Nota Extra';
})());

const editandoNombre = ref(false);
const inputNombreRef = ref(null);
watch(editandoNombre, (val) => {
    if (val) nextTick(() => inputNombreRef.value?.focus());
});
function finalizarNombre() {
    if (!nombreExtra.value.trim()) nombreExtra.value = 'Nota Extra';
    editandoNombre.value = false;
}
function tipoLabel(t) {
    return t.key === 'otros' ? (nombreExtra.value || 'Nota Extra') : t.label;
}

// ── Modal individual ──────────────────────────────────────────────────────────
const modalIns    = ref(null);
const loadingInd  = ref(false);

function abrirModal(ins) {
    modalIns.value = {
        id_inscripcion: ins.id_inscripcion,
        nombre: `${ins.apellido}, ${ins.nombre}`,
        evals: TIPOS.map(t => {
            const ex = getEval(ins, t.key);
            return {
                tipo:         t.key,
                calificacion: ex ? String(ex.calificacion) : '',
                fecha:        ex ? String(ex.fecha).slice(0, 10) : props.hoy,
            };
        }),
    };
}

function submitIndividual() {
    if (loadingInd.value) return;
    loadingInd.value = true;
    router.post(route('admin.evaluaciones.store'), {
        id_inscripcion: modalIns.value.id_inscripcion,
        evaluaciones: modalIns.value.evals
            .filter(e => e.calificacion !== '')
            .map(e => ({
                tipo:         e.tipo,
                calificacion: parseFloat(e.calificacion),
                fecha:        e.fecha || props.hoy,
                ...(e.tipo === 'otros' && { descripcion: nombreExtra.value }),
            })),
    }, {
        preserveScroll: true,
        onSuccess: () => { modalIns.value = null; },
        onFinish:  () => { loadingInd.value = false; },
    });
}

// ── Modal masivo ──────────────────────────────────────────────────────────────
const showMasivo   = ref(false);
const loadingMas   = ref(false);
const notasMasivas = ref({});

function abrirMasivo() {
    const n = {};
    for (const ins of props.inscritos) {
        n[ins.id_inscripcion] = {};
        for (const t of TIPOS) {
            const ex = getEval(ins, t.key);
            n[ins.id_inscripcion][t.key] = ex ? String(ex.calificacion) : '';
        }
    }
    notasMasivas.value = n;
    showMasivo.value = true;
}

function submitMasivo() {
    if (loadingMas.value) return;
    loadingMas.value = true;
    const notas = props.inscritos.map(ins => ({
        id_inscripcion: ins.id_inscripcion,
        evaluaciones: TIPOS
            .filter(t => notasMasivas.value[ins.id_inscripcion][t.key] !== '')
            .map(t => ({
                tipo:         t.key,
                calificacion: parseFloat(notasMasivas.value[ins.id_inscripcion][t.key]),
                fecha:        props.hoy,
            })),
    })).filter(n => n.evaluaciones.length > 0);

    router.post(route('admin.evaluaciones.masivo'), {
        id_oferta:         props.grupo.id_oferta,
        descripcion_extra: nombreExtra.value || 'Nota Extra',
        notas,
    }, {
        preserveScroll: true,
        onSuccess: () => { showMasivo.value = false; },
        onFinish:  () => { loadingMas.value = false; },
    });
}
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
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold truncate" style="color: var(--text-color);">
                        Inscritos — {{ grupo.codigo_grupo ?? grupo.materia_codigo }}
                    </h1>
                    <p class="text-xs" style="color: var(--text-secondary);">CU9 · CU12 — Inscritos y Notas</p>
                </div>
            </div>
        </template>

        <!-- Flash -->
        <div v-if="flash.success"
            class="mb-4 rounded-lg px-4 py-3 text-sm font-medium"
            style="background-color:color-mix(in srgb,#10b981 15%,transparent);color:#10b981;border:1px solid color-mix(in srgb,#10b981 30%,transparent);">
            {{ flash.success }}
        </div>

        <!-- Banner acta -->
        <div v-if="cronograma"
            class="mb-5 rounded-lg px-4 py-3 flex items-center gap-3 text-sm border"
            :style="actaCerrada
                ? 'background-color:color-mix(in srgb,#ef4444 10%,transparent);border-color:color-mix(in srgb,#ef4444 25%,transparent);color:#ef4444;'
                : 'background-color:color-mix(in srgb,#10b981 10%,transparent);border-color:color-mix(in srgb,#10b981 25%,transparent);color:#10b981;'">
            <span class="text-base">{{ actaCerrada ? '🔒' : '📝' }}</span>
            <div class="leading-tight">
                <span class="font-semibold">{{ actaCerrada ? 'Acta cerrada' : 'Acta abierta' }}</span>
                <span class="ml-2 text-xs opacity-75">
                    Período de clases: {{ cronograma.fecha_inicio }} → {{ cronograma.fecha_fin }}
                </span>
            </div>
        </div>

        <!-- Info grupo -->
        <div class="rounded-xl border p-5 mb-5"
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
                    <p class="text-xs font-semibold uppercase tracking-wide mb-0.5" style="color: var(--text-secondary);">Docente · Horario</p>
                    <p class="text-sm font-medium capitalize" style="color: var(--text-color);">{{ grupo.profesor_nombre }}</p>
                    <p class="text-xs capitalize" style="color: var(--text-secondary);">{{ grupo.dia_semana }} {{ fmtHora(grupo.hora_inicio) }}–{{ fmtHora(grupo.hora_fin) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide mb-0.5" style="color: var(--text-secondary);">Vacantes · Aula</p>
                    <p class="text-sm font-bold" style="color: var(--primary-color);">
                        {{ grupo.vacantes_ocupadas ?? 0 }} / {{ grupo.vacantes_max }}
                    </p>
                    <p class="text-xs" style="color: var(--text-secondary);">{{ grupo.aula_nombre }}</p>
                </div>
            </div>
        </div>

        <!-- Stats + Acción masiva -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
            <div class="flex flex-wrap gap-3">
                <div v-for="s in [
                    { label: 'Inscritos',    value: inscritos.length, color: 'var(--primary-color)' },
                    { label: 'Activos',      value: activos,          color: '#6366f1' },
                    { label: 'Calificados',  value: calificados,      color: '#f59e0b' },
                    { label: 'Aprobados',    value: aprobados,        color: '#10b981' },
                    { label: 'Pendientes',   value: pendientes,       color: '#ef4444' },
                ]" :key="s.label"
                    class="rounded-xl px-4 py-3 text-center border"
                    style="background-color:var(--card-bg);border-color:var(--border-color);min-width:76px;">
                    <p class="text-xl font-bold" :style="{ color: s.color }">{{ s.value }}</p>
                    <p class="text-xs mt-0.5" style="color:var(--text-secondary);">{{ s.label }}</p>
                </div>
            </div>

            <button v-if="!actaCerrada && inscritos.length > 0" @click="abrirMasivo"
                class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition"
                style="background-color:var(--primary-color);color:var(--primary-text);">
                ⚡ Carga Masiva de Notas
            </button>
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
                        <tr style="background-color:color-mix(in srgb,var(--text-color) 4%,transparent);border-bottom:1px solid var(--border-color);">
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:var(--text-secondary);">Estudiante</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:var(--text-secondary);">Carrera</th>
                            <th v-for="t in TIPOS" :key="t.key"
                                class="text-center px-3 py-3 text-xs font-semibold uppercase tracking-wide"
                                :style="`color:${t.color};`">
                                <template v-if="t.key === 'otros' && !actaCerrada">
                                    <span v-if="!editandoNombre"
                                        class="group inline-flex items-center gap-1 cursor-pointer select-none"
                                        @click="editandoNombre = true">
                                        <span>{{ tipoLabel(t) }}</span>
                                        <svg class="opacity-0 group-hover:opacity-60 transition-opacity w-3 h-3 shrink-0"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zm-2.207 2.207L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                        </svg>
                                    </span>
                                    <input v-else
                                        ref="inputNombreRef"
                                        v-model="nombreExtra"
                                        type="text" maxlength="60"
                                        placeholder="Nota Extra"
                                        @blur="finalizarNombre"
                                        @keydown.enter="finalizarNombre"
                                        @keydown.escape="editandoNombre = false"
                                        class="bg-transparent text-center outline-none uppercase tracking-wide font-semibold text-xs w-24 border-b"
                                        :style="`color:${t.color};border-color:${t.color}88;`" />
                                </template>
                                <template v-else>{{ tipoLabel(t) }}</template>
                                <br><span class="font-normal opacity-60 normal-case">({{ t.porcentaje }}%)</span>
                            </th>
                            <th class="text-center px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:var(--text-secondary);">Final</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:var(--text-secondary);">Estado</th>
                            <th v-if="!actaCerrada" class="text-right px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:var(--text-secondary);">Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="ins in inscritos" :key="ins.id_inscripcion"
                            style="border-bottom:1px solid var(--border-color);"
                            onmouseover="this.style.backgroundColor='color-mix(in srgb,var(--text-color) 3%,transparent)'"
                            onmouseout="this.style.backgroundColor='transparent'">

                            <!-- Estudiante -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                                        style="background-color:color-mix(in srgb,var(--primary-color) 15%,transparent);color:var(--primary-color);">
                                        {{ ins.nombre?.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-sm" style="color:var(--text-color);">{{ ins.apellido }}, {{ ins.nombre }}</p>
                                        <p class="text-xs" style="color:var(--text-secondary);">{{ ins.legajo }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Carrera -->
                            <td class="px-4 py-3">
                                <p class="text-xs" style="color:var(--text-secondary);">{{ ins.carrera_nombre ?? '—' }}</p>
                            </td>

                            <!-- Nota por tipo -->
                            <td v-for="t in TIPOS" :key="t.key" class="px-3 py-3 text-center">
                                <span v-if="getEval(ins, t.key) !== null"
                                    class="text-sm font-bold"
                                    :style="notaColor(getEval(ins, t.key)?.calificacion)">
                                    {{ getEval(ins, t.key)?.calificacion }}
                                </span>
                                <span v-else class="text-xs" style="color:var(--text-secondary);">—</span>
                            </td>

                            <!-- Final -->
                            <td class="px-4 py-3 text-center">
                                <span v-if="ins.calificacion_final !== null"
                                    class="text-sm font-bold px-2 py-0.5 rounded"
                                    :style="ins.calificacion_final >= 51
                                        ? 'background-color:color-mix(in srgb,#10b981 15%,transparent);color:#10b981;'
                                        : 'background-color:color-mix(in srgb,#ef4444 15%,transparent);color:#ef4444;'">
                                    {{ ins.calificacion_final }}
                                </span>
                                <span v-else class="text-xs" style="color:var(--text-secondary);">—</span>
                            </td>

                            <!-- Estado -->
                            <td class="px-4 py-3 text-center">
                                <span class="text-[11px] font-medium px-2 py-0.5 rounded-full"
                                    :style="`background-color:color-mix(in srgb,${estadoBadge(ins.estado, ins.aprobado).color} 15%,transparent);color:${estadoBadge(ins.estado, ins.aprobado).color};`">
                                    {{ estadoBadge(ins.estado, ins.aprobado).label }}
                                </span>
                            </td>

                            <!-- Acción notas -->
                            <td v-if="!actaCerrada" class="px-4 py-3 text-right">
                                <button @click="abrirModal(ins)"
                                    class="text-xs font-semibold px-3 py-1.5 rounded-md transition"
                                    style="background-color:color-mix(in srgb,var(--primary-color) 12%,transparent);color:var(--primary-color);">
                                    {{ ins.evaluaciones?.length ? 'Editar' : 'Cargar' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-xs mt-3" style="color:var(--text-secondary);">
            Aprobado ≥ 51 · Final = P1×25% + P2×25% + Examen×40% + Extra×10%
        </p>

    </AdminLayout>

    <!-- ── Modal Individual ──────────────────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="modalIns"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background:rgba(0,0,0,0.5);"
            @click.self="modalIns = null">
            <div class="w-full max-w-md rounded-2xl shadow-2xl p-6"
                style="background-color:var(--card-bg);border:1px solid var(--border-color);">

                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-base font-semibold" style="color:var(--text-color);">Cargar Notas</h3>
                        <p class="text-xs mt-0.5" style="color:var(--text-secondary);">{{ modalIns.nombre }}</p>
                    </div>
                    <button @click="modalIns = null" class="text-lg leading-none" style="color:var(--text-secondary);">✕</button>
                </div>

                <div class="space-y-2">
                    <template v-for="(ev, i) in modalIns.evals" :key="ev.tipo">
                        <div class="flex items-center gap-3 rounded-lg px-3 py-2.5"
                            style="background-color:var(--bg-color);border:1px solid var(--border-color);">
                            <span class="text-xs font-semibold w-24 shrink-0" :style="`color:${TIPOS[i].color}`">
                                {{ tipoLabel(TIPOS[i]) }}
                                <span class="font-normal opacity-60 ml-1">{{ TIPOS[i].porcentaje }}%</span>
                            </span>
                            <input v-model="ev.calificacion"
                                type="number" min="0" max="100" step="0.5"
                                placeholder="0–100"
                                class="rounded border px-2 py-1 text-sm outline-none text-center"
                                style="background-color:var(--card-bg);border-color:var(--border-color);color:var(--text-color);width:70px;" />
                            <input v-model="ev.fecha" type="date"
                                class="flex-1 rounded border px-2 py-1 text-xs outline-none"
                                style="background-color:var(--card-bg);border-color:var(--border-color);color:var(--text-color);" />
                        </div>
                    </template>
                </div>

                <div class="flex justify-end gap-3 mt-5">
                    <button @click="modalIns = null"
                        class="px-4 py-2 rounded-lg text-sm font-medium border"
                        style="border-color:var(--border-color);color:var(--text-secondary);">
                        Cancelar
                    </button>
                    <button @click="submitIndividual" :disabled="loadingInd"
                        class="px-5 py-2 rounded-lg text-sm font-semibold transition"
                        :style="loadingInd ? 'opacity:0.6;' : ''"
                        style="background-color:var(--primary-color);color:var(--primary-text);">
                        {{ loadingInd ? 'Guardando…' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- ── Modal Masivo ──────────────────────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="showMasivo"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background:rgba(0,0,0,0.5);"
            @click.self="showMasivo = false">
            <div class="w-full max-w-4xl rounded-2xl shadow-2xl flex flex-col"
                style="background-color:var(--card-bg);border:1px solid var(--border-color);max-height:90vh;">

                <div class="flex items-center justify-between px-6 py-4 border-b shrink-0" style="border-color:var(--border-color);">
                    <div>
                        <h3 class="text-base font-semibold" style="color:var(--text-color);">⚡ Carga Masiva de Notas</h3>
                        <p class="text-xs mt-0.5" style="color:var(--text-secondary);">{{ grupo.materia_nombre }} · {{ inscritos.length }} estudiantes</p>
                    </div>
                    <button @click="showMasivo = false" class="text-lg leading-none" style="color:var(--text-secondary);">✕</button>
                </div>

                <div class="overflow-auto flex-1 px-2 py-2">
                    <table class="w-full text-xs">
                        <thead>
                            <tr style="border-bottom:1px solid var(--border-color);">
                                <th class="text-left px-3 py-2 font-semibold sticky left-0"
                                    style="background-color:var(--card-bg);color:var(--text-secondary);min-width:160px;">
                                    Estudiante
                                </th>
                                <th v-for="t in TIPOS" :key="t.key"
                                    class="text-center px-2 py-2 font-semibold"
                                    :style="`color:${t.color};min-width:90px;`">
                                    {{ tipoLabel(t) }}<br>
                                    <span class="font-normal opacity-60">({{ t.porcentaje }}%)</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="ins in inscritos" :key="ins.id_inscripcion"
                                style="border-bottom:1px solid var(--border-color);">
                                <td class="px-3 py-2 font-medium sticky left-0"
                                    style="background-color:var(--card-bg);color:var(--text-color);">
                                    {{ ins.apellido }}, {{ ins.nombre }}
                                </td>
                                <td v-for="t in TIPOS" :key="t.key" class="px-2 py-2 text-center">
                                    <input
                                        v-model="notasMasivas[ins.id_inscripcion][t.key]"
                                        type="number" min="0" max="100" step="0.5"
                                        placeholder="—"
                                        class="w-16 rounded border px-1 py-1 text-center outline-none text-xs"
                                        style="background-color:var(--bg-color);border-color:var(--border-color);color:var(--text-color);" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between px-6 py-4 border-t shrink-0" style="border-color:var(--border-color);">
                    <p class="text-xs" style="color:var(--text-secondary);">
                        Celdas vacías se omiten · Fecha automática: hoy
                    </p>
                    <div class="flex gap-3">
                        <button @click="showMasivo = false"
                            class="px-4 py-2 rounded-lg text-sm font-medium border"
                            style="border-color:var(--border-color);color:var(--text-secondary);">
                            Cancelar
                        </button>
                        <button @click="submitMasivo" :disabled="loadingMas"
                            class="px-5 py-2 rounded-lg text-sm font-semibold transition"
                            :style="loadingMas ? 'opacity:0.6;' : ''"
                            style="background-color:var(--primary-color);color:var(--primary-text);">
                            {{ loadingMas ? 'Guardando…' : 'Guardar Todo' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
