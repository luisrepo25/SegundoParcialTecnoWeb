<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    carrera:  Object,
    porNivel: Array,
});

const TIPOS = {
    tecnico:          'Técnico',
    tecnico_superior: 'Técnico Superior',
    curso_libre:      'Curso Libre',
};

function formatCosto(val) {
    if (!val) return '—';
    return 'Bs ' + parseFloat(val).toLocaleString('es-BO', { minimumFractionDigits: 2 });
}
</script>

<template>
    <Head :title="`Materias — ${carrera.nombre}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('director.carreras.index')"
                    class="text-sm px-3 py-1 rounded-lg border transition"
                    style="color: var(--text-secondary); border-color: var(--border-color); background: var(--card-bg);">
                    ← Carreras
                </Link>
                <div>
                    <h2 class="text-xl font-semibold leading-tight" style="color: var(--text-color);">
                        {{ carrera.nombre }}
                    </h2>
                    <p class="text-xs mt-0.5" style="color: var(--text-secondary);">
                        {{ TIPOS[carrera.tipo] ?? carrera.tipo }} · {{ carrera.duracion_niveles }} nivel(es) · {{ formatCosto(carrera.costo_carrera_completa) }}
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <!-- Sin malla curricular -->
                <div v-if="porNivel.length === 0"
                     class="rounded-xl p-10 text-center"
                     style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <p class="text-3xl mb-3">📋</p>
                    <p class="font-medium text-sm" style="color: var(--text-color);">Sin materias asignadas</p>
                    <p class="text-sm mt-1" style="color: var(--text-secondary);">
                        Esta carrera aún no tiene malla curricular configurada.
                    </p>
                    <Link :href="route('director.materias.index')"
                        class="inline-block mt-4 rounded-lg px-4 py-2 text-sm font-medium"
                        style="background-color: var(--primary-color); color: var(--primary-text);">
                        Gestionar Materias
                    </Link>
                </div>

                <!-- Materias por nivel -->
                <div v-else class="space-y-6">
                    <div v-for="nivel in porNivel" :key="nivel.numero_nivel"
                         class="rounded-xl overflow-hidden"
                         style="background-color: var(--card-bg); border: 1px solid var(--border-color);">

                        <!-- Cabecera nivel -->
                        <div class="flex items-center gap-3 px-5 py-3"
                             style="background-color: var(--bg-color); border-bottom: 1px solid var(--border-color);">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold"
                                 style="background-color: var(--primary-color); color: var(--primary-text);">
                                {{ nivel.numero_nivel }}
                            </div>
                            <div>
                                <p class="font-semibold text-sm" style="color: var(--text-color);">{{ nivel.nombre_nivel }}</p>
                                <p class="text-xs" style="color: var(--text-secondary);">{{ nivel.materias.length }} materia(s)</p>
                            </div>
                        </div>

                        <!-- Tabla materias del nivel -->
                        <table class="min-w-full">
                            <thead>
                                <tr style="background-color: var(--bg-color);">
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Ord.</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Código</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Materia</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider hidden md:table-cell" style="color: var(--text-secondary);">Duración</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider hidden md:table-cell" style="color: var(--text-secondary);">Costo/mes</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Tipo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="m in nivel.materias" :key="m.id_malla"
                                    class="border-t" style="border-color: var(--border-color);">
                                    <td class="px-4 py-3 text-sm" style="color: var(--text-secondary);">
                                        {{ m.orden_en_nivel ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-mono text-sm font-semibold" style="color: var(--primary-color);">{{ m.codigo }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-sm" style="color: var(--text-color);">{{ m.nombre }}</div>
                                        <div v-if="m.creditos" class="text-xs mt-0.5" style="color: var(--text-secondary);">{{ m.creditos }} créditos</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm hidden md:table-cell" style="color: var(--text-color);">
                                        {{ m.duracion_meses }} mes(es)
                                    </td>
                                    <td class="px-4 py-3 text-sm hidden md:table-cell" style="color: var(--text-color);">
                                        {{ formatCosto(m.costo_mensual) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="['badge', m.obligatoria ? 'badge-obligatoria' : 'badge-electiva']">
                                            {{ m.obligatoria ? 'Obligatoria' : 'Electiva' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Botón ir a gestionar materias -->
                    <div class="flex justify-end">
                        <Link :href="route('director.materias.index')"
                            class="rounded-lg px-4 py-2 text-sm font-medium border transition"
                            style="color: var(--primary-color); border-color: var(--primary-color); background: transparent;">
                            Gestionar todas las materias →
                        </Link>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.badge { display: inline-flex; border-radius: 9999px; padding: 0.125rem 0.625rem; font-size: 0.75rem; font-weight: 600; }
.badge-obligatoria { background: rgba(59,130,246,0.2);  color: #60a5fa; }
.badge-electiva    { background: rgba(245,158,11,0.2);  color: #fbbf24; }
</style>
