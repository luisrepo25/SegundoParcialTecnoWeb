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
        router.get(route('propietario.seguimiento.index'),
            { buscar: buscar.value || undefined },
            { preserveState: true, replace: true }
        );
    }, 400);
});
</script>

<template>
    <Head title="Seguimiento Académico" />

    <AdminLayout>
        <template #header>
            <h2 class="text-base font-semibold" style="color: var(--text-color);">Seguimiento Académico</h2>
        </template>

        <!-- Volver -->
        <div class="mb-6">
            <Link :href="route('dashboard.propietario')"
                class="inline-flex items-center gap-1.5 text-sm font-medium transition-opacity hover:opacity-70"
                style="color: var(--text-secondary);">
                ← Volver al Dashboard
            </Link>
        </div>

        <!-- Barra de búsqueda -->
        <div class="mb-6 flex items-center gap-3">
            <input v-model="buscar"
                type="text"
                placeholder="Buscar por nombre, apellido, DNI o email…"
                class="w-full max-w-md rounded-lg px-4 py-2.5 text-sm focus:outline-none"
                style="background-color: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color);" />
            <span class="text-xs" style="color: var(--text-secondary);">
                {{ estudiantes.length }} estudiante{{ estudiantes.length !== 1 ? 's' : '' }}
            </span>
        </div>

        <!-- Tabla -->
        <div class="rounded-xl overflow-hidden" style="border: 1px solid var(--border-color);">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background-color: color-mix(in srgb, var(--card-bg) 80%, var(--border-color));">
                        <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">Estudiante</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">Legajo</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">DNI</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">Carrera</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">Estado</th>
                        <th class="text-right px-4 py-3 text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!estudiantes.length">
                        <td colspan="6" class="text-center py-10 text-sm" style="color: var(--text-secondary);">
                            No se encontraron estudiantes
                        </td>
                    </tr>
                    <tr v-for="e in estudiantes" :key="e.id_usuario"
                        class="border-t transition-colors"
                        style="border-color: var(--border-color);"
                        onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--text-color) 3%, transparent)'"
                        onmouseout="this.style.backgroundColor='transparent'">

                        <td class="px-4 py-3">
                            <div class="font-medium" style="color: var(--text-color);">{{ e.apellido }}, {{ e.nombre }}</div>
                            <div class="text-xs" style="color: var(--text-secondary);">{{ e.email }}</div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs" style="color: var(--text-color);">
                            {{ e.legajo ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs" style="color: var(--text-color);">
                            {{ e.dni ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs" style="color: var(--text-secondary);">
                            {{ e.carrera?.nombre ?? 'Sin carrera asignada' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                  :style="e.activo
                                    ? 'background-color: rgba(52,211,153,0.15); color: #34d399;'
                                    : 'background-color: rgba(248,113,113,0.15); color: #f87171;'">
                                {{ e.activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('propietario.seguimiento.show', e.id_usuario)"
                                class="text-sm font-medium transition-opacity hover:opacity-70"
                                style="color: var(--primary-color);">
                                Ver historial →
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </AdminLayout>
</template>
