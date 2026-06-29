<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DocenteLayout from '@/Layouts/DocenteLayout.vue';

defineProps({
    grupos: { type: Array, required: true },
});

const DIA_LABELS = {
    lunes: 'Lun', martes: 'Mar', miercoles: 'Mié',
    jueves: 'Jue', viernes: 'Vie', sabado: 'Sáb', domingo: 'Dom',
};
</script>

<template>
    <Head title="Mis Materias" />

    <DocenteLayout>
        <template #header>
            <div>
                <h2 class="text-base font-semibold leading-tight" style="color: var(--text-color);">Mis Materias Asignadas</h2>
                <p class="text-xs mt-0.5" style="color: var(--text-secondary);">Seleccioná un grupo para ver estudiantes y cargar notas</p>
            </div>
        </template>

        <!-- Sin grupos -->
        <div v-if="grupos.length === 0"
            class="rounded-xl p-12 text-center border"
            style="background-color: var(--card-bg); border-color: var(--border-color);">
            <p class="text-3xl mb-3">📚</p>
            <p class="font-semibold text-sm" style="color: var(--text-color);">Sin materias asignadas</p>
            <p class="text-xs mt-1" style="color: var(--text-secondary);">Contactá al director para que te asigne grupos.</p>
        </div>

        <!-- Grid de grupos -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="grupo in grupos" :key="grupo.id_oferta"
                class="rounded-xl border flex flex-col overflow-hidden"
                style="background-color: var(--card-bg); border-color: var(--border-color);">

                <!-- Cabecera -->
                <div class="px-5 py-4 flex-1">
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <h3 class="font-semibold text-sm leading-snug" style="color: var(--text-color);">
                            {{ grupo.materia }}
                        </h3>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded shrink-0"
                            style="background-color: color-mix(in srgb, var(--primary-color) 12%, transparent); color: var(--primary-color);">
                            {{ grupo.codigo_grupo || 'S/N' }}
                        </span>
                    </div>

                    <div class="space-y-1.5 text-xs" style="color: var(--text-secondary);">
                        <div class="flex items-center gap-2">
                            <span>🏫</span>
                            <span>{{ grupo.aula }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span>🕐</span>
                            <span class="capitalize">
                                {{ DIA_LABELS[grupo.dia_semana] ?? grupo.dia_semana }}
                                · {{ grupo.hora_inicio?.slice(0,5) }} – {{ grupo.hora_fin?.slice(0,5) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Footer con botón -->
                <div class="px-5 py-3 border-t" style="border-color: var(--border-color);">
                    <Link :href="route('dashboard.profesor.grupo', grupo.id_oferta)"
                        class="block w-full text-center rounded-lg px-4 py-2 text-sm font-semibold transition"
                        style="background-color: var(--primary-color); color: var(--primary-text);">
                        📋 Estudiantes y Notas
                    </Link>
                </div>
            </div>
        </div>

    </DocenteLayout>
</template>
