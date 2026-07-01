<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS, CategoryScale, LinearScale,
    LineElement, PointElement, Filler, Tooltip,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, LineElement, PointElement, Filler, Tooltip);

const props = defineProps({
    nombre:          { type: String, default: '' },
    totalCarreras:   { type: Number, default: 0 },
    totalMaterias:   { type: Number, default: 0 },
    carrerasActivas: { type: Number, default: 0 },
    totalGrupos:     { type: Number, default: 0 },
    totalEstudiantes:{ type: Number, default: 0 },
    totalProfesores: { type: Number, default: 0 },
    sparklines:      { type: Object, default: () => ({ inscripciones: [] }) },
});

const hoy = computed(() => new Date().toLocaleDateString('es-ES', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
}).toUpperCase());

// ── Selector de rango ─────────────────────────────────────────────────────────
const rangos = [
    { key: '3m', label: '3M', meses: 3 },
    { key: '6m', label: '6M', meses: 6 },
    { key: '12m', label: '1A', meses: 12 },
];
const rangoActivo = ref('12m');

function mesLabel(yyyymm) {
    if (!yyyymm) return '';
    const [y, m] = yyyymm.split('-');
    const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    return `${meses[parseInt(m, 10) - 1]} ${y.slice(2)}`;
}

const inscFiltradas = computed(() => {
    const rango = rangos.find(r => r.key === rangoActivo.value);
    const data  = props.sparklines.inscripciones ?? [];
    return rango ? data.slice(-rango.meses) : data;
});

const totalInscPeriodo = computed(() => inscFiltradas.value.reduce((s, r) => s + r.valor, 0));

const optsSparkInsc = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y} estudiantes` } } },
    scales: { x: { display: false }, y: { display: false } },
    elements: { point: { radius: 0, hoverRadius: 3 } },
};

const dataSparkInsc = computed(() => ({
    labels: inscFiltradas.value.map(r => mesLabel(r.label)),
    datasets: [{
        data: inscFiltradas.value.map(r => r.valor),
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99,102,241,0.15)',
        fill: true, tension: 0.4, borderWidth: 2,
    }],
}));

const resumen = computed(() => [
    { label: 'Carreras Registradas',  valor: props.totalCarreras,    detalle: 'Total en el sistema',          color: '#6366f1' },
    { label: 'Carreras Activas',      valor: props.carrerasActivas,  detalle: 'Disponibles para inscripción', color: '#10b981' },
    { label: 'Materias Registradas',  valor: props.totalMaterias,    detalle: 'Total en catálogo',            color: '#f59e0b' },
    { label: 'Grupos Activos',        valor: props.totalGrupos,      detalle: 'Oferta académica vigente',     color: '#0ea5e9' },
    { label: 'Estudiantes',           valor: props.totalEstudiantes, detalle: 'En el sistema',                color: '#3b82f6' },
    { label: 'Profesores',            valor: props.totalProfesores,  detalle: 'Cuerpo docente',               color: '#8b5cf6' },
]);

const modulos = [
    { numero: 'CU4', titulo: 'Gestión de Carreras', descripcion: 'Registrar, editar y administrar carreras técnicas y cursos libres.', ruta: 'director.carreras.index' },
    { numero: 'CU5', titulo: 'Gestión de Materias', descripcion: 'Registrar, editar y administrar materias, costos y requisitos.',     ruta: 'director.materias.index' },
    { numero: 'CU8', titulo: 'Períodos Académicos',  descripcion: 'Administrar períodos y cronogramas de dictado por carrera.',         ruta: 'director.periodos.index' },
    { numero: 'CU9', titulo: 'Grupos y Notas',       descripcion: 'Gestionar grupos, docentes asignados y calificaciones.',            ruta: 'director.grupos.index'   },
];
</script>

<template>
    <Head title="Panel Director" />

    <AdminLayout>
        <template #header>
            <h2 class="text-base font-semibold" style="color: var(--text-color);">Panel de Dirección</h2>
        </template>

        <div class="space-y-8">

            <!-- Bienvenida -->
            <div>
                <p class="text-xs font-semibold tracking-widest mb-1" style="color: var(--text-secondary);">{{ hoy }}</p>
                <h1 class="text-2xl font-light" style="color: var(--text-color);">
                    Bienvenido, <strong class="font-bold">{{ nombre || $page.props.auth.user.nombre }}</strong>
                </h1>
                <p class="text-sm mt-1" style="color: var(--text-secondary);">Vista académica del instituto San Pablo del Oriente.</p>
            </div>

            <!-- Widget sparkline inscripciones -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <p class="text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">Tendencia de inscripciones</p>
                    <div class="flex items-center gap-1 rounded-lg p-0.5"
                         style="background-color: var(--bg-color); border: 1px solid var(--border-color);">
                        <button v-for="r in rangos" :key="r.key"
                                @click="rangoActivo = r.key"
                                class="px-2.5 py-1 rounded text-xs font-medium transition-all"
                                :style="rangoActivo === r.key
                                    ? 'background-color: var(--primary-color); color: var(--primary-text);'
                                    : 'color: var(--text-secondary);'">
                            {{ r.label }}
                        </button>
                    </div>
                </div>

                <div class="rounded-xl p-5" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <p class="text-xs font-medium" style="color: var(--text-secondary);">Estudiantes inscritos</p>
                            <p class="text-2xl font-bold mt-0.5" style="color: var(--text-color);">{{ totalInscPeriodo }}</p>
                            <p class="text-[11px]" style="color: var(--text-secondary);">estudiantes únicos en el período</p>
                        </div>
                        <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                              style="background-color: rgba(99,102,241,0.15); color: #6366f1;">
                            {{ rangos.find(r => r.key === rangoActivo)?.label }}
                        </span>
                    </div>
                    <div style="height: 80px;">
                        <Line v-if="inscFiltradas.length" :data="dataSparkInsc" :options="optsSparkInsc" />
                        <div v-else class="flex items-center justify-center h-full text-xs" style="color: var(--text-secondary);">Sin inscripciones registradas</div>
                    </div>
                </div>
            </div>

            <!-- Métricas académicas -->
            <section>
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-3" style="color: var(--text-secondary);">Resumen académico</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div v-for="item in resumen" :key="item.label"
                         class="flex flex-col p-5 rounded-xl"
                         style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="flex items-start justify-between mb-2">
                            <p class="text-xs font-medium leading-tight pr-2" style="color: var(--text-secondary);">{{ item.label }}</p>
                            <span class="w-2 h-2 rounded-full shrink-0 mt-0.5" :style="{ backgroundColor: item.color }"></span>
                        </div>
                        <p class="text-3xl font-light" style="color: var(--text-color);">{{ item.valor }}</p>
                        <p class="text-[11px] mt-1" style="color: var(--text-secondary);">{{ item.detalle }}</p>
                    </div>
                </div>
            </section>

            <!-- Módulos de acceso -->
            <section>
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-3" style="color: var(--text-secondary);">Módulos</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Link v-for="m in modulos" :key="m.ruta" :href="route(m.ruta)"
                          class="group relative flex flex-col justify-between p-6 rounded-xl transition-all duration-200 hover:shadow-md hover:-translate-y-0.5"
                          style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-widest mb-2 opacity-50" style="color: var(--text-secondary);">{{ m.numero }}</p>
                            <h4 class="text-sm font-semibold mb-2" style="color: var(--text-color);">{{ m.titulo }}</h4>
                            <p class="text-xs leading-relaxed opacity-70" style="color: var(--text-secondary);">{{ m.descripcion }}</p>
                        </div>
                        <div class="mt-6 flex items-center justify-between">
                            <span class="text-[11px] font-medium uppercase tracking-wider opacity-60 group-hover:opacity-100 transition-opacity" style="color: var(--text-color);">Ingresar</span>
                            <span class="opacity-40 group-hover:opacity-100 group-hover:translate-x-1 transition-all duration-200" style="color: var(--text-color);">→</span>
                        </div>
                    </Link>
                </div>
            </section>

        </div>
    </AdminLayout>
</template>
