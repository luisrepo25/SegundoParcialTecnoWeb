<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const modulos = [
    {
        nombre: 'Gestión de Usuarios',
        descripcion: 'Registrar y administrar estudiantes, profesores y secretarias.',
        icono: '👥',
        ruta: 'propietario.usuarios.index',
        color: 'from-indigo-500 to-indigo-700',
    },
    {
        nombre: 'Inscripciones',
        descripcion: 'Asignar alumnos a grupos, registrar bajas y gestionar vacantes.',
        icono: '📝',
        ruta: 'secretaria.inscripciones.index',
        color: 'from-blue-500 to-blue-700',
    },
    {
        nombre: 'Caja y Pagos',
        descripcion: 'Cobro de matrícula, mensualidades y verificación de transacciones.',
        icono: '💰',
        ruta: 'secretaria.pagos.index',
        color: 'from-emerald-500 to-emerald-700',
    },
];
</script>

<template>
    <Head title="Panel Secretaría" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--text-color);">
                Panel Operativo de Secretaría
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <p class="mb-6 text-sm" style="color: var(--text-secondary);">
                    Seleccione el área en la que desea trabajar.
                </p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="m in modulos"
                        :key="m.ruta"
                        :href="route(m.ruta)"
                        class="group relative overflow-hidden rounded-2xl shadow hover:shadow-lg transition-shadow duration-200"
                        style="background-color: var(--card-bg); border: 1px solid var(--border-color);"
                    >
                        <div :class="['absolute inset-x-0 top-0 h-1 bg-gradient-to-r', m.color]" />
                        <div class="p-6">
                            <div class="mb-3 flex items-center gap-3">
                                <span class="text-3xl">{{ m.icono }}</span>
                            </div>
                            <h3 class="mb-1 text-base font-semibold transition-colors"
                                style="color: var(--text-color);">
                                {{ m.nombre }}
                            </h3>
                            <p class="text-sm leading-snug" style="color: var(--text-secondary);">
                                {{ m.descripcion }}
                            </p>
                        </div>
                        <div class="absolute bottom-4 right-4 transition-colors" style="color: var(--text-secondary);">
                            →
                        </div>
                    </Link>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
