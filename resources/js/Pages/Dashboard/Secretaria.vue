<script setup>
import SecretariaLayout from '@/Layouts/SecretariaLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const modulos = [
    {
        nombre: 'Gestión de Usuarios',
        descripcion: 'Administrar estudiantes, profesores y personal administrativo.',
        ruta: 'propietario.usuarios.index'
    },
    {
        nombre: 'Inscripciones',
        descripcion: 'Asignar alumnos a grupos, registrar bajas y gestionar vacantes.',
        ruta: 'secretaria.inscripciones.index'
    },
    {
        nombre: 'Caja y Pagos',
        descripcion: 'Cobro de matrículas, mensualidades y verificación de transacciones.',
        ruta: 'secretaria.pagos.index'
    },
];

const resumen = [
    { label: 'Estudiantes Activos', valor: '245', detalle: 'Total inscritos este semestre' },
    { label: 'Inscripciones Hoy', valor: '8', detalle: 'Nuevos registros en sistema' },
    { label: 'Pagos Pendientes', valor: '15', detalle: 'Requieren seguimiento' },
];
</script>

<template>
    <Head title="Panel Secretaría" />

    <SecretariaLayout>
        <template #header>
            <h2 class="text-xl font-semibold tracking-tight" style="color: var(--text-color);">
                Panel de Operaciones
            </h2>
        </template>

        <div class="space-y-10">
            
            <!-- Encabezado con fecha y bienvenida dentro del contenido -->
            <div class="flex flex-col mb-2">
                <p class="text-sm font-medium opacity-60 uppercase tracking-widest" style="color: var(--text-secondary);">
                    {{ new Date().toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                </p>
                <h3 class="text-2xl font-light mt-1" style="color: var(--text-color);">Bienvenida al sistema, <span class="font-medium">{{ $page.props.auth.user.name.split(' ')[0] }}</span></h3>
            </div>

            <!-- Sección de Resumen (Ultra Minimalista, tipografía como protagonista) -->
            <section>
                <h3 class="text-[11px] font-semibold uppercase tracking-widest mb-4 opacity-50" style="color: var(--text-secondary);">Métricas Clave</h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div v-for="item in resumen" :key="item.label" class="flex flex-col p-5 rounded-xl border shadow-sm" style="background-color: var(--card-bg); border-color: var(--border-color);">
                        <p class="text-[13px] font-medium mb-1" style="color: var(--text-secondary);">{{ item.label }}</p>
                        <p class="text-4xl font-light tracking-tight mb-2" style="color: var(--text-color);">{{ item.valor }}</p>
                        <p class="text-xs opacity-60" style="color: var(--text-secondary);">{{ item.detalle }}</p>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                
                <!-- Módulos Principales -->
                <section class="lg:col-span-2">
                    <h3 class="text-[11px] font-semibold uppercase tracking-widest mb-4 opacity-50" style="color: var(--text-secondary);">Accesos Directos</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Link
                            v-for="m in modulos"
                            :key="m.ruta"
                            :href="route(m.ruta)"
                            class="group relative flex flex-col justify-between p-6 rounded-xl border shadow-sm transition-all duration-200 hover:shadow-md hover:-translate-y-0.5"
                            style="background-color: var(--card-bg); border-color: var(--border-color);"
                        >
                            <div>
                                <h4 class="text-[15px] font-medium mb-2" style="color: var(--text-color);">{{ m.nombre }}</h4>
                                <p class="text-[13px] leading-relaxed opacity-70" style="color: var(--text-secondary);">
                                    {{ m.descripcion }}
                                </p>
                            </div>
                            <div class="mt-8 flex items-center justify-between">
                                <span class="text-[11px] font-medium uppercase tracking-wider opacity-60 group-hover:opacity-100 transition-opacity" style="color: var(--text-color);">Ingresar al módulo</span>
                                <span class="opacity-40 group-hover:opacity-100 transition-opacity group-hover:translate-x-1 duration-200" style="color: var(--text-color);">→</span>
                            </div>
                        </Link>
                    </div>
                </section>

                <!-- Panel Lateral: Avisos -->
                <aside class="lg:col-span-1">
                    <h3 class="text-xs font-semibold uppercase tracking-widest mb-4 opacity-50" style="color: var(--text-secondary);">Avisos Recientes</h3>
                    
                    <div class="flex flex-col gap-6">
                        
                        <div class="flex flex-col">
                            <p class="text-[10px] font-semibold uppercase tracking-widest mb-1 opacity-50" style="color: var(--text-secondary);">Informativo</p>
                            <p class="text-sm font-medium mb-1" style="color: var(--text-color);">Apertura de Inscripciones</p>
                            <p class="text-[13px] leading-relaxed opacity-70" style="color: var(--text-secondary);">
                                El sistema está listo para recibir nuevas inscripciones para el semestre actual.
                            </p>
                            <p class="text-[11px] mt-2 opacity-40" style="color: var(--text-secondary);">Hace 2 horas</p>
                        </div>

                        <hr class="border-t opacity-30" style="border-color: var(--border-color);" />

                        <div class="flex flex-col">
                            <p class="text-[10px] font-semibold uppercase tracking-widest mb-1 opacity-50" style="color: var(--text-secondary);">Atención</p>
                            <p class="text-sm font-medium mb-1" style="color: var(--text-color);">Cierre de Caja Diario</p>
                            <p class="text-[13px] leading-relaxed opacity-70" style="color: var(--text-secondary);">
                                Verifique y valide los comprobantes de depósito antes del cierre de turno.
                            </p>
                            <p class="text-[11px] mt-2 opacity-40" style="color: var(--text-secondary);">Hace 5 horas</p>
                        </div>

                    </div>
                </aside>

            </div>
        </div>
    </SecretariaLayout>
</template>
