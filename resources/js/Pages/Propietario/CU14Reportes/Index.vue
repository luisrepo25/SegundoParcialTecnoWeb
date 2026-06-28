<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Tooltip, Legend);

const props = defineProps({
    esPropietario:  Boolean,
    filtros:        Object,
    administrativo: Object,
    academico:      Object,
});

// ── Filtros reactivos ─────────────────────────────────────────────────────────
const fUsuarios = ref(props.filtros.activo_usuarios);
const fAulas    = ref(props.filtros.activo_aulas);
const fHorarios = ref(props.filtros.activo_horarios);

function aplicarFiltros() {
    router.get(route('propietario.reportes.index'), {
        activo_usuarios: fUsuarios.value !== 'todos' ? fUsuarios.value : undefined,
        activo_aulas:    fAulas.value    !== 'todos' ? fAulas.value    : undefined,
        activo_horarios: fHorarios.value !== 'todos' ? fHorarios.value : undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

watch([fUsuarios, fAulas, fHorarios], aplicarFiltros);

// ── Paletas de color ──────────────────────────────────────────────────────────
const PALETA_ROL  = ['#f59e0b', '#6366f1', '#ec4899', '#10b981', '#3b82f6'];
const PALETA_TIPO = ['#6366f1', '#10b981', '#f59e0b', '#ec4899'];
const PALETA_DIA  = ['#3b82f6', '#6366f1', '#10b981', '#f59e0b', '#f97316', '#ec4899', '#ef4444'];

// ── Opciones base de Chart.js ─────────────────────────────────────────────────
const optsBar = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { callbacks: {
        label: (ctx) => ` ${ctx.parsed.y}`,
    }}},
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
        tooltip: { callbacks: { label: (ctx) => ` ${ctx.label}: ${ctx.parsed}` } },
    },
};

// ── Datasets computados ───────────────────────────────────────────────────────
const dataUsuarios = computed(() => ({
    labels: props.administrativo.usuariosPorRol.map(i => i.label),
    datasets: [{ data: props.administrativo.usuariosPorRol.map(i => i.valor), backgroundColor: PALETA_ROL, borderWidth: 0 }],
}));

const dataAulas = computed(() => ({
    labels: props.administrativo.aulasPorTipo.map(i => i.label),
    datasets: [{
        label: 'Aulas',
        data: props.administrativo.aulasPorTipo.map(i => i.valor),
        backgroundColor: PALETA_TIPO,
        borderRadius: 6,
        borderWidth: 0,
    }],
}));

const dataCarreras = computed(() => ({
    labels: ['Activas', 'Inactivas'],
    datasets: [{ data: [props.academico.carrerasActivas, props.academico.carrerasInactivas], backgroundColor: ['#34d399', '#f87171'], borderWidth: 0 }],
}));

const dataMaterias = computed(() => ({
    labels: ['Activas', 'Inactivas'],
    datasets: [{ data: [props.academico.materiasActivas, props.academico.materiasInactivas], backgroundColor: ['#818cf8', '#f87171'], borderWidth: 0 }],
}));

const dataHorarios = computed(() => ({
    labels: props.academico.horariosPorDia.map(i => i.label),
    datasets: [{
        label: 'Horarios',
        data: props.academico.horariosPorDia.map(i => i.valor),
        backgroundColor: PALETA_DIA,
        borderRadius: 6,
        borderWidth: 0,
    }],
}));

// ── Totales para subtítulos ───────────────────────────────────────────────────
const totalUsuarios  = computed(() => props.administrativo.usuariosPorRol.reduce((s, i) => s + i.valor, 0));
const totalAulas     = computed(() => props.administrativo.aulasActivas + props.administrativo.aulasInactivas);
const totalCarreras  = computed(() => props.academico.carrerasActivas  + props.academico.carrerasInactivas);
const totalMaterias  = computed(() => props.academico.materiasActivas  + props.academico.materiasInactivas);
const totalHorarios  = computed(() => props.academico.horariosPorDia.reduce((s, i) => s + i.valor, 0));
</script>

<template>
    <Head title="Reportes y Estadísticas" />

    <AdminLayout>
        <template #header>
            <h2 class="text-base font-semibold" style="color: var(--text-color);">Reportes y Estadísticas</h2>
        </template>

        <!-- Volver -->
        <div class="mb-6">
            <Link :href="route('dashboard.propietario')"
                class="inline-flex items-center gap-1.5 text-sm font-medium transition-opacity hover:opacity-70"
                style="color: var(--text-secondary);">
                ← Volver al Dashboard
            </Link>
        </div>

        <div class="space-y-10">

            <!-- ══ ADMINISTRATIVO ════════════════════════════════════════ -->
            <section>
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-5"
                   style="color: var(--text-secondary);">Administrativo</p>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- Usuarios por rol -->
                    <div class="rounded-xl p-6" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-semibold" style="color: var(--text-color);">Usuarios por rol</h3>
                            <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                                  style="background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);">
                                {{ totalUsuarios }} total
                            </span>
                        </div>
                        <!-- Filtro -->
                        <div class="mb-4">
                            <select v-model="fUsuarios"
                                class="text-xs rounded-lg px-2 py-1 focus:outline-none"
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
                        <!-- Filtro -->
                        <div class="mb-4">
                            <select v-model="fAulas"
                                class="text-xs rounded-lg px-2 py-1 focus:outline-none"
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
            </section>

            <!-- ══ ACADÉMICO ═════════════════════════════════════════════ -->
            <section>
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-5"
                   style="color: var(--text-secondary);">Académico</p>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

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
                        <!-- Filtro -->
                        <div class="mb-4">
                            <select v-model="fHorarios"
                                class="text-xs rounded-lg px-2 py-1 focus:outline-none"
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

                <!-- Reportes pendientes académicos -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div v-for="rep in [
                        { titulo: 'Desempeño individual', req: 'CU12, CU13' },
                        { titulo: 'Tasa de aprobación por materia', req: 'CU12' },
                        { titulo: 'Estudiantes en riesgo', req: 'CU12, CU13' },
                        { titulo: 'Ocupación de grupos', req: 'CU9' },
                    ]" :key="rep.titulo"
                        class="rounded-xl p-5 opacity-50"
                        style="background-color: var(--card-bg); border: 1px dashed var(--border-color);">
                        <p class="text-xs font-semibold mb-1" style="color: var(--text-color);">{{ rep.titulo }}</p>
                        <p class="text-[11px]" style="color: var(--text-secondary);">Requiere {{ rep.req }}</p>
                    </div>
                </div>
            </section>

            <!-- ══ FINANCIERO ═════════════════════════════════════════════ -->
            <section>
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-5"
                   style="color: var(--text-secondary);">Financiero</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div v-for="rep in [
                        { titulo: 'Ingresos por matrículas', req: 'CU7' },
                        { titulo: 'Ingresos por pagos de materia', req: 'CU7' },
                        { titulo: 'Deudas y cuotas pendientes', req: 'CU7' },
                        { titulo: 'Proyección de ingresos', req: 'CU7' },
                    ]" :key="rep.titulo"
                        class="rounded-xl p-5 opacity-50"
                        style="background-color: var(--card-bg); border: 1px dashed var(--border-color);">
                        <p class="text-xs font-semibold mb-1" style="color: var(--text-color);">{{ rep.titulo }}</p>
                        <p class="text-[11px]" style="color: var(--text-secondary);">Requiere {{ rep.req }}</p>
                    </div>
                </div>
            </section>

            <!-- ══ AUDITORÍA (solo propietario) ══════════════════════════ -->
            <section v-if="esPropietario">
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-5"
                   style="color: var(--text-secondary);">Auditoría del Sistema</p>
                <div class="rounded-xl p-8 flex flex-col items-center justify-center text-center opacity-50"
                     style="background-color: var(--card-bg); border: 1px dashed var(--border-color); min-height: 130px;">
                    <p class="text-sm font-semibold mb-1" style="color: var(--text-color);">Registro de actividad del sistema</p>
                    <p class="text-xs" style="color: var(--text-secondary);">Acceso exclusivo del propietario — disponible próximamente</p>
                </div>
            </section>

        </div>
    </AdminLayout>
</template>
