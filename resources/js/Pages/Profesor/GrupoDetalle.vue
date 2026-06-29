<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import DocenteLayout from '@/Layouts/DocenteLayout.vue';

const props = defineProps({
    grupo:       { type: Object,  required: true },
    estudiantes: { type: Array,   required: true },
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
function getEval(est, tipo) {
    return est.evaluaciones?.find(e => e.tipo === tipo) ?? null;
}

function notaColor(nota) {
    if (nota === null || nota === undefined) return '';
    return nota >= 51 ? 'color:#10b981;' : 'color:#ef4444;';
}

// Nombre editable de la nota extra — se lee de la primera evaluacion existente, se edita a nivel de grupo
const nombreExtra = ref((() => {
    for (const est of props.estudiantes) {
        const d = getEval(est, 'otros')?.descripcion;
        if (d) return d;
    }
    return 'Nota Extra';
})());

function tipoLabel(t) {
    return t.key === 'otros' ? (nombreExtra.value || 'Nota Extra') : t.label;
}

// Edición inline del nombre extra
const editandoNombre = ref(false);
const inputNombreRef = ref(null);

watch(editandoNombre, (val) => {
    if (val) nextTick(() => inputNombreRef.value?.focus());
});

function finalizarNombre() {
    if (!nombreExtra.value.trim()) nombreExtra.value = 'Nota Extra';
    editandoNombre.value = false;
}

// ── Stats ─────────────────────────────────────────────────────────────────────
const totalCalificados = computed(() =>
    props.estudiantes.filter(e => e.calificacion_final !== null).length
);
const totalAprobados = computed(() =>
    props.estudiantes.filter(e => e.calificacion_final !== null && e.calificacion_final >= 51).length
);

// ── Modal individual ──────────────────────────────────────────────────────────
const modalEst   = ref(null);
const loadingInd = ref(false);

function abrirModal(est) {
    modalEst.value = {
        id_inscripcion: est.id_inscripcion,
        nombre: `${est.apellido}, ${est.nombre}`,
        evals: TIPOS.map(t => {
            const ex = getEval(est, t.key);
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

    router.post(route('profesor.evaluaciones.store'), {
        id_inscripcion: modalEst.value.id_inscripcion,
        evaluaciones: modalEst.value.evals
            .filter(e => e.calificacion !== '')
            .map(e => ({
                tipo:         e.tipo,
                calificacion: parseFloat(e.calificacion),
                fecha:        e.fecha || props.hoy,
                ...(e.tipo === 'otros' && { descripcion: nombreExtra.value }),
            })),
    }, {
        preserveScroll: true,
        onSuccess: () => { modalEst.value = null; },
        onFinish:  () => { loadingInd.value = false; },
    });
}

// ── Modal masivo ──────────────────────────────────────────────────────────────
const showMasivo   = ref(false);
const loadingMas   = ref(false);
const notasMasivas = ref({});

function abrirMasivo() {
    const n = {};
    for (const est of props.estudiantes) {
        n[est.id_inscripcion] = {};
        for (const t of TIPOS) {
            const ex = getEval(est, t.key);
            n[est.id_inscripcion][t.key] = ex ? String(ex.calificacion) : '';
        }
    }
    notasMasivas.value = n;
    showMasivo.value = true;
}

function submitMasivo() {
    if (loadingMas.value) return;
    loadingMas.value = true;

    const notas = props.estudiantes.map(est => ({
        id_inscripcion: est.id_inscripcion,
        evaluaciones: TIPOS
            .filter(t => notasMasivas.value[est.id_inscripcion][t.key] !== '')
            .map(t => ({
                tipo:         t.key,
                calificacion: parseFloat(notasMasivas.value[est.id_inscripcion][t.key]),
                fecha:        props.hoy,
            })),
    })).filter(n => n.evaluaciones.length > 0);

    router.post(route('profesor.evaluaciones.masivo'), {
        id_oferta:        props.grupo.id_oferta,
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
    <Head :title="grupo.materia_nombre + ' — Notas'" />

    <DocenteLayout>
        <template #header>
            <div>
                <h2 class="font-semibold text-base leading-tight" style="color: var(--text-color);">
                    {{ grupo.materia_nombre }}
                    <span class="text-sm font-normal ml-1" style="color: var(--text-secondary);">
                        ({{ grupo.codigo_grupo || 'S/N' }})
                    </span>
                </h2>
                <p class="text-xs mt-0.5" style="color: var(--text-secondary);">CU12 — Evaluaciones y Notas</p>
            </div>
        </template>

        <!-- Volver -->
        <div class="mb-4">
            <Link :href="route('dashboard.profesor')"
                class="inline-flex items-center gap-1.5 text-sm font-medium transition-colors"
                style="color: var(--text-secondary);"
                onmouseover="this.style.color='var(--text-color)'"
                onmouseout="this.style.color='var(--text-secondary)'">
                ← Volver a Mis Materias
            </Link>
        </div>

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

        <!-- Stats + Acciones -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
            <div class="flex gap-4">
                <div class="rounded-xl px-4 py-3 text-center border" style="background-color:var(--card-bg);border-color:var(--border-color);min-width:80px;">
                    <p class="text-2xl font-bold" style="color:var(--primary-color);">{{ estudiantes.length }}</p>
                    <p class="text-xs mt-0.5" style="color:var(--text-secondary);">Estudiantes</p>
                </div>
                <div class="rounded-xl px-4 py-3 text-center border" style="background-color:var(--card-bg);border-color:var(--border-color);min-width:80px;">
                    <p class="text-2xl font-bold" style="color:#f59e0b;">{{ totalCalificados }}</p>
                    <p class="text-xs mt-0.5" style="color:var(--text-secondary);">Calificados</p>
                </div>
                <div class="rounded-xl px-4 py-3 text-center border" style="background-color:var(--card-bg);border-color:var(--border-color);min-width:80px;">
                    <p class="text-2xl font-bold" style="color:#10b981;">{{ totalAprobados }}</p>
                    <p class="text-xs mt-0.5" style="color:var(--text-secondary);">Aprobados</p>
                </div>
            </div>

            <button v-if="!actaCerrada" @click="abrirMasivo"
                class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition"
                style="background-color:var(--primary-color);color:var(--primary-text);">
                ⚡ Carga Masiva
            </button>
        </div>

        <!-- Tabla padrón -->
        <div class="rounded-xl overflow-hidden border" style="background-color:var(--card-bg);border-color:var(--border-color);">

            <div v-if="estudiantes.length === 0" class="p-12 text-center">
                <p class="text-3xl mb-2">📋</p>
                <p class="text-sm" style="color:var(--text-secondary);">Sin estudiantes inscritos.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border-color);background-color:color-mix(in srgb,var(--text-color) 3%,transparent);">
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:var(--text-secondary);">Estudiante</th>
                            <th v-for="t in TIPOS" :key="t.key"
                                class="text-center px-3 py-3 text-xs font-semibold uppercase tracking-wide"
                                :style="`color:${t.color};`">
                                <template v-if="t.key === 'otros' && !actaCerrada">
                                    <!-- Modo texto: muestra nombre + lápiz al hover -->
                                    <span v-if="!editandoNombre"
                                        class="group inline-flex items-center gap-1 cursor-pointer select-none"
                                        @click="editandoNombre = true">
                                        <span>{{ tipoLabel(t) }}</span>
                                        <svg class="opacity-0 group-hover:opacity-60 transition-opacity w-3 h-3 shrink-0"
                                            :style="`color:${t.color}`"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zm-2.207 2.207L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                        </svg>
                                    </span>
                                    <!-- Modo edición: input inline -->
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
                                <br>
                                <span class="font-normal opacity-60 normal-case">({{ t.porcentaje }}%)</span>
                            </th>
                            <th class="text-center px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:var(--text-secondary);">Final</th>
                            <th v-if="!actaCerrada" class="text-right px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:var(--text-secondary);">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="est in estudiantes" :key="est.id_inscripcion"
                            style="border-bottom:1px solid var(--border-color);"
                            onmouseover="this.style.backgroundColor='color-mix(in srgb,var(--text-color) 3%,transparent)'"
                            onmouseout="this.style.backgroundColor='transparent'">

                            <!-- Estudiante -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                                        style="background-color:color-mix(in srgb,var(--primary-color) 15%,transparent);color:var(--primary-color);">
                                        {{ est.nombre.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-sm" style="color:var(--text-color);">{{ est.apellido }}, {{ est.nombre }}</p>
                                        <p class="text-xs" style="color:var(--text-secondary);">{{ est.legajo }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Nota por tipo -->
                            <td v-for="t in TIPOS" :key="t.key" class="px-3 py-3 text-center">
                                <span v-if="getEval(est, t.key) !== null"
                                    class="text-sm font-bold"
                                    :style="notaColor(getEval(est, t.key)?.calificacion)">
                                    {{ getEval(est, t.key)?.calificacion }}
                                </span>
                                <span v-else class="text-xs" style="color:var(--text-secondary);">—</span>
                            </td>

                            <!-- Calificación final -->
                            <td class="px-4 py-3 text-center">
                                <span v-if="est.calificacion_final !== null"
                                    class="text-sm font-bold px-2 py-0.5 rounded"
                                    :style="est.calificacion_final >= 51
                                        ? 'background-color:color-mix(in srgb,#10b981 15%,transparent);color:#10b981;'
                                        : 'background-color:color-mix(in srgb,#ef4444 15%,transparent);color:#ef4444;'">
                                    {{ est.calificacion_final }}
                                </span>
                                <span v-else class="text-xs" style="color:var(--text-secondary);">—</span>
                            </td>

                            <!-- Acción -->
                            <td v-if="!actaCerrada" class="px-4 py-3 text-right">
                                <button @click="abrirModal(est)"
                                    class="text-xs font-semibold px-3 py-1.5 rounded-md transition"
                                    style="background-color:color-mix(in srgb,var(--primary-color) 12%,transparent);color:var(--primary-color);">
                                    {{ est.evaluaciones?.length ? 'Editar' : 'Cargar' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Leyenda -->
        <p class="text-xs mt-3" style="color:var(--text-secondary);">
            Aprobado ≥ 51 puntos · Final = P1×25% + P2×25% + Examen×40% + Extra×10%
        </p>

    </DocenteLayout>

    <!-- ── Modal Individual ──────────────────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="modalEst"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background:rgba(0,0,0,0.5);"
            @click.self="modalEst = null">
            <div class="w-full max-w-md rounded-2xl shadow-2xl p-6"
                style="background-color:var(--card-bg);border:1px solid var(--border-color);">

                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-base font-semibold" style="color:var(--text-color);">Cargar Notas</h3>
                        <p class="text-xs mt-0.5" style="color:var(--text-secondary);">{{ modalEst.nombre }}</p>
                    </div>
                    <button @click="modalEst = null" class="text-lg leading-none" style="color:var(--text-secondary);">✕</button>
                </div>

                <div class="space-y-2">
                    <template v-for="(ev, i) in modalEst.evals" :key="ev.tipo">
                        <!-- Fila nota -->
                        <div class="flex items-center gap-3 rounded-lg px-3 py-2.5"
                            style="background-color:var(--bg-color);border:1px solid var(--border-color);">
                            <span class="text-xs font-semibold w-24 shrink-0"
                                :style="`color:${TIPOS[i].color}`">
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
                    <button @click="modalEst = null"
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

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b shrink-0" style="border-color:var(--border-color);">
                    <div>
                        <h3 class="text-base font-semibold" style="color:var(--text-color);">⚡ Carga Masiva de Notas</h3>
                        <p class="text-xs mt-0.5" style="color:var(--text-secondary);">{{ grupo.materia_nombre }} · {{ estudiantes.length }} estudiantes</p>
                    </div>
                    <button @click="showMasivo = false" class="text-lg leading-none" style="color:var(--text-secondary);">✕</button>
                </div>

                <!-- Tabla masiva -->
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
                            <tr v-for="est in estudiantes" :key="est.id_inscripcion"
                                style="border-bottom:1px solid var(--border-color);">
                                <td class="px-3 py-2 font-medium sticky left-0"
                                    style="background-color:var(--card-bg);color:var(--text-color);">
                                    {{ est.apellido }}, {{ est.nombre }}
                                </td>
                                <td v-for="t in TIPOS" :key="t.key" class="px-2 py-2 text-center">
                                    <input
                                        v-model="notasMasivas[est.id_inscripcion][t.key]"
                                        type="number" min="0" max="100" step="0.5"
                                        placeholder="—"
                                        class="w-16 rounded border px-1 py-1 text-center outline-none text-xs"
                                        style="background-color:var(--bg-color);border-color:var(--border-color);color:var(--text-color);" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between px-6 py-4 border-t shrink-0" style="border-color:var(--border-color);">
                    <p class="text-xs" style="color:var(--text-secondary);">
                        Las celdas vacías se omiten · Fecha automática: hoy
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
