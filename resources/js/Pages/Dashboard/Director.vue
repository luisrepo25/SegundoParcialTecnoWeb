<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const cus = [
    {
        numero:      'CU4',
        titulo:      'Gestión de Carreras',
        descripcion: 'Registrar, editar y administrar carreras técnicas y cursos libres.',
        icono:       '🎓',
        ruta:        'director.carreras.index',
        color:       'from-green-500 to-green-700',
    },
    {
        numero:      'CU5',
        titulo:      'Gestión de Materias',
        descripcion: 'Registrar, editar y administrar materias, costos y requisitos.',
        icono:       '📚',
        ruta:        'director.materias.index',
        color:       'from-teal-500 to-teal-700',
    },
];
</script>

<template>
    <Head title="Panel Director" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--text-color);">
                Panel Director
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <p class="mb-6 text-sm" style="color: var(--text-secondary);">
                    Seleccione una función para gestionar la institución.
                </p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="cu in cus"
                        :key="cu.numero"
                        :href="route(cu.ruta)"
                        class="group relative overflow-hidden rounded-2xl shadow hover:shadow-lg transition-shadow duration-200"
                        style="background-color: var(--card-bg); border: 1px solid var(--border-color);"
                    >
                        <div :class="['absolute inset-x-0 top-0 h-1 bg-gradient-to-r', cu.color]" />
                        <div class="p-6">
                            <div class="mb-3 flex items-center gap-3">
                                <span class="text-3xl">{{ cu.icono }}</span>
                                <span class="text-xs font-bold tracking-widest uppercase" style="color: var(--text-secondary);">
                                    {{ cu.numero }}
                                </span>
                            </div>
                            <h3 class="mb-1 text-base font-semibold transition-colors" style="color: var(--text-color);">
                                {{ cu.titulo }}
                            </h3>
                            <p class="text-sm leading-snug" style="color: var(--text-secondary);">
                                {{ cu.descripcion }}
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
