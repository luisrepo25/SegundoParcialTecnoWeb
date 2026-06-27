<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const cus = [
    {
        numero: 'CU1',
        titulo: 'Gestión de Usuarios',
        descripcion: 'Crear, editar, activar/desactivar y gestionar contraseñas de usuarios del sistema.',
        icono: '👥',
        ruta: 'propietario.usuarios.index',
        color: 'from-indigo-500 to-indigo-700',
    },
    // Próximos CUs se agregan aquí
];
</script>

<template>
    <Head title="Panel Propietario" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Panel Propietario
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <p class="mb-6 text-sm text-gray-500">
                    Seleccione una función para gestionar el sistema.
                </p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="cu in cus"
                        :key="cu.numero"
                        :href="route(cu.ruta)"
                        class="group relative overflow-hidden rounded-2xl bg-white shadow hover:shadow-lg transition-shadow duration-200"
                    >
                        <div :class="['absolute inset-x-0 top-0 h-1 bg-gradient-to-r', cu.color]" />
                        <div class="p-6">
                            <div class="mb-3 flex items-center gap-3">
                                <span class="text-3xl">{{ cu.icono }}</span>
                                <span class="text-xs font-bold tracking-widest text-gray-400 uppercase">
                                    {{ cu.numero }}
                                </span>
                            </div>
                            <h3 class="mb-1 text-base font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">
                                {{ cu.titulo }}
                            </h3>
                            <p class="text-sm text-gray-500 leading-snug">
                                {{ cu.descripcion }}
                            </p>
                        </div>
                        <div class="absolute bottom-4 right-4 text-gray-300 group-hover:text-indigo-400 transition-colors">
                            →
                        </div>
                    </Link>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
