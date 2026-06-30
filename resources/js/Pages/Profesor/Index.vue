<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DocenteLayout from '@/Layouts/DocenteLayout.vue';

defineProps({
    materias:      { type: Array,   required: true },
    periodoActual: { type: String,  default: null },
    esFallback:    { type: Boolean, default: false },
});

const DIA = {
    lunes: 'Lun', martes: 'Mar', miercoles: 'Mié',
    jueves: 'Jue', viernes: 'Vie', sabado: 'Sáb', domingo: 'Dom',
};
</script>

<template>
    <Head title="Mis Materias" />

    <DocenteLayout>
        <template #header>
            <div>
                <h2 class="text-base font-semibold leading-tight" style="color: var(--text-color);">
                    Mis Materias Asignadas
                </h2>
                <p class="text-xs mt-0.5" style="color: var(--text-secondary);">
                    Seleccioná un grupo para ver estudiantes y cargar notas
                </p>
            </div>
        </template>

        <!-- Badge período -->
        <div v-if="periodoActual" class="mb-6 flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full"
                style="background-color: color-mix(in srgb, var(--primary-color) 12%, transparent); color: var(--primary-color);">
                <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                </svg>
                {{ periodoActual }}
            </span>
            <span v-if="esFallback" class="text-xs" style="color: var(--text-secondary);">
                Sin período activo · mostrando el más reciente
            </span>
        </div>

        <!-- Sin materias -->
        <div v-if="materias.length === 0"
            class="rounded-2xl p-14 text-center border"
            style="background-color: var(--card-bg); border-color: var(--border-color);">
            <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center text-2xl"
                style="background-color: color-mix(in srgb, var(--primary-color) 10%, transparent);">
                📚
            </div>
            <p class="font-semibold text-sm mb-1" style="color: var(--text-color);">Sin materias asignadas</p>
            <p class="text-xs" style="color: var(--text-secondary);">Contactá al director para que te asigne grupos.</p>
        </div>

        <!-- Grid de tarjetas -->
        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div v-for="mat in materias" :key="mat.materia + mat.aula"
                class="rounded-2xl border overflow-hidden"
                style="background-color: var(--card-bg); border-color: var(--border-color);">

                <!-- Cabecera con acento -->
                <div class="px-5 pt-5 pb-4"
                    style="background: linear-gradient(135deg, color-mix(in srgb, var(--primary-color) 8%, transparent), transparent);">

                    <!-- Nombre materia + contador -->
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-1 h-10 rounded-full shrink-0"
                                style="background-color: var(--primary-color); opacity: 0.7;"></div>
                            <h3 class="font-bold text-base leading-tight" style="color: var(--text-color);">
                                {{ mat.materia }}
                            </h3>
                        </div>
                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full shrink-0 mt-0.5"
                            style="background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);">
                            {{ mat.grupos.length }} grupo{{ mat.grupos.length !== 1 ? 's' : '' }}
                        </span>
                    </div>

                    <!-- Meta info: aula + días -->
                    <div class="flex flex-wrap items-center gap-3 ml-3.5">
                        <span class="flex items-center gap-1.5 text-xs font-medium"
                            style="color: var(--text-secondary);">
                            <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                            </svg>
                            {{ mat.aula }}
                        </span>
                        <!-- Chips de días -->
                        <div class="flex flex-wrap gap-1">
                            <span v-for="dia in mat.dias" :key="dia"
                                class="text-[10px] font-bold px-1.5 py-0.5 rounded"
                                style="background-color: color-mix(in srgb, var(--primary-color) 12%, transparent); color: var(--primary-color);">
                                {{ DIA[dia] ?? dia }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Separador -->
                <div class="h-px mx-5" style="background-color: var(--border-color);"></div>

                <!-- Filas de horarios -->
                <div class="px-2 py-2 space-y-1">
                    <Link v-for="grupo in mat.grupos" :key="grupo.id_oferta"
                        :href="route('dashboard.profesor.grupo', grupo.id_oferta)"
                        class="flex items-center justify-between px-3 py-2.5 rounded-xl group transition-all duration-150"
                        style="color: inherit;"
                        onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--primary-color) 6%, transparent)'"
                        onmouseout="this.style.backgroundColor='transparent'">

                        <!-- Código + horario -->
                        <div class="flex items-center gap-3">
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-md w-28 text-center shrink-0"
                                style="background-color: color-mix(in srgb, var(--text-color) 8%, transparent); color: var(--text-secondary);">
                                {{ grupo.codigo_grupo }}
                            </span>
                            <span class="text-sm font-semibold tabular-nums" style="color: var(--text-color);">
                                {{ grupo.hora_inicio?.slice(0,5) }}
                                <span class="mx-1 font-normal" style="color: var(--text-secondary);">–</span>
                                {{ grupo.hora_fin?.slice(0,5) }}
                            </span>
                        </div>

                        <!-- Flecha animada -->
                        <svg class="w-4 h-4 transition-transform duration-150 group-hover:translate-x-0.5"
                            style="color: var(--primary-color);"
                            viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </Link>
                </div>

            </div>
        </div>

    </DocenteLayout>
</template>
