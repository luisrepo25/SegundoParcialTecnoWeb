<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DocenteLayout from '@/Layouts/DocenteLayout.vue';

defineProps({
    grupo: {
        type: Object,
        required: true,
    },
    estudiantes: {
        type: Array,
        required: true,
    }
});
</script>

<template>
    <Head :title="'Grupo: ' + grupo.materia_nombre" />

    <DocenteLayout>
        <template #header>
            <div>
                <h2 class="font-semibold text-xl leading-tight" style="color: var(--text-color);">
                    {{ grupo.materia_nombre }} <span class="text-sm" style="color: var(--text-secondary);">({{ grupo.codigo_grupo || 'S/N' }})</span>
                </h2>
                <p class="text-sm mt-1" style="color: var(--text-secondary);">Lista de Estudiantes Inscritos</p>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Link para volver (fuera del header, muy limpio) -->
                <div class="mb-4 px-2 sm:px-0">
                    <Link :href="route('dashboard.profesor')" class="inline-flex items-center gap-2 text-sm font-medium transition-colors" style="color: var(--text-secondary);" onmouseover="this.style.color='var(--text-color)'" onmouseout="this.style.color='var(--text-secondary)'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver a Mis Materias
                    </Link>
                </div>

                <div class="overflow-hidden shadow-sm sm:rounded-lg" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs uppercase border-b" style="background-color: color-mix(in srgb, var(--text-color) 3%, transparent); color: var(--text-secondary); border-color: var(--border-color);">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Estudiante</th>
                                    <th scope="col" class="px-6 py-3">Contacto</th>
                                    <th scope="col" class="px-6 py-3 text-center">Estado</th>
                                    <th scope="col" class="px-6 py-3 text-center">Calificación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="est in estudiantes" :key="est.id_inscripcion" class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-4">
                                            <img v-if="est.foto_perfil" :src="($page.props.asset_url || '') + '/imagenes/' + est.foto_perfil" class="w-10 h-10 rounded-full object-cover shrink-0 ring-2 ring-gray-100">
                                            <div v-else class="flex items-center justify-center w-10 h-10 rounded-full text-sm font-bold shrink-0 ring-2 ring-gray-100"
                                                 style="background-color: var(--primary-color); color: var(--primary-text);">
                                                {{ est.nombre.charAt(0).toUpperCase() }}
                                            </div>
                                            
                                            <div>
                                                <div class="font-medium text-gray-900">{{ est.apellido }}, {{ est.nombre }}</div>
                                                <div class="text-xs text-gray-500">Legajo: {{ est.legajo }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ est.email }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium"
                                              :class="est.estado === 'aprobada' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'">
                                            {{ est.estado.replace('_', ' ').toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-gray-900">
                                        {{ est.calificacion_final ?? '-' }}
                                    </td>
                                </tr>
                                <tr v-if="estudiantes.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        No hay estudiantes inscritos en este grupo todavía.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </DocenteLayout>
</template>
