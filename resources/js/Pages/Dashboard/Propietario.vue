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
    { label: 'Usuarios Registrados', valor: props.stats.total_usuarios  ?? 0, desc: 'Total en el sistema' },
    { label: 'Usuarios Activos',     valor: props.stats.usuarios_activos ?? 0, desc: 'Con acceso habilitado' },
    { label: 'Aulas Registradas',    valor: props.stats.total_aulas      ?? 0, desc: 'Total en catálogo' },
    { label: 'Carreras',             valor: props.stats.total_carreras   ?? 0, desc: 'Registradas en el sistema' },
    { label: 'Materias',             valor: props.stats.total_materias   ?? 0, desc: 'Registradas en el sistema' },
    { label: 'Horarios',             valor: props.stats.total_horarios   ?? 0, desc: 'Bloques registrados' },
]);

const secciones = [
    {
        label: 'Académico',
        modulos: [
            {
                numero: 'CU4',
                titulo: 'Gestión de Carreras',
                descripcion: 'Registrar y administrar las carreras ofertadas por el instituto.',
                ruta: 'director.carreras.index',
            },
            {
                numero: 'CU5',
                titulo: 'Gestión de Materias',
                descripcion: 'Crear y administrar materias del catálogo académico.',
                ruta: 'director.materias.index',
            },
            {
                numero: 'CU10',
                titulo: 'Cronogramas',
                descripcion: 'Consultar y gestionar los cronogramas académicos por periodo.',
                ruta: 'secretaria.cronogramas.index',
            },
            {
                numero: 'CU13',
                titulo: 'Seguimiento Académico',
                descripcion: 'Ver historial, indicadores y progreso académico de cualquier estudiante.',
                ruta: 'propietario.seguimiento.index',
            },
        ],
    },
    {
        label: 'Financiero',
        modulos: [
            {
                numero: 'CU7',
                titulo: 'Inscripciones',
                descripcion: 'Registrar y gestionar inscripciones de estudiantes.',
                ruta: 'secretaria.inscripciones.index',
            },
            {
                numero: 'CU8',
                titulo: 'Caja y Pagos',
                descripcion: 'Registrar pagos y gestionar el flujo de caja del instituto.',
                ruta: 'secretaria.pagos.index',
            },
        ],
    },
    {
        label: 'Administrativo',
        modulos: [
            {
                numero: 'CU1',
                titulo: 'Gestión de Usuarios',
                descripcion: 'Crear, editar, activar/desactivar y gestionar contraseñas de usuarios del sistema.',
                ruta: 'propietario.usuarios.index',
            },
            {
                numero: 'CU2',
                titulo: 'Gestión de Aulas',
                descripcion: 'Registrar, editar y administrar aulas, laboratorios y salas.',
                ruta: 'propietario.aulas.index',
            },
            {
                numero: 'CU11',
                titulo: 'Gestión de Horarios',
                descripcion: 'Registrar y administrar bloques horarios por día y hora.',
                ruta: 'propietario.horarios.index',
            },
            {
                numero: 'CU14',
                titulo: 'Reportes y Estadísticas',
                descripcion: 'Visualizar estadísticas del sistema, académicas, financieras y auditoría.',
                ruta: 'propietario.reportes.index',
            },
        ],
    },
];
</script>

<template>
    <Head title="Panel Propietario" />

    <AdminLayout>
        <template #header>
            <h2 class="text-base font-semibold" style="color: var(--text-color);">Panel de Propietario</h2>
        </template>

        <!-- Fecha y bienvenida -->
        <div class="mb-8">
            <p class="text-xs font-semibold tracking-widest mb-1" style="color: var(--text-secondary);">{{ hoy }}</p>
            <h1 class="text-2xl font-light" style="color: var(--text-color);">
                Bienvenido, <strong class="font-bold">{{ nombre || $page.props.auth.user.nombre }}</strong>
            </h1>
        </div>

        <!-- Métricas -->
        <div class="mb-8">
            <p class="text-[11px] font-semibold uppercase tracking-widest mb-4" style="color: var(--text-secondary);">Resumen</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div v-for="m in metricas" :key="m.label"
                     class="rounded-xl p-6"
                     style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <p class="text-sm mb-2" style="color: var(--text-secondary);">{{ m.label }}</p>
                    <p class="text-4xl font-light mb-1" style="color: var(--text-color);">{{ m.valor }}</p>
                    <p class="text-xs" style="color: var(--text-secondary);">{{ m.desc }}</p>
                </div>
            </div>
        </div>

        <!-- Módulos por sección -->
        <div class="space-y-8">
            <div v-for="sec in secciones" :key="sec.label">
                <p class="text-[11px] font-semibold uppercase tracking-widest mb-4" style="color: var(--text-secondary);">{{ sec.label }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link
                        v-for="m in sec.modulos"
                        :key="m.numero"
                        :href="route(m.ruta)"
                        class="group rounded-xl p-6 flex flex-col justify-between transition-shadow hover:shadow-md"
                        style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-widest mb-2" style="color: var(--text-secondary);">{{ m.numero }}</p>
                            <h3 class="text-base font-semibold mb-2" style="color: var(--text-color);">{{ m.titulo }}</h3>
                            <p class="text-sm leading-snug" style="color: var(--text-secondary);">{{ m.descripcion }}</p>
                        </div>
                        <div class="mt-6 flex items-center justify-between border-t pt-4" style="border-color: var(--border-color);">
                            <span class="text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">Ingresar al módulo</span>
                            <span style="color: var(--text-secondary);">→</span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
