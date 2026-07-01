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
    nombre:     { type: String,  default: '' },
    stats:      { type: Object,  default: () => ({}) },
    sparklines: { type: Object,  default: () => ({ pagos: [] }) },
});

const hoy = computed(() => new Date().toLocaleDateString('es-ES', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
}).toUpperCase());

// ── Selector de rango ─────────────────────────────────────────────────────────
const rangos = [
    { key: '3m', label: '3M', meses: 3 },
    { key: '6m', label: '6M', meses: 6 },
];
const rangoActivo = ref('6m');

function mesLabel(yyyymm) {
    if (!yyyymm) return '';
    const [y, m] = yyyymm.split('-');
    const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    return `${meses[parseInt(m, 10) - 1]} ${y.slice(2)}`;
}

const pagosFiltrados = computed(() => {
    const rango = rangos.find(r => r.key === rangoActivo.value);
    const data  = props.sparklines.pagos ?? [];
    return rango ? data.slice(-rango.meses) : data;
});

const totalPagosPeriodo = computed(() =>
    pagosFiltrados.value.reduce((s, r) => s + r.valor, 0)
);

const optsSparkPagos = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` Bs ${ctx.parsed.y.toLocaleString()}` } } },
    scales: { x: { display: false }, y: { display: false } },
    elements: { point: { radius: 0, hoverRadius: 3 } },
};

const dataSparkPagos = computed(() => ({
    labels: pagosFiltrados.value.map(r => mesLabel(r.label)),
    datasets: [{
        data: pagosFiltrados.value.map(r => r.valor),
        borderColor: '#10b981',
        backgroundColor: 'rgba(16,185,129,0.12)',
        fill: true, tension: 0.4, borderWidth: 2,
    }],
}));

const resumen = computed(() => [
    { label: 'Estudiantes Activos',    valor: props.stats.estudiantes_activos   ?? 0, desc: 'Con acceso habilitado',    color: '#3b82f6' },
    { label: 'Inscripciones Activas',  valor: props.stats.inscripciones_activas ?? 0, desc: 'En grupos vigentes',       color: '#6366f1' },
    { label: 'Cuotas Pendientes',      valor: props.stats.cuotas_pendientes     ?? 0, desc: 'Por cobrar',               color: '#f87171' },
    { label: 'Pagos Hoy',              valor: props.stats.pagos_hoy             ?? 0, desc: 'Transacciones del día',    color: '#10b981' },
]);

const modulos = [
    { nombre: 'Inscripciones',     descripcion: 'Asignar alumnos a grupos y gestionar vacantes.', ruta: 'secretaria.inscripciones.index' },
    { nombre: 'Caja y Pagos',      descripcion: 'Cobros de matrículas, mensualidades y pagos.',   ruta: 'secretaria.pagos.index'        },
    { nombre: 'Cronogramas',       descripcion: 'Ver cronogramas de inscripción y calendarios.',   ruta: 'secretaria.cronogramas.index'  },
    { nombre: 'Gestión Usuarios',  descripcion: 'Administrar estudiantes y personal del sistema.', ruta: 'propietario.usuarios.index'    },
];
</script>

<template>
    <Head title="Panel Secretaría" />

    <AdminLayout>
        <template #header>
            <h2 class="text-base font-semibold" style="color: var(--text-color);">Panel de Operaciones</h2>
        </template>

        <div class="space-y-8">

            <!-- Bienvenida -->
            <div>
                <p class="text-xs font-semibold tracking-widest mb-1" style="color: var(--text-secondary);">{{ hoy }}</p>
                <h1 class="text-2xl font-light" style="color: var(--text-color);">
                    Bienvenida, <strong class="font-bold">{{ nombre || $page.props.auth.user.nombre }}</strong>
                </h1>
                <p class="text-sm mt-1" style="color: var(--text-secondary);">Panel de operaciones del Instituto San Pablo del Oriente.</p>
            </div>

            <!-- Widget sparkline pagos -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <p class="text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">Ingresos por período</p>
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
                            <p class="text-xs font-medium" style="color: var(--text-secondary);">Pagos recibidos</p>
                            <p class="text-2xl font-bold mt-0.5" style="color: var(--text-color);">
                                Bs {{ totalPagosPeriodo.toLocaleString('es-BO', { maximumFractionDigits: 0 }) }}
                            </p>
                            <p class="text-[11px]" style="color: var(--text-secondary);">matrículas y materias</p>
                        </div>
                        <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                              style="background-color: rgba(16,185,129,0.15); color: #10b981;">
                            {{ rangos.find(r => r.key === rangoActivo)?.label }}
                        </span>
                    </div>
                    <div style="height: 70px;">
                        <Line v-if="pagosFiltrados.length" :data="dataSparkPagos" :options="optsSparkPagos" />
                        <div v-else class="flex items-center justify-center h-full text-xs" style="color: var(--text-secondary);">Sin pagos registrados</div>
                    </div>
                </div>
            </div>

            <!-- Métricas clave -->
            <section>
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-3" style="color: var(--text-secondary);">Métricas clave</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div v-for="item in resumen" :key="item.label"
                         class="flex flex-col p-5 rounded-xl"
                         style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="flex items-start justify-between mb-2">
                            <p class="text-xs font-medium leading-tight pr-2" style="color: var(--text-secondary);">{{ item.label }}</p>
                            <span class="w-2 h-2 rounded-full shrink-0 mt-0.5" :style="{ backgroundColor: item.color }"></span>
                        </div>
                        <p class="text-3xl font-light" style="color: var(--text-color);">{{ item.valor }}</p>
                        <p class="text-[11px] mt-1" style="color: var(--text-secondary);">{{ item.desc }}</p>
                    </div>
                </div>
            </section>

            <!-- Accesos directos -->
            <section>
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-3" style="color: var(--text-secondary);">Accesos directos</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Link v-for="m in modulos" :key="m.ruta" :href="route(m.ruta)"
                          class="group flex items-center gap-3 rounded-xl px-5 py-4 transition-all hover:shadow-md hover:-translate-y-0.5"
                          style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold truncate" style="color: var(--text-color);">{{ m.nombre }}</p>
                            <p class="text-xs truncate mt-0.5" style="color: var(--text-secondary);">{{ m.descripcion }}</p>
                        </div>
                        <span class="ml-auto shrink-0 transition-transform group-hover:translate-x-0.5" style="color: var(--text-secondary);">→</span>
                    </Link>
                </div>
            </section>

        </div>
    </AdminLayout>
</template>
