<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DocenteLayout from '@/Layouts/DocenteLayout.vue';

defineProps({
    grupos: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <Head title="Mis Materias" />

    <DocenteLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mis Materias Asignadas</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="grupos.length === 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    No tienes materias asignadas en este momento.
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="grupo in grupos" :key="grupo.id_oferta" class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex flex-col">
                        <div class="p-6 border-b border-gray-200 flex-grow">
                            <h3 class="text-lg font-bold text-gray-900 mb-1">{{ grupo.materia }}</h3>
                            <p class="text-sm text-gray-500 mb-4">Grupo: {{ grupo.codigo_grupo || 'S/N' }}</p>

                            <div class="space-y-2 text-sm text-gray-600">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    Aula: {{ grupo.aula }}
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ grupo.dia_semana }} ({{ grupo.hora_inicio }} - {{ grupo.hora_fin }})
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-4">
                            <Link :href="route('dashboard.profesor.grupo', grupo.id_oferta)" class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Ver Estudiantes
                            </Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </DocenteLayout>
</template>
