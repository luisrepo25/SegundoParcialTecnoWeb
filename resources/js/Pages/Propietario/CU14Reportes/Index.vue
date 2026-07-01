<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import {
    ArcElement, BarElement, CategoryScale, Chart as ChartJS,
    Filler, Legend, LinearScale, LineElement, PointElement, Tooltip,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, LineElement, PointElement, Filler, Tooltip, Legend);

const props = defineProps({
    esPropietario:  Boolean,
    filtros:        Object,
    periodos:       { type: Array, default: () => [] },
    carreras:       { type: Array, default: () => [] },
    administrativo: Object,
    academico:      Object,
    financiero:     Object,
});

// ── Filtros reactivos ─────────────────────────────────────────────────────────
const fUsuarios = ref(props.filtros.activo_usuarios);
const fAulas    = ref(props.filtros.activo_aulas);
const fHorarios = ref(props.filtros.activo_horarios);
const fPeriodo  = ref(props.filtros.nombre_periodo ?? '');
const fCarrera  = ref(props.filtros.id_carrera     ?? '');

function aplicarFiltros() {
    router.get(route('propietario.reportes.index'), {
        activo_usuarios: fUsuarios.value !== 'todos' ? fUsuarios.value : undefined,
        activo_aulas:    fAulas.value    !== 'todos' ? fAulas.value    : undefined,
        activo_horarios: fHorarios.value !== 'todos' ? fHorarios.value : undefined,
        nombre_periodo:  fPeriodo.value  || undefined,
        id_carrera:      fCarrera.value  || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

// Cuando cambia la carrera, resetear período si ya no pertenece a esa carrera
watch(fCarrera, (nuevaCarrera) => {
    if (fPeriodo.value && nuevaCarrera) {
        const valido = props.periodos.some(
            p => p.nombre === fPeriodo.value && p.id_carrera === Number(nuevaCarrera)
        );
        if (!valido) fPeriodo.value = '';
    }
});

watch([fUsuarios, fAulas, fHorarios, fPeriodo, fCarrera], aplicarFiltros);

// Períodos filtrados según carrera seleccionada (deduplica nombres cuando es "Todas")
const periodosDisponibles = computed(() => {
    if (!fCarrera.value) {
        // Sin carrera: un período por nombre único, el más reciente
        const map = new Map();
        for (const p of props.periodos) {
            if (!map.has(p.nombre) || p.max_fecha > map.get(p.nombre).max_fecha)
                map.set(p.nombre, p);
        }
        return [...map.values()].sort((a, b) => b.max_fecha?.localeCompare(a.max_fecha ?? '') ?? 0);
    }
    return props.periodos
        .filter(p => p.id_carrera === Number(fCarrera.value))
        .sort((a, b) => b.max_fecha?.localeCompare(a.max_fecha ?? '') ?? 0);
});

const carreraSeleccionada = computed(() =>
    props.carreras.find(c => c.id_carrera === Number(fCarrera.value))
);

const hayFiltroActivo = computed(() => !!(fPeriodo.value || fCarrera.value));

// ── Helpers ───────────────────────────────────────────────────────────────────
function mesLabel(yyyymm) {
    if (!yyyymm) return '';
    const [y, m] = yyyymm.split('-');
    const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    return `${meses[parseInt(m, 10) - 1]} ${y.slice(2)}`;
}

function formatBs(n) {
    return 'Bs ' + Number(n || 0).toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

// ── Paletas de color ──────────────────────────────────────────────────────────
const PALETA_ROL  = ['#f59e0b', '#6366f1', '#ec4899', '#10b981', '#3b82f6'];
const PALETA_TIPO = ['#6366f1', '#10b981', '#f59e0b', '#ec4899'];
const PALETA_DIA  = ['#3b82f6', '#6366f1', '#10b981', '#f59e0b', '#f97316', '#ec4899', '#ef4444'];
const PALETA_BAR  = ['#6366f1','#8b5cf6','#a78bfa','#c4b5fd','#818cf8','#60a5fa','#34d399','#f59e0b'];

// ── Opciones base de Chart.js ─────────────────────────────────────────────────
const optsBar = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y}` } } },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 11 } }, border: { display: false } },
        y: { grid: { color: 'rgba(156,163,175,0.12)' }, ticks: { color: '#9ca3af', font: { size: 11 }, stepSize: 1 }, border: { display: false } },
    },
};

const optsDoughnut = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '68%',
    plugins: {
        legend: { display: true, position: 'bottom', labels: { color: '#9ca3af', padding: 14, font: { size: 11 }, boxWidth: 12 } },
        tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } },
    },
};

const optsBarH = {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.x}%` } } },
    scales: {
        x: { min: 0, max: 100, grid: { color: 'rgba(156,163,175,0.12)' }, ticks: { color: '#9ca3af', font: { size: 10 }, callback: v => v + '%' }, border: { display: false } },
        y: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } }, border: { display: false } },
    },
};

const optsBarStacked = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: true, position: 'bottom', labels: { color: '#9ca3af', padding: 10, font: { size: 10 }, boxWidth: 10 } }, tooltip: {} },
    scales: {
        x: { stacked: true, grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } }, border: { display: false } },
        y: { stacked: true, grid: { color: 'rgba(156,163,175,0.12)' }, ticks: { color: '#9ca3af', font: { size: 10 } }, border: { display: false } },
    },
};

const optsBarBs = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` Bs ${ctx.parsed.y.toLocaleString()}` } } },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } }, border: { display: false } },
        y: { grid: { color: 'rgba(156,163,175,0.12)' }, ticks: { color: '#9ca3af', font: { size: 10 }, callback: v => 'Bs ' + v.toLocaleString() }, border: { display: false } },
    },
};

const optsLine = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` Bs ${ctx.parsed.y.toLocaleString()}` } } },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } }, border: { display: false } },
        y: { grid: { color: 'rgba(156,163,175,0.12)' }, ticks: { color: '#9ca3af', font: { size: 10 }, callback: v => 'Bs ' + v.toLocaleString() }, border: { display: false } },
    },
};

// ── Datasets — Administrativo ─────────────────────────────────────────────────
const dataUsuarios = computed(() => ({
    labels:   props.administrativo.usuariosPorRol.map(i => i.label),
    datasets: [{ data: props.administrativo.usuariosPorRol.map(i => i.valor), backgroundColor: PALETA_ROL, borderWidth: 0 }],
}));

const dataAulas = computed(() => ({
    labels:   props.administrativo.aulasPorTipo.map(i => i.label),
    datasets: [{ label: 'Aulas', data: props.administrativo.aulasPorTipo.map(i => i.valor), backgroundColor: PALETA_TIPO, borderRadius: 6, borderWidth: 0 }],
}));

const dataInscripcionesPorCarrera = computed(() => ({
    labels:   props.administrativo.inscripcionesPorCarrera.map(i => i.label),
    datasets: [{ label: 'Inscripciones', data: props.administrativo.inscripcionesPorCarrera.map(i => i.valor), backgroundColor: PALETA_BAR, borderRadius: 6, borderWidth: 0 }],
}));

const dataCargaHoraria = computed(() => ({
    labels:   props.administrativo.cargaHoraria.map(i => i.label),
    datasets: [{ label: 'Grupos', data: props.administrativo.cargaHoraria.map(i => i.valor), backgroundColor: '#10b981', borderRadius: 4, borderWidth: 0 }],
}));

const dataDisponibilidad = computed(() => ({
    labels:   props.administrativo.disponibilidadAulas.map(i => i.label),
    datasets: [
        { label: 'En uso',    data: props.administrativo.disponibilidadAulas.map(i => i.grupos_asignados), backgroundColor: '#6366f1', borderRadius: { topLeft: 0, topRight: 0, bottomLeft: 4, bottomRight: 4 }, borderWidth: 0 },
        { label: 'Libres',    data: props.administrativo.disponibilidadAulas.map(i => Math.max(0, i.capacidad - i.grupos_asignados)), backgroundColor: 'rgba(99,102,241,0.18)', borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 }, borderWidth: 0 },
    ],
}));

// ── Datasets — Académico ──────────────────────────────────────────────────────
const dataCarreras = computed(() => ({
    labels:   ['Activas', 'Inactivas'],
    datasets: [{ data: [props.academico.carrerasActivas, props.academico.carrerasInactivas], backgroundColor: ['#34d399', '#f87171'], borderWidth: 0 }],
}));

const dataMaterias = computed(() => ({
    labels:   ['Activas', 'Inactivas'],
    datasets: [{ data: [props.academico.materiasActivas, props.academico.materiasInactivas], backgroundColor: ['#818cf8', '#f87171'], borderWidth: 0 }],
}));

const dataHorarios = computed(() => ({
    labels:   props.academico.horariosPorDia.map(i => i.label),
    datasets: [{ label: 'Horarios', data: props.academico.horariosPorDia.map(i => i.valor), backgroundColor: PALETA_DIA, borderRadius: 6, borderWidth: 0 }],
}));

const dataAprobacion = computed(() => ({
    labels:   props.academico.tasaAprobacion.map(i => i.label),
    datasets: [{
        label: 'Aprobación %',
        data:  props.academico.tasaAprobacion.map(i => i.tasa),
        backgroundColor: props.academico.tasaAprobacion.map(i => i.tasa >= 70 ? '#34d399' : i.tasa >= 51 ? '#f59e0b' : '#f87171'),
        borderRadius: 4,
        borderWidth: 0,
    }],
}));

const dataRiesgo = computed(() => ({
    labels:   props.academico.estudiantesEnRiesgo.map(i => i.label),
    datasets: [{ label: 'En riesgo', data: props.academico.estudiantesEnRiesgo.map(i => i.valor), backgroundColor: '#f87171', borderRadius: 6, borderWidth: 0 }],
}));

const dataOcupacion = computed(() => ({
    labels:   props.academico.ocupacionGrupos.map(i => i.label),
    datasets: [
        { label: 'Ocupadas',    data: props.academico.ocupacionGrupos.map(i => i.ocupadas),                                    backgroundColor: '#6366f1',              borderWidth: 0 },
        { label: 'Disponibles', data: props.academico.ocupacionGrupos.map(i => Math.max(0, i.capacidad - i.ocupadas)), backgroundColor: 'rgba(99,102,241,0.18)', borderWidth: 0 },
    ],
}));

// ── Datasets — Financiero ─────────────────────────────────────────────────────
const dataIngresosMatriculas = computed(() => ({
    labels:   props.financiero.ingresosMatriculas.map(i => mesLabel(i.label)),
    datasets: [{ label: 'Matrículas', data: props.financiero.ingresosMatriculas.map(i => i.valor), backgroundColor: '#6366f1', borderRadius: 4, borderWidth: 0 }],
}));

const dataIngresosMaterias = computed(() => ({
    labels:   props.financiero.ingresosMaterias.map(i => mesLabel(i.label)),
    datasets: [{ label: 'Materias sueltas', data: props.financiero.ingresosMaterias.map(i => i.valor), backgroundColor: '#10b981', borderRadius: 4, borderWidth: 0 }],
}));

const dataProyeccion = computed(() => ({
    labels:   props.financiero.proyeccion.map(i => mesLabel(i.label)),
    datasets: [{
        label: 'Proyectado',
        data: props.financiero.proyeccion.map(i => i.valor),
        borderColor: '#f59e0b',
        backgroundColor: 'rgba(245,158,11,0.12)',
        fill: true,
        tension: 0.3,
        pointBackgroundColor: '#f59e0b',
        pointBorderColor: '#f59e0b',
        pointRadius: 4,
    }],
}));

// ── Totales para subtítulos ───────────────────────────────────────────────────
const totalUsuarios       = computed(() => props.administrativo.usuariosPorRol.reduce((s, i) => s + i.valor, 0));
const totalAulas          = computed(() => props.administrativo.aulasActivas + props.administrativo.aulasInactivas);
const totalCarreras       = computed(() => props.academico.carrerasActivas  + props.academico.carrerasInactivas);
const totalMaterias       = computed(() => props.academico.materiasActivas  + props.academico.materiasInactivas);
const totalHorarios       = computed(() => props.academico.horariosPorDia.reduce((s, i) => s + i.valor, 0));
const totalRiesgo         = computed(() => props.academico.estudiantesEnRiesgo.reduce((s, i) => s + i.valor, 0));
const totalInscripciones  = computed(() => props.administrativo.inscripcionesPorCarrera.reduce((s, i) => s + i.valor, 0));
</script>

<template>
    <Head title="Reportes y Estadísticas" />

    <AdminLayout>
        <template #header>
            <h2 class="text-base font-semibold" style="color: var(--text-color);">Reportes y Estadísticas</h2>
        </template>

        <!-- Volver -->
        <div class="mb-4">
            <Link :href="route('dashboard.propietario')"
                class="inline-flex items-center gap-1.5 text-sm font-medium transition-opacity hover:opacity-70"
                style="color: var(--text-secondary);">
                ← Volver al Dashboard
            </Link>
        </div>

        <!-- ── Barra de filtros globales ── -->
        <div class="rounded-xl px-5 py-3.5 mb-6 flex flex-wrap items-center gap-4"
             style="background-color: var(--card-bg); border: 1px solid var(--border-color);">

            <!-- Período -->
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold shrink-0" style="color: var(--text-secondary);">Período</span>
                <select v-model="fPeriodo"
                        class="text-xs rounded-lg px-3 py-1.5 focus:outline-none"
                        style="background-color: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color);">
                    <option value="">Todos</option>
                    <option v-for="p in periodosDisponibles" :key="p.nombre + '_' + p.id_carrera" :value="p.nombre">{{ p.nombre }}</option>
                </select>
            </div>

            <div class="w-px self-stretch" style="background-color: var(--border-color);"></div>

            <!-- Carrera -->
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold shrink-0" style="color: var(--text-secondary);">Carrera</span>
                <select v-model="fCarrera"
                        class="text-xs rounded-lg px-3 py-1.5 focus:outline-none"
                        style="background-color: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color);">
                    <option value="">Todas</option>
                    <option v-for="c in carreras" :key="c.id_carrera" :value="c.id_carrera">{{ c.nombre }}</option>
                </select>
            </div>

            <!-- Chips activos -->
            <div class="flex flex-wrap gap-1.5 ml-1">
                <span v-if="fPeriodo"
                      class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full font-medium"
                      style="background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);">
                    {{ fPeriodo }}
                    <button @click="fPeriodo = ''" class="opacity-60 hover:opacity-100 leading-none">✕</button>
                </span>
                <span v-if="fCarrera && carreraSeleccionada"
                      class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full font-medium"
                      style="background-color: color-mix(in srgb, #10b981 15%, transparent); color: #10b981;">
                    {{ carreraSeleccionada.nombre }}
                    <button @click="fCarrera = ''" class="opacity-60 hover:opacity-100 leading-none">✕</button>
                </span>
            </div>

            <span class="text-[11px] ml-auto shrink-0" style="color: var(--text-secondary);">
                Afecta: aprobación · riesgo · inscripciones · carga horaria
            </span>
        </div>

        <!-- Aviso de filtro activo -->
        <div v-if="hayFiltroActivo"
             class="rounded-xl px-4 py-3 mb-2 flex items-center gap-2 text-sm"
             style="background-color: color-mix(in srgb,#6366f1 8%,transparent); border: 1px solid color-mix(in srgb,#6366f1 25%,transparent); color:#6366f1;">
            <span>🔍</span>
            <span>Mostrando solo los reportes que varían con el filtro activo.</span>
        </div>

        <div class="space-y-10">

            <!-- ══ ADMINISTRATIVO ════════════════════════════════════════ -->
            <section v-if="!hayFiltroActivo">
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-5"
                   style="color: var(--text-secondary);">Administrativo</p>

                <!-- Fila 1: Usuarios + Aulas -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

                    <!-- Usuarios por rol -->
                    <div class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-semibold" style="color: var(--text-color);">Usuarios por rol</h3>
                            <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                                  style="background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);">
                                {{ totalUsuarios }} total
                            </span>
                        </div>
                        <div class="mb-4">
                            <select v-model="fUsuarios" class="text-xs rounded-lg px-2 py-1 focus:outline-none"
                                style="background-color: var(--card-bg); color: var(--text-secondary); border: 1px solid var(--border-color);">
                                <option value="todos">Todos</option>
                                <option value="1">Solo activos</option>
                                <option value="0">Solo inactivos</option>
                            </select>
                        </div>
                        <div style="height: 220px;">
                            <Doughnut :data="dataUsuarios" :options="optsDoughnut" />
                        </div>
                    </div>

                    <!-- Aulas por tipo -->
                    <div class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-semibold" style="color: var(--text-color);">Aulas por tipo</h3>
                            <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                                  style="background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);">
                                {{ totalAulas }} total
                            </span>
                        </div>
                        <div class="mb-4">
                            <select v-model="fAulas" class="text-xs rounded-lg px-2 py-1 focus:outline-none"
                                style="background-color: var(--card-bg); color: var(--text-secondary); border: 1px solid var(--border-color);">
                                <option value="todos">Todas</option>
                                <option value="1">Solo activas</option>
                                <option value="0">Solo inactivas</option>
                            </select>
                        </div>
                        <div style="height: 220px;">
                            <Bar :data="dataAulas" :options="optsBar" />
                        </div>
                        <div class="mt-3 flex gap-4 pt-3 border-t" style="border-color: var(--border-color);">
                            <span class="text-xs" style="color: var(--text-secondary);">
                                Activas: <strong style="color: #34d399">{{ administrativo.aulasActivas }}</strong>
                            </span>
                            <span class="text-xs" style="color: var(--text-secondary);">
                                Inactivas: <strong style="color: #f87171">{{ administrativo.aulasInactivas }}</strong>
                            </span>
                        </div>
                    </div>

                </div>

                <!-- Fila 2: Inscripciones + Carga horaria + Disponibilidad aulas -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Inscripciones por carrera -->
                    <div class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold" style="color: var(--text-color);">Inscripciones por carrera</h3>
                            <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                                  style="background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);">
                                {{ totalInscripciones }} total
                            </span>
                        </div>
                        <div v-if="administrativo.inscripcionesPorCarrera.length" style="height: 200px;">
                            <Bar :data="dataInscripcionesPorCarrera" :options="optsBar" />
                        </div>
                        <div v-else class="flex items-center justify-center" style="height: 200px;">
                            <p class="text-sm" style="color: var(--text-secondary);">Sin inscripciones</p>
                        </div>
                    </div>

                    <!-- Carga horaria de profesores -->
                    <div class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold" style="color: var(--text-color);">Carga horaria</h3>
                                <p class="text-[11px] mt-0.5" style="color: var(--text-secondary);">
                                    {{ fPeriodo || 'Grupos activos' }} · por profesor
                                </p>
                            </div>
                            <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                                  style="background-color: rgba(16,185,129,0.15); color: #10b981;">grupos</span>
                        </div>
                        <div v-if="administrativo.cargaHoraria.length" style="height: 200px;">
                            <Bar :data="dataCargaHoraria" :options="optsBar" />
                        </div>
                        <div v-else class="flex items-center justify-center" style="height: 200px;">
                            <p class="text-sm" style="color: var(--text-secondary);">Sin grupos activos</p>
                        </div>
                    </div>

                    <!-- Disponibilidad de aulas -->
                    <div class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold" style="color: var(--text-color);">Disponibilidad de aulas</h3>
                                <p class="text-[11px] mt-0.5" style="color: var(--text-secondary);">Uso actual vs capacidad</p>
                            </div>
                        </div>
                        <div v-if="administrativo.disponibilidadAulas.length" style="height: 200px;">
                            <Bar :data="dataDisponibilidad" :options="optsBarStacked" />
                        </div>
                        <div v-else class="flex items-center justify-center" style="height: 200px;">
                            <p class="text-sm" style="color: var(--text-secondary);">Sin aulas activas</p>
                        </div>
                    </div>

                </div>
            </section>

            <!-- ══ ACADÉMICO ═════════════════════════════════════════════ -->
            <section>
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-5"
                   style="color: var(--text-secondary);">Académico</p>

                <!-- Fila 1: Carreras + Materias + Horarios — solo sin filtro -->
                <div v-if="!hayFiltroActivo" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

                    <!-- Carreras -->
                    <div class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold" style="color: var(--text-color);">Carreras</h3>
                            <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                                  style="background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);">
                                {{ totalCarreras }} total
                            </span>
                        </div>
                        <div style="height: 180px;">
                            <Doughnut :data="dataCarreras" :options="optsDoughnut" />
                        </div>
                    </div>

                    <!-- Materias -->
                    <div class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold" style="color: var(--text-color);">Materias</h3>
                            <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                                  style="background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);">
                                {{ totalMaterias }} total
                            </span>
                        </div>
                        <div style="height: 180px;">
                            <Doughnut :data="dataMaterias" :options="optsDoughnut" />
                        </div>
                    </div>

                    <!-- Horarios por día -->
                    <div class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-semibold" style="color: var(--text-color);">Horarios por día</h3>
                            <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                                  style="background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);">
                                {{ totalHorarios }} total
                            </span>
                        </div>
                        <div class="mb-4">
                            <select v-model="fHorarios" class="text-xs rounded-lg px-2 py-1 focus:outline-none"
                                style="background-color: var(--card-bg); color: var(--text-secondary); border: 1px solid var(--border-color);">
                                <option value="todos">Todos</option>
                                <option value="1">Solo activos</option>
                                <option value="0">Solo inactivos</option>
                            </select>
                        </div>
                        <div style="height: 180px;">
                            <Bar :data="dataHorarios" :options="optsBar" />
                        </div>
                    </div>

                </div>

                <!-- Fila 2: Tasa aprobación + Ocupación grupos -->
                <div :class="hayFiltroActivo ? 'mb-6' : 'grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6'">

                    <!-- Tasa de aprobación por materia -->
                    <div class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold" style="color: var(--text-color);">Tasa de aprobación por materia</h3>
                                <p class="text-[11px] mt-0.5" style="color: var(--text-secondary);">Top 8 por volumen de inscritos</p>
                            </div>
                            <div class="flex gap-1.5 text-[10px]">
                                <span class="px-1.5 py-0.5 rounded" style="background-color: rgba(52,211,153,0.18); color: #34d399;">≥70%</span>
                                <span class="px-1.5 py-0.5 rounded" style="background-color: rgba(245,158,11,0.18); color: #f59e0b;">51-69%</span>
                                <span class="px-1.5 py-0.5 rounded" style="background-color: rgba(248,113,113,0.18); color: #f87171;">&lt;51%</span>
                            </div>
                        </div>
                        <div v-if="academico.tasaAprobacion.length" style="height: 240px;">
                            <Bar :data="dataAprobacion" :options="optsBarH" />
                        </div>
                        <div v-else class="flex items-center justify-center" style="height: 240px;">
                            <p class="text-sm" style="color: var(--text-secondary);">Sin calificaciones registradas</p>
                        </div>
                    </div>

                    <!-- Ocupación de grupos por periodo (sin filtro activo) -->
                    <div v-if="!hayFiltroActivo" class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold" style="color: var(--text-color);">Ocupación de grupos por periodo</h3>
                                <p class="text-[11px] mt-0.5" style="color: var(--text-secondary);">Últimos 6 periodos</p>
                            </div>
                        </div>
                        <div v-if="academico.ocupacionGrupos.length" style="height: 240px;">
                            <Bar :data="dataOcupacion" :options="optsBarStacked" />
                        </div>
                        <div v-else class="flex items-center justify-center" style="height: 240px;">
                            <p class="text-sm" style="color: var(--text-secondary);">Sin periodos registrados</p>
                        </div>
                    </div>

                </div>

                <!-- Fila 3: Estudiantes en riesgo -->
                <div class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold" style="color: var(--text-color);">Estudiantes en riesgo por carrera</h3>
                            <p class="text-[11px] mt-0.5" style="color: var(--text-secondary);">
                                Inscripciones activas con nota &lt; 51 o sin calificación
                            </p>
                        </div>
                        <span v-if="totalRiesgo > 0"
                              class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                              style="background-color: rgba(248,113,113,0.15); color: #f87171;">
                            {{ totalRiesgo }} en riesgo
                        </span>
                        <span v-else
                              class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                              style="background-color: rgba(52,211,153,0.15); color: #34d399;">
                            Sin riesgo detectado
                        </span>
                    </div>
                    <div v-if="academico.estudiantesEnRiesgo.length" style="height: 180px;">
                        <Bar :data="dataRiesgo" :options="optsBar" />
                    </div>
                    <div v-else class="flex items-center justify-center" style="height: 120px;">
                        <p class="text-sm" style="color: #34d399;">Todos los estudiantes activos tienen nota aprobatoria</p>
                    </div>
                </div>

            </section>

            <!-- ══ FINANCIERO ═════════════════════════════════════════════ -->
            <section v-if="!hayFiltroActivo">
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-5"
                   style="color: var(--text-secondary);">Financiero</p>

                <!-- Stat cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">

                    <div class="rounded-xl p-5" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <p class="text-[11px] font-medium mb-1" style="color: var(--text-secondary);">Cuotas pendientes</p>
                        <p class="text-2xl font-bold" style="color: var(--text-color);">{{ financiero.cuotasPendientes.total_cuotas }}</p>
                        <p class="text-[11px] mt-1" style="color: var(--text-secondary);">cuotas sin cobrar</p>
                    </div>

                    <div class="rounded-xl p-5" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <p class="text-[11px] font-medium mb-1" style="color: var(--text-secondary);">Deuda total</p>
                        <p class="text-2xl font-bold" style="color: #f87171;">{{ formatBs(financiero.cuotasPendientes.total_deuda) }}</p>
                        <p class="text-[11px] mt-1" style="color: var(--text-secondary);">en cuotas impagas</p>
                    </div>

                    <div class="rounded-xl p-5" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <p class="text-[11px] font-medium mb-1" style="color: var(--text-secondary);">Próxima cuota vence</p>
                        <p class="text-2xl font-bold" style="color: #f59e0b;">
                            {{ financiero.cuotasPendientes.proxima
                               ? new Date(financiero.cuotasPendientes.proxima + 'T12:00:00').toLocaleDateString('es-BO', { day: '2-digit', month: 'short' })
                               : '—' }}
                        </p>
                        <p class="text-[11px] mt-1" style="color: var(--text-secondary);">próxima fecha de vencimiento</p>
                    </div>

                </div>

                <!-- Ingresos matrículas + materias sueltas -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

                    <!-- Ingresos por matrículas -->
                    <div class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold" style="color: var(--text-color);">Ingresos por matrículas</h3>
                                <p class="text-[11px] mt-0.5" style="color: var(--text-secondary);">Últimos 12 meses</p>
                            </div>
                            <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                                  style="background-color: rgba(99,102,241,0.15); color: #6366f1;">pagado</span>
                        </div>
                        <div v-if="financiero.ingresosMatriculas.length" style="height: 200px;">
                            <Bar :data="dataIngresosMatriculas" :options="optsBarBs" />
                        </div>
                        <div v-else class="flex items-center justify-center" style="height: 160px;">
                            <p class="text-sm" style="color: var(--text-secondary);">Sin pagos de matrícula registrados</p>
                        </div>
                    </div>

                    <!-- Ingresos por materias sueltas -->
                    <div class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold" style="color: var(--text-color);">Ingresos por materias sueltas</h3>
                                <p class="text-[11px] mt-0.5" style="color: var(--text-secondary);">Últimos 12 meses</p>
                            </div>
                            <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                                  style="background-color: rgba(16,185,129,0.15); color: #10b981;">pagado</span>
                        </div>
                        <div v-if="financiero.ingresosMaterias.length" style="height: 200px;">
                            <Bar :data="dataIngresosMaterias" :options="optsBarBs" />
                        </div>
                        <div v-else class="flex items-center justify-center" style="height: 160px;">
                            <p class="text-sm" style="color: var(--text-secondary);">Sin pagos por materia suelta</p>
                        </div>
                    </div>

                </div>

                <!-- Proyección de ingresos -->
                <div class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold" style="color: var(--text-color);">Proyección de ingresos</h3>
                            <p class="text-[11px] mt-0.5" style="color: var(--text-secondary);">
                                Cuotas pendientes agrupadas por fecha de vencimiento — próximos 6 meses
                            </p>
                        </div>
                        <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                              style="background-color: rgba(245,158,11,0.15); color: #f59e0b;">proyectado</span>
                    </div>
                    <div v-if="financiero.proyeccion.length" style="height: 200px;">
                        <Line :data="dataProyeccion" :options="optsLine" />
                    </div>
                    <div v-else class="flex items-center justify-center" style="height: 140px;">
                        <p class="text-sm" style="color: var(--text-secondary);">Sin cuotas pendientes próximas</p>
                    </div>
                </div>

            </section>

            <!-- ══ AUDITORÍA (solo propietario) ══════════════════════════ -->
            <section v-if="esPropietario && !hayFiltroActivo">
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-5"
                   style="color: var(--text-secondary);">Auditoría del Sistema</p>

                <div class="rounded-xl overflow-hidden" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">

                    <div v-if="administrativo.auditoria && administrativo.auditoria.length" class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color: var(--text-secondary);">Fecha / Hora</th>
                                    <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color: var(--text-secondary);">Usuario</th>
                                    <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color: var(--text-secondary);">Acción</th>
                                    <th class="text-left px-4 py-3 font-semibold" style="color: var(--text-secondary);">Descripción</th>
                                    <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color: var(--text-secondary);">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="log in administrativo.auditoria" :key="log.id_log"
                                    style="border-bottom: 1px solid var(--border-color);"
                                    class="transition-colors hover:bg-black/5">
                                    <td class="px-4 py-2.5 whitespace-nowrap font-mono" style="color: var(--text-secondary);">
                                        {{ log.fecha_hora ? new Date(log.fecha_hora).toLocaleString('es-BO') : '—' }}
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap" style="color: var(--text-color);">{{ log.usuario_nombre }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-medium"
                                              style="background-color: color-mix(in srgb, var(--primary-color) 12%, transparent); color: var(--primary-color);">
                                            {{ log.accion }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 max-w-xs truncate" style="color: var(--text-color);">{{ log.descripcion }}</td>
                                    <td class="px-4 py-2.5 font-mono whitespace-nowrap" style="color: var(--text-secondary);">{{ log.ip_origen || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                        <p class="text-sm font-medium" style="color: var(--text-secondary);">Sin registros de auditoría</p>
                        <p class="text-xs mt-1" style="color: var(--text-secondary);">La tabla seguimiento_log está vacía</p>
                    </div>

                </div>
            </section>

        </div>
    </AdminLayout>
</template>
