<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const modulos = [
    {
        name: 'Gestión de Usuarios',
        description: 'Registrar y administrar estudiantes, profesores y secretarias.',
        icon: '👥',
        route: 'admin.usuarios.index',
        color: 'bg-blue-50 text-blue-700 border-blue-200'
    },
    {
        name: 'Inscripciones',
        description: 'Asignar alumnos a grupos, registrar bajas y gestionar vacantes.',
        icon: '📝',
        route: 'secretaria.inscripciones.index',
        color: 'bg-indigo-50 text-indigo-700 border-indigo-200'
    },
    {
        name: 'Caja y Pagos',
        description: 'Cobro de matrícula, mensualidades y verificación de transacciones.',
        icon: '💰',
        route: 'secretaria.pagos.index',
        color: 'bg-emerald-50 text-emerald-700 border-emerald-200'
    }
];
</script>

<template>
    <Head title="Panel de Secretaría" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--text-color);">
                Panel Operativo de Secretaría
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <div class="mb-8">
                    <h3 class="text-lg font-medium" style="color: var(--text-color);">Módulos Disponibles</h3>
                    <p class="text-sm mt-1" style="color: var(--text-secondary);">
                        Seleccione el área en la que desea trabajar.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link 
                        v-for="modulo in modulos" 
                        :key="modulo.name"
                        :href="route(modulo.route)"
                        class="group flex flex-col justify-between p-6 rounded-2xl border transition-all hover:shadow-lg hover:-translate-y-1 bg-white dark:bg-gray-800"
                        style="border-color: var(--border-color);"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-4xl" aria-hidden="true">{{ modulo.icon }}</span>
                                <span 
                                    class="text-xs font-semibold px-2.5 py-1 rounded-full border"
                                    :class="modulo.color"
                                >
                                    Acceder
                                </span>
                            </div>
                            <h4 class="text-xl font-bold mb-2" style="color: var(--text-color);">{{ modulo.name }}</h4>
                            <p class="text-sm" style="color: var(--text-secondary);">
                                {{ modulo.description }}
                            </p>
                        </div>
                    </Link>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>