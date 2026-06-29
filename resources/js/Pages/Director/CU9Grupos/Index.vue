<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ComboSelect from '@/Components/ComboSelect.vue';

const canEdit = computed(() => ['propietario', 'director'].includes(usePage().props.auth?.user?.role));

const props = defineProps({
    periodos:        { type: Array,  default: () => [] },
    materias:        { type: Array,  default: () => [] },
    aulas:           { type: Array,  default: () => [] },
    profesores:      { type: Array,  default: () => [] },
    horarios:        { type: Array,  default: () => [] },
    mallaPorCarrera: { type: Object, default: () => ({}) },
});

// ── Stats ─────────────────────────────────────────────────────────────────────
const todosGrupos   = computed(() => props.periodos.flatMap(p => p.grupos));
const totalGrupos   = computed(() => todosGrupos.value.length);
const gruposActivos = computed(() => todosGrupos.value.filter(g => g.activo).length);
const conVacantes   = computed(() => todosGrupos.value.filter(g => g.activo && (g.vacantes_max - g.vacantes_ocupadas) > 0).length);

// ── Períodos abiertos/cerrados ─────────────────────────────────────────────────
const periodosAbiertos = ref({});
props.periodos.forEach(p => { periodosAbiertos.value[p.id_periodo] = p.activo; });
const togglePeriodo = (id) => { periodosAbiertos.value[id] = !periodosAbiertos.value[id]; };

// ── Filtros ───────────────────────────────────────────────────────────────────
const filtroCarrera = ref('');
const soloActivos   = ref(true);
const hoy      = new Date().toISOString().slice(0, 10);
const anoActual = new Date().getFullYear();

const optsCarrerasFiltro = computed(() => {
    const seen = new Set();
    const opts = [];
    for (const p of props.periodos) {
        if (p.carrera_nombre && p.id_carrera && !seen.has(p.id_carrera)) {
            seen.add(p.id_carrera);
            opts.push({ value: p.id_carrera, label: p.carrera_nombre });
        }
    }
    return opts.sort((a, b) => a.label.localeCompare(b.label));
});

// Lista filtrada plana (usada por modales de clonar, etc.)
const periodosFiltrados = computed(() => {
    let lista = props.periodos;
    if (soloActivos.value) {
        lista = lista.filter(p =>
            p.activo && (
                new Date(p.fecha_inicio).getFullYear() <= anoActual || // año actual o anterior
                p.grupos.length > 0                                     // o ya tiene grupos
            )
        );
    }
    if (filtroCarrera.value) lista = lista.filter(p => p.id_carrera === filtroCarrera.value);
    return lista;
});

// Agrupado por carrera para el accordion principal
const periodosPorCarrera = computed(() => {
    const map = {};
    for (const p of periodosFiltrados.value) {
        const key = p.id_carrera ?? '__libre__';
        if (!map[key]) {
            map[key] = {
                id_carrera: p.id_carrera ?? key,
                nombre: p.carrera_nombre ?? 'Sin carrera',
                periodos: [],
                totalGrupos: 0,
            };
        }
        map[key].periodos.push(p);
        map[key].totalGrupos += p.grupos.length;
    }
    return Object.values(map).sort((a, b) => a.nombre.localeCompare(b.nombre));
});

// Accordion externo: qué carrera está abierta
const carrerasAbiertas = ref({});
const toggleCarreraGrupos = (id) => {
    carrerasAbiertas.value[id] = !carrerasAbiertas.value[id];
};

// ── Options para ComboSelect ──────────────────────────────────────────────────
const optsPeriodos = computed(() =>
    props.periodos
        .filter(p => p.activo)
        .sort((a, b) => (b.fecha_inicio ?? '').localeCompare(a.fecha_inicio ?? ''))
        .map(p => ({
            value: p.id_periodo,
            label: (p.carrera_nombre ? p.carrera_nombre + ' · ' : '')
                 + (p.nivel_nombre ? p.nivel_nombre + ' — ' : '')
                 + p.nombre,
        }))
);

// ── Filtro de carrera para modal Nuevo Grupo ──────────────────────────────────
const filtroCarreraModal  = ref('');
const soloVigentesModal   = ref(true);
const optsPeriodosModalFiltrados = computed(() => {
    let lista = props.periodos.filter(p => p.activo);
    if (soloVigentesModal.value) lista = lista.filter(p => new Date(p.fecha_inicio).getFullYear() === anoActual);
    if (filtroCarreraModal.value) lista = lista.filter(p => p.id_carrera === filtroCarreraModal.value);
    return lista
        .sort((a, b) => (b.fecha_inicio ?? '').localeCompare(a.fecha_inicio ?? ''))
        .map(p => ({
            value: p.id_periodo,
            label: (filtroCarreraModal.value ? '' : (p.carrera_nombre ? p.carrera_nombre + ' · ' : ''))
                 + (p.nivel_nombre ? p.nivel_nombre + ' — ' : '')
                 + p.nombre,
        }));
});

// ── Filtro de carrera para modal Clonar Oferta ────────────────────────────────
const filtroCarreraClonar  = ref('');
const soloVigentesClonar   = ref(true);
const optsPeriodosOrigenFiltrados = computed(() => {
    let lista = props.periodos;
    if (soloVigentesClonar.value) lista = lista.filter(p => new Date(p.fecha_inicio).getFullYear() === anoActual);
    if (filtroCarreraClonar.value) lista = lista.filter(p => p.id_carrera === filtroCarreraClonar.value);
    return lista
        .sort((a, b) => (b.fecha_inicio ?? '').localeCompare(a.fecha_inicio ?? ''))
        .map(p => ({
            value: p.id_periodo,
            label: (filtroCarreraClonar.value ? '' : (p.carrera_nombre ? p.carrera_nombre + ' · ' : ''))
                 + (p.nivel_nombre ? p.nivel_nombre + ' — ' : '')
                 + p.nombre,
        }));
});
const optsPeriodosDestinoClonarFiltrados = computed(() =>
    optsPeriodosOrigenFiltrados.value.filter(p => p.value !== formClonar.id_periodo_origen)
);

const optsAulas = computed(() => props.aulas.map(a => ({
    value: a.id_aula,
    label: a.nombre + ' (cap. ' + a.capacidad + ')',
})));

const optsProfesores = computed(() => props.profesores.map(p => ({
    value: p.id_profesor,
    label: p.nombre + (p.especialidad ? ' · ' + p.especialidad : ''),
})));

const optsHorarios = computed(() => props.horarios.map(h => ({
    value: h.id_horario,
    label: h.label,
})));

// ── Modal Crear ───────────────────────────────────────────────────────────────
const showModal = ref(false);
const formNuevo = useForm({
    id_periodo:   '',
    id_materia:   '',
    id_aula:      '',
    id_profesor:  '',
    id_horario:   '',
    vacantes_max: 30,
    codigo_grupo: '',
});

// Período seleccionado en el modal → para filtrar materias por malla
const periodoDelModal = computed(() =>
    props.periodos.find(p => p.id_periodo === formNuevo.id_periodo)
);

// Materias filtradas por malla de la carrera del período seleccionado
const optsMateriasFiltradas = computed(() => {
    const p = periodoDelModal.value;
    if (!p?.id_carrera) {
        return props.materias.map(m => ({ value: m.id_materia, label: m.codigo + ' · ' + m.nombre }));
    }
    const malla = props.mallaPorCarrera[p.id_carrera] ?? [];
    if (malla.length === 0) {
        return props.materias.map(m => ({ value: m.id_materia, label: m.codigo + ' · ' + m.nombre }));
    }
    return malla.map(m => ({
        value: m.id_materia,
        label: 'Año ' + m.numero_nivel + ' · ' + m.codigo + ' · ' + m.nombre,
    }));
});

// Al cambiar período, resetear materia (porque cambia el listado)
watch(() => formNuevo.id_periodo, () => { formNuevo.id_materia = ''; });
// Al cambiar carrera en el modal, resetear período y materia
watch(filtroCarreraModal, () => { formNuevo.id_periodo = ''; formNuevo.id_materia = ''; });

// ── Aula seleccionada → auto-fill vacantes y código ───────────────────────────
const aulaSeleccionada = computed(() =>
    props.aulas.find(a => a.id_aula === formNuevo.id_aula)
);

// Capacidad del aula seleccionada (límite máximo de vacantes)
const aulaCapacidad = computed(() => aulaSeleccionada.value?.capacidad ?? 200);

// Genera código automático: "ADM101-A06" desde sigla de materia + identificador de aula
function generarCodigoAuto() {
    const materia = (() => {
        const cid = periodoDelModal.value?.id_carrera;
        if (cid) {
            const enMalla = (props.mallaPorCarrera[cid] ?? []).find(m => m.id_materia === formNuevo.id_materia);
            if (enMalla) return enMalla;
        }
        return props.materias.find(m => m.id_materia === formNuevo.id_materia);
    })();
    const aula = aulaSeleccionada.value;
    if (!materia || !aula) return;

    // "ADM-101" → "ADM101"  |  "Aula A-06" → "A06"
    const matCode  = (materia.codigo ?? '').replace(/[-\s]/g, '');
    const aulaPart = aula.nombre.replace(/^aula\s+/i, '').replace(/[-\s]/g, '');
    formNuevo.codigo_grupo = matCode + '-' + aulaPart;
}

watch(() => formNuevo.id_aula, (newId) => {
    const aula = props.aulas.find(a => a.id_aula === newId);
    if (aula) {
        formNuevo.vacantes_max = aula.capacidad;
    }
    generarCodigoAuto();
});

watch(() => formNuevo.id_materia, () => {
    generarCodigoAuto();
});

const abrirModal = (id_periodo = '') => {
    formNuevo.reset();
    formNuevo.id_periodo  = id_periodo;
    formNuevo.vacantes_max = 30;
    if (id_periodo) {
        const p = props.periodos.find(x => x.id_periodo === id_periodo);
        filtroCarreraModal.value = p?.id_carrera ?? '';
    } else {
        filtroCarreraModal.value = '';
    }
    showModal.value = true;
};

const guardarNuevo = () => {
    formNuevo.post(route('director.grupos.store'), {
        onSuccess: () => { showModal.value = false; formNuevo.reset(); },
    });
};

// ── Modal Editar ──────────────────────────────────────────────────────────────
const showEdit  = ref(false);
const grupoEdit = ref(null);
const formEdit  = useForm({
    vacantes_max: 30,
    codigo_grupo: '',
    id_aula:      '',
    id_profesor:  '',
    id_horario:   '',
});

const aulaEditSeleccionada = computed(() =>
    props.aulas.find(a => a.id_aula === formEdit.id_aula)
);
const aulaEditCapacidad = computed(() => aulaEditSeleccionada.value?.capacidad ?? 200);

function generarCodigoEdit() {
    const matCode = (grupoEdit.value?.materia_codigo ?? '').replace(/[-\s]/g, '');
    const aula = aulaEditSeleccionada.value;
    if (!matCode || !aula) return;
    const aulaPart = aula.nombre.replace(/^aula\s+/i, '').replace(/[-\s]/g, '');
    formEdit.codigo_grupo = matCode + '-' + aulaPart;
}

watch(() => formEdit.id_aula, (newId) => {
    const aula = props.aulas.find(a => a.id_aula === newId);
    if (aula) formEdit.vacantes_max = aula.capacidad;
    generarCodigoEdit();
});

const abrirEditar = (grupo) => {
    grupoEdit.value       = grupo;
    formEdit.vacantes_max = grupo.vacantes_max;
    formEdit.codigo_grupo = grupo.codigo_grupo ?? '';
    formEdit.id_aula      = grupo.id_aula;
    formEdit.id_profesor  = grupo.id_profesor;
    formEdit.id_horario   = grupo.id_horario;
    showEdit.value = true;
};

const guardarEdit = () => {
    formEdit.put(route('director.grupos.update', grupoEdit.value.id_oferta), {
        onSuccess: () => { showEdit.value = false; },
    });
};

// ── Modal Clonar Oferta ───────────────────────────────────────────────────────
const showClonar  = ref(false);
const formClonar  = useForm({ id_periodo_origen: '', id_periodo_destino: '' });

const optsPeriodosDestino = computed(() =>
    props.periodos
        .filter(p => p.id_periodo !== formClonar.id_periodo_origen)
        .map(p => ({
            value: p.id_periodo,
            label: p.nombre + (p.carrera_nombre ? ' · ' + p.carrera_nombre : ''),
        }))
);

watch(() => formClonar.id_periodo_origen, () => { formClonar.id_periodo_destino = ''; });
// Al cambiar carrera en modal clonar, resetear ambos períodos
watch(filtroCarreraClonar, () => { formClonar.id_periodo_origen = ''; formClonar.id_periodo_destino = ''; });

const gruposOrigen = computed(() => {
    if (!formClonar.id_periodo_origen) return 0;
    return props.periodos.find(p => p.id_periodo === formClonar.id_periodo_origen)?.grupos?.length ?? 0;
});

const abrirClonar = () => {
    filtroCarreraClonar.value  = '';
    soloVigentesClonar.value   = true;
    formClonar.reset();
    showClonar.value = true;
};

const guardarClonar = () => {
    formClonar.post(route('director.grupos.clonar'), {
        onSuccess: () => {
            showClonar.value          = false;
            formClonar.reset();
            filtroCarreraClonar.value = '';
            soloVigentesClonar.value  = true;
        },
    });
};

// ── Toggle / Eliminar ─────────────────────────────────────────────────────────
const toggleGrupo = (grupo) => {
    router.patch(route('director.grupos.toggle', grupo.id_oferta), {}, { preserveScroll: true });
};

const confirmarEliminar = ref(null);
const eliminarGrupo = () => {
    if (!confirmarEliminar.value) return;
    router.delete(route('director.grupos.destroy', confirmarEliminar.value.id_oferta), {
        onSuccess: () => { confirmarEliminar.value = null; },
    });
};

// ── Helpers ───────────────────────────────────────────────────────────────────
const vacantesLibres = (g) => Math.max(0, g.vacantes_max - g.vacantes_ocupadas);

const tipoBadge = (tipo) => {
    const map = { semestral: '#6366f1', mensual: '#f59e0b', anual: '#10b981', intensivo: '#ef4444' };
    return map[tipo] ?? '#6b7280';
};

const fmtFecha = (f) => {
    if (!f) return '';
    return new Date(f + 'T12:00:00').toLocaleDateString('es-BO', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <AdminLayout>
        <template #header>
            <h1 class="text-lg font-semibold truncate" style="color: var(--text-color);">
                Grupos / Oferta Académica
            </h1>
        </template>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div v-for="stat in [
                { label: 'Total grupos', value: totalGrupos,   color: '#6366f1' },
                { label: 'Activos',      value: gruposActivos, color: '#10b981' },
                { label: 'Con vacantes', value: conVacantes,   color: '#f59e0b' },
            ]" :key="stat.label"
                class="rounded-xl p-4 border"
                style="background-color: var(--card-bg); border-color: var(--border-color);">
                <p class="text-2xl font-bold" :style="{ color: stat.color }">{{ stat.value }}</p>
                <p class="text-xs mt-0.5" style="color: var(--text-secondary);">{{ stat.label }}</p>
            </div>
        </div>

        <!-- Filtros + acciones -->
        <div class="flex items-center gap-3 mb-4 flex-wrap">
            <div class="flex-1 min-w-44 max-w-xs">
                <ComboSelect
                    v-model="filtroCarrera"
                    :options="optsCarrerasFiltro"
                    placeholder="Todas las carreras"
                    emptyLabel="Todas las carreras" />
            </div>
            <!-- Toggle solo activos -->
            <label class="flex items-center gap-2 cursor-pointer select-none text-sm shrink-0"
                   style="color: var(--text-secondary);">
                <span class="relative inline-block w-9 h-5">
                    <input type="checkbox" v-model="soloActivos" class="sr-only peer" />
                    <span class="block w-full h-full rounded-full transition peer-checked:opacity-100"
                          :style="soloActivos ? 'background-color: var(--primary-color);' : 'background-color: var(--border-color);'"></span>
                    <span class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform"
                          :style="soloActivos ? 'transform:translateX(16px)' : ''"></span>
                </span>
                Solo vigentes
            </label>
            <p v-if="filtroCarrera" class="text-xs shrink-0" style="color: var(--text-secondary);">
                <button @click="filtroCarrera = ''" class="underline" style="color: var(--primary-color);">Limpiar filtro</button>
            </p>
            <div v-if="canEdit" class="ml-auto flex items-center gap-2 shrink-0">
                <button @click="abrirClonar()"
                    class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold border transition"
                    style="border-color: var(--primary-color); color: var(--primary-color); background: transparent;">
                    📋 Clonar Oferta
                </button>
                <button @click="abrirModal()"
                    class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-opacity hover:opacity-80"
                    style="background-color: var(--primary-color); color: var(--primary-text);">
                    + Nuevo Grupo
                </button>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success"
            class="mb-4 rounded-lg px-4 py-3 text-sm font-medium"
            style="background-color: color-mix(in srgb,#10b981 15%,transparent); color:#10b981; border:1px solid color-mix(in srgb,#10b981 30%,transparent);">
            {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.errors?.grupo"
            class="mb-4 rounded-lg px-4 py-3 text-sm font-medium"
            style="background-color: color-mix(in srgb,#ef4444 12%,transparent); color:#ef4444; border:1px solid color-mix(in srgb,#ef4444 30%,transparent);">
            {{ $page.props.errors.grupo }}
        </div>

        <!-- Sin períodos -->
        <div v-if="periodosPorCarrera.length === 0"
            class="rounded-xl border p-10 text-center"
            style="background-color: var(--card-bg); border-color: var(--border-color);">
            <p class="text-4xl mb-3">📅</p>
            <template v-if="props.periodos.length === 0">
                <p class="font-medium mb-1" style="color: var(--text-color);">Sin períodos configurados</p>
                <p class="text-sm" style="color: var(--text-secondary);">
                    Primero crea períodos académicos en <strong>Períodos Académicos</strong>.
                </p>
            </template>
            <template v-else>
                <p class="font-medium mb-1" style="color: var(--text-color);">Sin períodos vigentes</p>
                <p class="text-sm mb-3" style="color: var(--text-secondary);">
                    No hay períodos activos en la fecha actual ni con grupos creados.
                </p>
                <button @click="soloActivos = false"
                    class="text-sm font-medium underline"
                    style="color: var(--primary-color);">
                    Mostrar todos los períodos
                </button>
            </template>
        </div>

        <!-- Lista agrupada por carrera -->
        <div v-else class="space-y-3">
            <div v-for="carrera in periodosPorCarrera" :key="carrera.id_carrera"
                 class="rounded-xl border overflow-hidden"
                 style="background-color: var(--card-bg); border-color: var(--border-color);">

                <!-- ── Cabecera carrera (accordion externo) ── -->
                <div class="flex items-center justify-between px-5 py-3 cursor-pointer select-none transition-colors"
                     style="background-color: var(--bg-color);"
                     :style="carrerasAbiertas[carrera.id_carrera] ? 'border-bottom:1px solid var(--border-color);' : ''"
                     @click="toggleCarreraGrupos(carrera.id_carrera)">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-xs shrink-0 transition-transform duration-200"
                              :style="carrerasAbiertas[carrera.id_carrera] ? 'transform:rotate(90deg);' : ''"
                              style="color: var(--text-secondary);">▶</span>
                        <span class="font-semibold text-sm truncate" style="color: var(--text-color);">
                            {{ carrera.nombre }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <span class="text-xs" style="color: var(--text-secondary);">
                            {{ carrera.periodos.length }} período(s)
                        </span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                              :style="carrera.totalGrupos > 0
                                ? 'background-color:color-mix(in srgb,var(--primary-color) 12%,transparent);color:var(--primary-color);'
                                : 'background-color:color-mix(in srgb,#6b7280 12%,transparent);color:#6b7280;'">
                            {{ carrera.totalGrupos }} grupo{{ carrera.totalGrupos !== 1 ? 's' : '' }}
                        </span>
                    </div>
                </div>

                <!-- ── Períodos de esta carrera ── -->
                <div v-show="carrerasAbiertas[carrera.id_carrera]" class="space-y-2 p-3">
                    <div v-for="periodo in carrera.periodos" :key="periodo.id_periodo"
                        class="rounded-lg border overflow-hidden"
                        style="border-color: var(--border-color); background-color: var(--card-bg);">

                <!-- Header período -->
                <button @click="togglePeriodo(periodo.id_periodo)"
                    class="w-full flex items-center justify-between px-5 py-4 text-left transition-colors"
                    :style="periodosAbiertos[periodo.id_periodo]
                        ? 'background-color: color-mix(in srgb, var(--primary-color) 8%, transparent);'
                        : ''">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <span class="shrink-0 text-[10px] font-bold uppercase px-2 py-0.5 rounded"
                            :style="{ backgroundColor: tipoBadge(periodo.tipo_periodo) + '22', color: tipoBadge(periodo.tipo_periodo) }">
                            {{ periodo.tipo_periodo }}
                        </span>
                        <div class="min-w-0">
                            <p class="font-semibold text-sm truncate" style="color: var(--text-color);">
                                {{ periodo.nombre }}
                            </p>
                            <p class="text-xs truncate" style="color: var(--text-secondary);">
                                📚 {{ fmtFecha(periodo.fecha_inicio) }} — {{ fmtFecha(periodo.fecha_fin) }}
                                <span v-if="periodo.carrera_nombre"> · {{ periodo.carrera_nombre }}</span>
                                <span v-if="periodo.nivel_nombre"> · {{ periodo.nivel_nombre }}</span>
                            </p>
                            <p class="text-xs truncate"
                               :style="periodo.fecha_inicio_inscripcion
                                   ? 'color: #8b5cf6;'
                                   : 'color: var(--text-secondary); opacity: 0.55;'">
                                <template v-if="periodo.fecha_inicio_inscripcion">
                                    📝 Inscripciones: {{ fmtFecha(periodo.fecha_inicio_inscripcion) }} → {{ fmtFecha(periodo.fecha_fin_inscripcion) }}
                                </template>
                                <template v-else>
                                    📝 Sin ventana de inscripciones definida
                                </template>
                            </p>
                        </div>
                        <span v-if="!periodo.activo"
                            class="shrink-0 text-[10px] font-medium px-2 py-0.5 rounded"
                            style="background-color: color-mix(in srgb,#6b7280 15%,transparent); color:#6b7280;">
                            Inactivo
                        </span>
                    </div>
                    <div class="flex items-center gap-3 shrink-0 ml-3">
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full"
                            style="background-color: color-mix(in srgb,var(--primary-color) 12%,transparent); color: var(--primary-color);">
                            {{ periodo.grupos.length }} grupo{{ periodo.grupos.length !== 1 ? 's' : '' }}
                        </span>
                        <button v-if="canEdit" @click.stop="abrirModal(periodo.id_periodo)"
                            class="text-xs px-2.5 py-1 rounded-md font-medium transition-opacity hover:opacity-80"
                            style="background-color: var(--primary-color); color: var(--primary-text);">
                            + Grupo
                        </button>
                        <span class="text-xs opacity-40 transition-transform duration-200"
                            :style="periodosAbiertos[periodo.id_periodo] ? 'transform:rotate(180deg)' : ''">▾</span>
                    </div>
                </button>

                <!-- Tabla de grupos -->
                <div v-show="periodosAbiertos[periodo.id_periodo]">
                    <div v-if="periodo.grupos.length === 0"
                        class="px-6 py-6 text-center border-t"
                        style="border-color: var(--border-color);">
                        <p class="text-sm" style="color: var(--text-secondary);">Sin grupos en este período.</p>
                    </div>

                    <div v-else class="overflow-x-auto border-t" style="border-color: var(--border-color);">
                        <table class="w-full text-sm">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-color); background-color: color-mix(in srgb, var(--text-color) 3%, transparent);">
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Código</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Materia</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Aula</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Profesor</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Horario</th>
                                    <th class="text-center px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Vacantes</th>
                                    <th class="text-center px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Estado</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="grupo in periodo.grupos" :key="grupo.id_oferta"
                                    class="transition-colors"
                                    style="border-bottom: 1px solid var(--border-color);"
                                    onmouseover="this.style.backgroundColor='color-mix(in srgb,var(--text-color) 3%,transparent)'"
                                    onmouseout="this.style.backgroundColor='transparent'">

                                    <td class="px-4 py-3">
                                        <code class="text-xs font-bold px-1.5 py-0.5 rounded"
                                            style="background-color: color-mix(in srgb,var(--primary-color) 12%,transparent); color: var(--primary-color);">
                                            {{ grupo.codigo_grupo }}
                                        </code>
                                    </td>

                                    <td class="px-4 py-3">
                                        <p class="font-medium text-xs" style="color: var(--text-color);">{{ grupo.materia_nombre }}</p>
                                        <p class="text-[11px]" style="color: var(--text-secondary);">{{ grupo.materia_codigo }}</p>
                                    </td>

                                    <td class="px-4 py-3">
                                        <p class="text-xs" style="color: var(--text-color);">{{ grupo.aula_nombre }}</p>
                                        <p class="text-[11px]" style="color: var(--text-secondary);">Cap. {{ grupo.aula_capacidad }}</p>
                                    </td>

                                    <td class="px-4 py-3">
                                        <p class="text-xs" style="color: var(--text-color);">{{ grupo.profesor_nombre }}</p>
                                    </td>

                                    <td class="px-4 py-3">
                                        <p class="text-xs font-medium capitalize" style="color: var(--text-color);">{{ grupo.dia_semana }}</p>
                                        <p class="text-[11px]" style="color: var(--text-secondary);">{{ grupo.hora_inicio }}–{{ grupo.hora_fin }}</p>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs font-bold"
                                            :style="{ color: vacantesLibres(grupo) === 0 ? '#ef4444' : vacantesLibres(grupo) <= 5 ? '#f59e0b' : '#10b981' }">
                                            {{ vacantesLibres(grupo) }}
                                        </span>
                                        <span class="text-[11px]" style="color: var(--text-secondary);">/{{ grupo.vacantes_max }}</span>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full"
                                            :style="grupo.activo
                                                ? 'background-color: color-mix(in srgb,#10b981 15%,transparent); color:#10b981;'
                                                : 'background-color: color-mix(in srgb,#6b7280 15%,transparent); color:#6b7280;'">
                                            {{ grupo.activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <a :href="route('director.grupos.inscritos', grupo.id_oferta)"
                                               class="p-1.5 rounded text-xs" title="Ver inscritos"
                                               style="color: var(--text-secondary);"
                                               onmouseover="this.style.color='#10b981'"
                                               onmouseout="this.style.color='var(--text-secondary)'">👥</a>
                                            <a v-if="usePage().props.auth?.user?.role === 'director'"
                                               :href="route('director.grupos.notas', grupo.id_oferta)"
                                               class="p-1.5 rounded text-xs" title="Administrar notas"
                                               style="color: var(--text-secondary);"
                                               onmouseover="this.style.color='#6366f1'"
                                               onmouseout="this.style.color='var(--text-secondary)'">📊</a>
                                            <template v-if="canEdit">
                                                <button @click="abrirEditar(grupo)" class="p-1.5 rounded text-xs" title="Editar"
                                                    style="color: var(--text-secondary);"
                                                    onmouseover="this.style.color='var(--primary-color)'"
                                                    onmouseout="this.style.color='var(--text-secondary)'">✏️</button>
                                                <button @click="toggleGrupo(grupo)" class="p-1.5 rounded text-xs"
                                                    :title="grupo.activo ? 'Desactivar' : 'Activar'"
                                                    style="color: var(--text-secondary);"
                                                    onmouseover="this.style.color='#f59e0b'"
                                                    onmouseout="this.style.color='var(--text-secondary)'">
                                                    {{ grupo.activo ? '🔒' : '🔓' }}
                                                </button>
                                                <button @click="confirmarEliminar = grupo" class="p-1.5 rounded text-xs" title="Eliminar"
                                                    style="color: var(--text-secondary);"
                                                    onmouseover="this.style.color='#ef4444'"
                                                    onmouseout="this.style.color='var(--text-secondary)'">🗑️</button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </div>
                    </div> <!-- fin período -->
                </div> <!-- fin lista períodos carrera -->
            </div> <!-- fin carrera -->
        </div>

        <!-- ══ MODAL: Nuevo Grupo ══════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background-color: rgba(0,0,0,0.5);"
                @click.self="showModal = false">
                <div class="w-full max-w-lg rounded-2xl border shadow-xl overflow-y-auto max-h-[90vh]"
                    style="background-color: var(--card-bg); border-color: var(--border-color);">
                    <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color: var(--border-color);">
                        <h2 class="font-bold text-base" style="color: var(--text-color);">Nuevo Grupo</h2>
                        <button @click="showModal = false" class="text-lg leading-none" style="color: var(--text-secondary);">✕</button>
                    </div>

                    <div class="px-6 py-5 space-y-4">
                        <div v-if="formNuevo.errors.grupo"
                            class="rounded-lg px-3 py-2 text-xs font-medium"
                            style="background-color: color-mix(in srgb,#ef4444 12%,transparent); color:#ef4444; border:1px solid color-mix(in srgb,#ef4444 30%,transparent);">
                            {{ formNuevo.errors.grupo }}
                        </div>

                        <!-- Carrera + toggle vigentes (pre-filtro) -->
                        <div class="flex gap-3 items-end">
                            <div class="flex-1">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Carrera</label>
                                <select v-model="filtroCarreraModal"
                                    class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                    style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);">
                                    <option value="">— Todas las carreras —</option>
                                    <option v-for="c in optsCarrerasFiltro" :key="c.value" :value="c.value">{{ c.label }}</option>
                                </select>
                            </div>
                            <label class="flex items-center gap-1.5 cursor-pointer select-none pb-2 shrink-0">
                                <span class="relative inline-block w-8 h-4">
                                    <input type="checkbox" v-model="soloVigentesModal" class="sr-only peer" />
                                    <span class="block w-full h-full rounded-full transition"
                                          :style="soloVigentesModal ? 'background-color:var(--primary-color)' : 'background-color:var(--border-color)'"></span>
                                    <span class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full shadow transition-transform"
                                          :style="soloVigentesModal ? 'transform:translateX(16px)' : ''"></span>
                                </span>
                                <span class="text-xs" style="color: var(--text-secondary);">{{ anoActual }}</span>
                            </label>
                        </div>

                        <!-- Período -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Período *</label>
                            <ComboSelect v-model="formNuevo.id_periodo" :options="optsPeriodosModalFiltrados"
                                placeholder="— Seleccionar período —" emptyLabel="" />
                            <p v-if="formNuevo.errors.id_periodo" class="text-xs mt-1" style="color:#ef4444;">{{ formNuevo.errors.id_periodo }}</p>
                        </div>

                        <!-- Materia (filtrada por malla de la carrera del período) -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                Materia *
                                <span v-if="periodoDelModal?.id_carrera && mallaPorCarrera[periodoDelModal.id_carrera]?.length"
                                    class="font-normal opacity-60 ml-1">
                                    — {{ mallaPorCarrera[periodoDelModal.id_carrera].length }} en malla de {{ periodoDelModal.carrera_nombre }}
                                </span>
                            </label>
                            <ComboSelect v-model="formNuevo.id_materia" :options="optsMateriasFiltradas"
                                placeholder="— Seleccionar materia —" emptyLabel="" />
                            <p v-if="formNuevo.errors.id_materia" class="text-xs mt-1" style="color:#ef4444;">{{ formNuevo.errors.id_materia }}</p>
                        </div>

                        <!-- Aula -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Aula *</label>
                            <ComboSelect v-model="formNuevo.id_aula" :options="optsAulas"
                                placeholder="— Seleccionar aula —" emptyLabel="" />
                            <p v-if="formNuevo.errors.id_aula" class="text-xs mt-1" style="color:#ef4444;">{{ formNuevo.errors.id_aula }}</p>
                        </div>

                        <!-- Profesor -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Profesor *</label>
                            <ComboSelect v-model="formNuevo.id_profesor" :options="optsProfesores"
                                placeholder="— Seleccionar profesor —" emptyLabel="" />
                            <p v-if="formNuevo.errors.id_profesor" class="text-xs mt-1" style="color:#ef4444;">{{ formNuevo.errors.id_profesor }}</p>
                        </div>

                        <!-- Horario -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Horario *</label>
                            <ComboSelect v-model="formNuevo.id_horario" :options="optsHorarios"
                                placeholder="— Seleccionar horario —" emptyLabel="" />
                            <p v-if="formNuevo.errors.id_horario" class="text-xs mt-1" style="color:#ef4444;">{{ formNuevo.errors.id_horario }}</p>
                        </div>

                        <!-- Vacantes + Código -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                    Vacantes máx. *
                                    <span v-if="aulaSeleccionada" class="font-normal opacity-60 ml-1">(máx. {{ aulaCapacidad }})</span>
                                </label>
                                <input v-model.number="formNuevo.vacantes_max" type="number" min="1"
                                    :max="aulaCapacidad"
                                    class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                    style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);" />
                                <p v-if="formNuevo.vacantes_max > aulaCapacidad" class="text-xs mt-1" style="color:#ef4444;">
                                    Supera capacidad del aula ({{ aulaCapacidad }})
                                </p>
                                <p v-else-if="formNuevo.errors.vacantes_max" class="text-xs mt-1" style="color:#ef4444;">{{ formNuevo.errors.vacantes_max }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                    Código grupo <span class="font-normal opacity-60">(opcional)</span>
                                </label>
                                <input v-model="formNuevo.codigo_grupo" type="text" maxlength="20"
                                    placeholder="Ej: PROG101-A"
                                    class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                    style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);" />
                                <p class="text-[11px] mt-0.5 opacity-50" style="color: var(--text-secondary);">Auto: G-{ID} si vacío</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 px-6 py-4 border-t" style="border-color: var(--border-color);">
                        <button @click="showModal = false"
                            class="px-4 py-2 rounded-lg text-sm font-medium border"
                            style="border-color: var(--border-color); color: var(--text-secondary);">
                            Cancelar
                        </button>
                        <button @click="guardarNuevo" :disabled="formNuevo.processing"
                            class="px-5 py-2 rounded-lg text-sm font-medium transition-opacity"
                            :style="formNuevo.processing ? 'opacity:0.6;' : ''"
                            style="background-color: var(--primary-color); color: var(--primary-text);">
                            {{ formNuevo.processing ? 'Guardando...' : 'Crear Grupo' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ══ MODAL: Clonar Oferta (Maestro de Oferta) ══════════════════════ -->
        <Teleport to="body">
            <div v-if="showClonar"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background-color: rgba(0,0,0,0.5);"
                @click.self="showClonar = false; filtroCarreraClonar = ''">
                <div class="w-full max-w-md rounded-2xl border shadow-xl flex flex-col"
                    style="background-color: var(--card-bg); border-color: var(--border-color); max-height: 92vh;">
                    <div class="flex items-center justify-between px-6 py-4 border-b shrink-0" style="border-color: var(--border-color);">
                        <div>
                            <h2 class="font-bold text-base" style="color: var(--text-color);">📋 Clonar Oferta Académica</h2>
                            <p class="text-xs mt-0.5" style="color: var(--text-secondary);">
                                Copia todos los grupos de un período a otro. Solo modifica los que cambien.
                            </p>
                        </div>
                        <button @click="showClonar = false; filtroCarreraClonar = ''" class="text-lg leading-none" style="color: var(--text-secondary);">✕</button>
                    </div>

                    <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4">
                        <div v-if="formClonar.errors.grupo"
                            class="rounded-lg px-3 py-2 text-xs font-medium"
                            style="background-color: color-mix(in srgb,#ef4444 12%,transparent); color:#ef4444; border:1px solid color-mix(in srgb,#ef4444 30%,transparent);">
                            {{ formClonar.errors.grupo }}
                        </div>

                        <!-- Carrera + toggle vigentes (pre-filtro) -->
                        <div class="flex gap-3 items-end">
                            <div class="flex-1">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Carrera</label>
                                <select v-model="filtroCarreraClonar"
                                    class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                    style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);">
                                    <option value="">— Todas las carreras —</option>
                                    <option v-for="c in optsCarrerasFiltro" :key="c.value" :value="c.value">{{ c.label }}</option>
                                </select>
                            </div>
                            <label class="flex items-center gap-1.5 cursor-pointer select-none pb-2 shrink-0">
                                <span class="relative inline-block w-8 h-4">
                                    <input type="checkbox" v-model="soloVigentesClonar" class="sr-only peer" />
                                    <span class="block w-full h-full rounded-full transition"
                                          :style="soloVigentesClonar ? 'background-color:var(--primary-color)' : 'background-color:var(--border-color)'"></span>
                                    <span class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full shadow transition-transform"
                                          :style="soloVigentesClonar ? 'transform:translateX(16px)' : ''"></span>
                                </span>
                                <span class="text-xs" style="color: var(--text-secondary);">{{ anoActual }}</span>
                            </label>
                        </div>

                        <!-- Período origen -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                Período plantilla (origen) *
                            </label>
                            <ComboSelect v-model="formClonar.id_periodo_origen" :options="optsPeriodosOrigenFiltrados"
                                placeholder="— Seleccionar período origen —" emptyLabel="" />
                            <p v-if="gruposOrigen > 0" class="text-[11px] mt-1" style="color: var(--primary-color);">
                                {{ gruposOrigen }} grupo(s) disponibles para clonar
                            </p>
                            <p v-else-if="formClonar.id_periodo_origen" class="text-[11px] mt-1" style="color: #f59e0b;">
                                Este período no tiene grupos aún.
                            </p>
                        </div>

                        <!-- Período destino -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                Período destino *
                            </label>
                            <ComboSelect v-model="formClonar.id_periodo_destino" :options="optsPeriodosDestinoClonarFiltrados"
                                placeholder="— Seleccionar período destino —" emptyLabel="" />
                        </div>

                        <!-- Info -->
                        <div v-if="formClonar.id_periodo_origen && formClonar.id_periodo_destino"
                            class="rounded-lg p-3 border text-xs"
                            style="background-color: color-mix(in srgb,var(--primary-color) 6%,transparent); border-color: color-mix(in srgb,var(--primary-color) 25%,transparent); color: var(--text-secondary);">
                            Se copiarán <strong style="color:var(--primary-color);">{{ gruposOrigen }}</strong> grupo(s)
                            con las mismas materias, profesores, aulas y horarios.
                            Los grupos con conflictos de horario serán omitidos.
                            Luego puedes editar individualmente los que cambien.
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 px-6 py-4 border-t shrink-0" style="border-color: var(--border-color);">
                        <button @click="showClonar = false; filtroCarreraClonar = ''"
                            class="px-4 py-2 rounded-lg text-sm font-medium border"
                            style="border-color: var(--border-color); color: var(--text-secondary);">
                            Cancelar
                        </button>
                        <button @click="guardarClonar"
                            :disabled="formClonar.processing || !formClonar.id_periodo_origen || !formClonar.id_periodo_destino || gruposOrigen === 0"
                            class="px-5 py-2 rounded-lg text-sm font-medium transition-opacity"
                            :style="(formClonar.processing || !formClonar.id_periodo_origen || !formClonar.id_periodo_destino || gruposOrigen === 0) ? 'opacity:0.5;' : ''"
                            style="background-color: var(--primary-color); color: var(--primary-text);">
                            {{ formClonar.processing ? 'Clonando...' : `Clonar ${gruposOrigen} grupo(s)` }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ══ MODAL: Editar Grupo ════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showEdit"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background-color: rgba(0,0,0,0.5);"
                @click.self="showEdit = false">
                <div class="w-full max-w-lg rounded-2xl border shadow-xl flex flex-col"
                    style="background-color: var(--card-bg); border-color: var(--border-color); max-height: 92vh;">
                    <div class="flex items-center justify-between px-6 py-4 border-b shrink-0" style="border-color: var(--border-color);">
                        <h2 class="font-bold text-base" style="color: var(--text-color);">Editar Grupo</h2>
                        <button @click="showEdit = false" class="text-lg leading-none" style="color: var(--text-secondary);">✕</button>
                    </div>
                    <div class="px-6 pt-4 pb-2 shrink-0">
                        <p class="text-sm font-medium" style="color: var(--text-color);">{{ grupoEdit?.materia_nombre }}</p>
                        <p class="text-xs" style="color: var(--text-secondary);">{{ grupoEdit?.materia_codigo }}</p>
                    </div>
                    <div class="overflow-y-auto flex-1 px-6 py-4 space-y-4">
                        <div v-if="formEdit.errors.grupo"
                            class="rounded-lg px-3 py-2 text-xs font-medium"
                            style="background-color: color-mix(in srgb,#ef4444 12%,transparent); color:#ef4444; border:1px solid color-mix(in srgb,#ef4444 30%,transparent);">
                            {{ formEdit.errors.grupo }}
                        </div>

                        <!-- Aula -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Aula *</label>
                            <ComboSelect v-model="formEdit.id_aula" :options="optsAulas" placeholder="Seleccionar aula" />
                            <p v-if="aulaEditSeleccionada" class="text-[11px] mt-1 opacity-60" style="color: var(--text-secondary);">
                                Capacidad máx: {{ aulaEditSeleccionada.capacidad }}
                            </p>
                        </div>

                        <!-- Profesor -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Profesor *</label>
                            <ComboSelect v-model="formEdit.id_profesor" :options="optsProfesores" placeholder="Seleccionar profesor" />
                        </div>

                        <!-- Horario -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Horario *</label>
                            <ComboSelect v-model="formEdit.id_horario" :options="optsHorarios" placeholder="Seleccionar horario" />
                        </div>

                        <!-- Vacantes -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Vacantes máximas *</label>
                            <input v-model.number="formEdit.vacantes_max" type="number" min="1" :max="aulaEditCapacidad"
                                class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);" />
                            <p class="text-[11px] mt-1 opacity-60" style="color: var(--text-secondary);">
                                Ocupadas: {{ grupoEdit?.vacantes_ocupadas ?? 0 }}
                                <span v-if="aulaEditSeleccionada"> · Máx aula: {{ aulaEditCapacidad }}</span>
                            </p>
                            <p v-if="formEdit.vacantes_max > aulaEditCapacidad" class="text-xs mt-1" style="color:#ef4444;">
                                Supera la capacidad del aula ({{ aulaEditCapacidad }})
                            </p>
                            <p v-if="formEdit.errors.vacantes_max" class="text-xs mt-1" style="color:#ef4444;">{{ formEdit.errors.vacantes_max }}</p>
                        </div>

                        <!-- Código grupo -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                Código grupo
                                <button type="button" @click="generarCodigoEdit"
                                    class="ml-2 text-[10px] px-2 py-0.5 rounded"
                                    style="background-color: var(--primary-color); color: var(--primary-text);">
                                    ↺ Auto
                                </button>
                            </label>
                            <input v-model="formEdit.codigo_grupo" type="text" maxlength="20"
                                class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 px-6 py-4 border-t shrink-0" style="border-color: var(--border-color);">
                        <button @click="showEdit = false"
                            class="px-4 py-2 rounded-lg text-sm font-medium border"
                            style="border-color: var(--border-color); color: var(--text-secondary);">
                            Cancelar
                        </button>
                        <button @click="guardarEdit" :disabled="formEdit.processing"
                            class="px-5 py-2 rounded-lg text-sm font-medium transition-opacity"
                            :style="formEdit.processing ? 'opacity:0.6;' : ''"
                            style="background-color: var(--primary-color); color: var(--primary-text);">
                            {{ formEdit.processing ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ══ MODAL: Confirmar Eliminar ══════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="confirmarEliminar"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background-color: rgba(0,0,0,0.5);"
                @click.self="confirmarEliminar = null">
                <div class="w-full max-w-sm rounded-2xl border shadow-xl p-6"
                    style="background-color: var(--card-bg); border-color: var(--border-color);">
                    <p class="text-2xl mb-3">⚠️</p>
                    <p class="font-bold mb-1" style="color: var(--text-color);">¿Eliminar grupo?</p>
                    <p class="text-sm mb-5" style="color: var(--text-secondary);">
                        <strong>{{ confirmarEliminar.codigo_grupo }}</strong> — {{ confirmarEliminar.materia_nombre }}.
                        Si tiene inscritos, la eliminación será rechazada.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button @click="confirmarEliminar = null"
                            class="px-4 py-2 rounded-lg text-sm font-medium border"
                            style="border-color: var(--border-color); color: var(--text-secondary);">
                            Cancelar
                        </button>
                        <button @click="eliminarGrupo"
                            class="px-5 py-2 rounded-lg text-sm font-medium"
                            style="background-color: #ef4444; color: white;">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
