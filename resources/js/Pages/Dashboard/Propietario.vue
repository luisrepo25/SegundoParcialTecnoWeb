<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats:  { type: Object, default: () => ({}) },
    nombre: { type: String, default: '' },
});

const hoy = computed(() => {
    return new Date().toLocaleDateString('es-ES', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    }).toUpperCase();
});

const metricas = computed(() => [
    { label: 'Usuarios Registrados',  valor: props.stats.total_usuarios        ?? 0, desc: 'Total en el sistema',       color: '#f59e0b' },
    { label: 'Usuarios Activos',      valor: props.stats.usuarios_activos      ?? 0, desc: 'Con acceso habilitado',      color: '#10b981' },
    { label: 'Estudiantes',           valor: props.stats.total_estudiantes     ?? 0, desc: 'En el sistema',              color: '#3b82f6' },
    { label: 'Profesores',            valor: props.stats.total_profesores      ?? 0, desc: 'Cuerpo docente',             color: '#8b5cf6' },
    { label: 'Grupos Activos',        valor: props.stats.grupos_activos        ?? 0, desc: 'Oferta vigente',             color: '#0ea5e9' },
    { label: 'Inscripciones Activas', valor: props.stats.inscripciones_activas ?? 0, desc: 'Estudiantes inscritos',      color: '#f97316' },
    { label: 'Carreras Activas',      valor: props.stats.total_carreras        ?? 0, desc: 'En catálogo académico',      color: '#059669' },
    { label: 'Cuotas Pendientes',     valor: props.stats.cuotas_pendientes     ?? 0, desc: 'Cuotas por cobrar',          color: '#ef4444' },
]);

const miniStats = computed(() => [
    { label: 'Materias Activas', valor: props.stats.total_materias   ?? 0 },
    { label: 'Horarios',         valor: props.stats.total_horarios   ?? 0 },
    { label: 'Aulas',            valor: props.stats.total_aulas      ?? 0 },
    { label: 'Períodos Activos', valor: props.stats.periodos_activos ?? 0 },
]);

const accesosRapidos = [
    { label: 'Reportes y Estadísticas', desc: 'Financiero, académico y auditoría',   ruta: 'propietario.reportes.index'   },
    { label: 'Seguimiento Académico',   desc: 'Historial y progreso de estudiantes', ruta: 'propietario.seguimiento.index' },
    { label: 'Caja y Pagos',            desc: 'Matrículas, cuotas y cobros',         ruta: 'secretaria.pagos.index'       },
    { label: 'Gestión de Usuarios',     desc: 'Crear, editar y gestionar cuentas',   ruta: 'propietario.usuarios.index'   },
];
</script>

<template>
    <Head title="Panel Propietario" />

    <AdminLayout>
        <template #header>
            <h2 class="text-base font-semibold" style="color: var(--text-color);">Panel de Propietario</h2>
        </template>

        <!-- Bienvenida -->
        <div class="mb-8">
            <p class="text-xs font-semibold tracking-widest mb-1" style="color: var(--text-secondary);">{{ hoy }}</p>
            <h1 class="text-2xl font-light" style="color: var(--text-color);">
                Bienvenido, <strong class="font-bold">{{ nombre || $page.props.auth.user.nombre }}</strong>
            </h1>
            <p class="text-sm mt-1" style="color: var(--text-secondary);">Vista general del instituto San Pablo del Oriente.</p>
        </div>

        <!-- Métricas principales -->
        <div class="mb-3">
            <p class="text-[11px] font-semibold uppercase tracking-widest mb-3" style="color: var(--text-secondary);">Indicadores del sistema</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div v-for="m in metricas" :key="m.label"
                     class="rounded-xl p-4"
                     style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <div class="flex items-start justify-between mb-2">
                        <p class="text-xs font-medium leading-tight pr-2" style="color: var(--text-secondary);">{{ m.label }}</p>
                        <span class="w-2 h-2 rounded-full shrink-0 mt-0.5" :style="{ backgroundColor: m.color }"></span>
                    </div>
                    <p class="text-3xl font-light" style="color: var(--text-color);">{{ m.valor }}</p>
                    <p class="text-[11px] mt-1" style="color: var(--text-secondary);">{{ m.desc }}</p>
                </div>
            </div>
        </div>

        <!-- Mini estadísticas -->
        <div class="mb-8 grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div v-for="s in miniStats" :key="s.label"
                 class="rounded-lg px-4 py-2.5 flex items-center justify-between"
                 style="background-color: color-mix(in srgb, var(--text-color) 4%, transparent); border: 1px solid var(--border-color);">
                <span class="text-xs" style="color: var(--text-secondary);">{{ s.label }}</span>
                <span class="text-sm font-bold" style="color: var(--text-color);">{{ s.valor }}</span>
            </div>
        </div>

        <!-- Acceso rápido -->
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-widest mb-3" style="color: var(--text-secondary);">Acceso rápido</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <Link v-for="a in accesosRapidos" :key="a.ruta"
                      :href="route(a.ruta)"
                      class="group flex items-center gap-3 rounded-xl px-4 py-3.5 transition-all hover:shadow-md"
                      style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold truncate" style="color: var(--text-color);">{{ a.label }}</p>
                        <p class="text-xs truncate" style="color: var(--text-secondary);">{{ a.desc }}</p>
                    </div>
                    <span class="ml-auto shrink-0 text-sm transition-transform group-hover:translate-x-0.5" style="color: var(--text-secondary);">→</span>
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
