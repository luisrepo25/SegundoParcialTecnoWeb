<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    estudiantes: Array,
    filtros:     Object,
});

const buscar = ref(props.filtros?.buscar ?? '');

let timeout = null;
watch(buscar, () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('secretaria.pagos.index'),
            { buscar: buscar.value || undefined },
            { preserveState: true, replace: true }
        );
    }, 400);
});

const MATRICULA_BADGE = {
    pagado:  { label: 'Pagada',   style: 'background-color:rgba(52,211,153,.15);color:#34d399;' },
    pendiente:{ label: 'Pendiente', style: 'background-color:rgba(245,158,11,.15);color:#f59e0b;' },
};
const CARRERA_BADGE = {
    pagado:  { label: 'Al día',    style: 'background-color:rgba(52,211,153,.15);color:#34d399;' },
    parcial: { label: 'Con cuotas', style: 'background-color:rgba(99,102,241,.15);color:#818cf8;' },
};
</script>

<template>
    <Head title="Caja y Pagos" />

    <AdminLayout>
        <template #header>
            <h2 class="text-base font-semibold" style="color:var(--text-color);">Caja y Pagos</h2>
        </template>

        <!-- Volver -->
        <div class="mb-6">
            <Link :href="route('dashboard.propietario')"
                class="inline-flex items-center gap-1.5 text-sm font-medium transition-opacity hover:opacity-70"
                style="color:var(--text-secondary);">
                ← Volver al Dashboard
            </Link>
        </div>

        <!-- Búsqueda -->
        <div class="mb-6 flex items-center gap-3">
            <input v-model="buscar" type="text"
                placeholder="Buscar por nombre, apellido, DNI o email…"
                class="w-full max-w-md rounded-lg px-4 py-2.5 text-sm focus:outline-none"
                style="background-color:var(--card-bg);color:var(--text-color);border:1px solid var(--border-color);" />
            <span class="text-xs shrink-0" style="color:var(--text-secondary);">
                {{ estudiantes.length }} estudiante{{ estudiantes.length !== 1 ? 's' : '' }}
            </span>
        </div>

        <!-- Tabla -->
        <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border-color);">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background-color:color-mix(in srgb,var(--card-bg) 80%,var(--border-color));">
                        <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color:var(--text-secondary);">Estudiante</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color:var(--text-secondary);">Legajo</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color:var(--text-secondary);">DNI</th>
                        <th class="text-center px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color:var(--text-secondary);">Matrícula</th>
                        <th class="text-center px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color:var(--text-secondary);">Plan Carrera</th>
                        <th class="text-right px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color:var(--text-secondary);">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!estudiantes.length">
                        <td colspan="6" class="text-center py-10 text-sm" style="color:var(--text-secondary);">
                            No se encontraron estudiantes
                        </td>
                    </tr>
                    <tr v-for="e in estudiantes" :key="e.id_usuario"
                        class="border-t transition-colors"
                        style="border-color:var(--border-color);"
                        onmouseover="this.style.backgroundColor='color-mix(in srgb,var(--text-color) 3%,transparent)'"
                        onmouseout="this.style.backgroundColor='transparent'">

                        <td class="px-4 py-3">
                            <div class="font-medium" style="color:var(--text-color);">{{ e.apellido }}, {{ e.nombre }}</div>
                            <div class="text-xs" style="color:var(--text-secondary);">{{ e.email }}</div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs" style="color:var(--text-color);">{{ e.legajo ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs" style="color:var(--text-color);">{{ e.dni ?? '—' }}</td>

                        <!-- Matrícula -->
                        <td class="px-4 py-3 text-center">
                            <span v-if="e.tiene_matricula"
                                  class="text-xs px-2 py-0.5 rounded-full font-medium"
                                  :style="(MATRICULA_BADGE[e.matricula_estado] ?? MATRICULA_BADGE.pagado).style">
                                {{ (MATRICULA_BADGE[e.matricula_estado] ?? MATRICULA_BADGE.pagado).label }}
                            </span>
                            <span v-else class="text-xs" style="color:var(--text-secondary);">Sin matrícula</span>
                        </td>

                        <!-- Plan carrera -->
                        <td class="px-4 py-3 text-center">
                            <div v-if="e.tiene_carrera" class="flex flex-col items-center gap-0.5">
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                      :style="(CARRERA_BADGE[e.carrera_estado] ?? CARRERA_BADGE.parcial).style">
                                    {{ (CARRERA_BADGE[e.carrera_estado] ?? CARRERA_BADGE.parcial).label }}
                                </span>
                                <span class="text-[10px] capitalize" style="color:var(--text-secondary);">{{ e.carrera_forma }}</span>
                            </div>
                            <span v-else class="text-xs" style="color:var(--text-secondary);">Sin plan</span>
                        </td>

                        <td class="px-4 py-3 text-right">
                            <Link :href="route('secretaria.pagos.show', e.id_usuario)"
                                class="text-sm font-medium transition-opacity hover:opacity-70"
                                style="color:var(--primary-color);">
                                Gestionar →
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </AdminLayout>
</template>
