<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { formatFecha } from '@/Composables/useFecha';

const props = defineProps({
    cronogramas: Array,
    carreras: Array,
    filtros: Object,
});

// ── Filtros ───────────────────────────────────────────────────────────────────
const buscar = ref(props.filtros?.buscar ?? '');
const faseFiltro = ref(props.filtros?.fase ?? 'todas');

let buscarTimeout = null;
watch(buscar, () => {
    clearTimeout(buscarTimeout);
    buscarTimeout = setTimeout(aplicarFiltros, 400);
});
watch(faseFiltro, aplicarFiltros);

function aplicarFiltros() {
    router.get(route('secretaria.cronogramas.index'), {
        buscar: buscar.value || undefined,
        fase: faseFiltro.value || undefined,
    }, { preserveState: true, replace: true });
}

// ── Modal y Formulario ────────────────────────────────────────────────────────
const mostrarModal = ref(false);

const form = useForm({
    id_cronograma: null,
    nombre: '',
    fecha_inicio: '',
    fecha_fin: '',
});

function abrirModalEditar(cronograma) {
    form.id_cronograma = cronograma.id_cronograma;
    form.nombre = cronograma.nombre;
    form.fecha_inicio = cronograma.fecha_inicio;
    form.fecha_fin = cronograma.fecha_fin;
    form.clearErrors();
    mostrarModal.value = true;
}

function cerrarModal() {
    mostrarModal.value = false;
    form.reset();
}

function guardar() {
    form.put(route('secretaria.cronogramas.update', form.id_cronograma), {
        onSuccess: () => {
            cerrarModal();
        }
    });
}

function toggleActivo(id) {
    if (confirm('¿Estás seguro de cambiar el estado de este cronograma?')) {
        router.patch(route('secretaria.cronogramas.toggle-activo', id));
    }
}
</script>

<template>
    <Head title="Gestión de Cronogramas" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--text-color);">Cronogramas Académicos</h2>
        </template>

        <div class="max-w-7xl mx-auto">
            <!-- Filtros -->
            <div class="bg-[var(--card-bg)] p-4 rounded-sm border border-[var(--border-color)] mb-6 flex flex-col sm:flex-row gap-4 items-center">
                <input
                    v-model="buscar"
                    type="text"
                    placeholder="Buscar cronograma..."
                    class="w-full sm:w-1/3 bg-transparent border border-[var(--border-color)] rounded-sm px-4 py-2 text-[var(--text-color)] focus:outline-none focus:border-[var(--primary-color)] font-light"
                >
                <select
                    v-model="faseFiltro"
                    class="w-full sm:w-48 bg-transparent border border-[var(--border-color)] rounded-sm px-4 py-2 text-[var(--text-color)] focus:outline-none focus:border-[var(--primary-color)] font-light appearance-none"
                >
                    <option value="todas" class="bg-[var(--bg-color)] text-[var(--text-color)]">Todas las fases</option>
                    <option value="abierta" class="bg-[var(--bg-color)] text-[var(--text-color)]">Abiertas</option>
                    <option value="proxima" class="bg-[var(--bg-color)] text-[var(--text-color)]">Próximas</option>
                    <option value="cerrada" class="bg-[var(--bg-color)] text-[var(--text-color)]">Cerradas</option>
                    <option value="inactiva" class="bg-[var(--bg-color)] text-[var(--text-color)]">Inactivos</option>
                </select>
            </div>

            <!-- Tabla -->
            <div class="bg-[var(--card-bg)] rounded-sm border border-[var(--border-color)] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--border-color)]">
                        <thead>
                            <tr class="bg-[var(--bg-color)]/50">
                                <th class="px-6 py-4 text-left text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Fechas</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Alcance</th>
                                <th class="px-6 py-4 text-center text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Fase / Estado</th>
                                <th class="px-6 py-4 text-right text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--border-color)]">
                            <tr v-for="c in cronogramas" :key="c.id_cronograma" class="hover:bg-[var(--bg-color)]/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-[var(--text-color)]">{{ c.nombre }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-[var(--text-muted)] capitalize">{{ c.tipo_periodo }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1 text-sm text-[var(--text-color)]">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[var(--text-muted)] text-xs uppercase tracking-wider w-12">Inicio:</span>
                                            <span class="font-medium">{{ formatFecha(c.fecha_inicio) }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[var(--text-muted)] text-xs uppercase tracking-wider w-12">Fin:</span>
                                            <span class="font-medium">{{ formatFecha(c.fecha_fin) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-[var(--bg-color)] text-[var(--text-muted)] border border-[var(--border-color)]">
                                        {{ c.alcance }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span v-if="c.estado === 'ABIERTA'" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                        Abierta
                                    </span>
                                    <span v-else-if="c.estado === 'PRÓXIMA'" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                        Próxima
                                    </span>
                                    <span v-else-if="c.estado === 'CERRADA'" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-rose-500/10 text-rose-500 border border-rose-500/20">
                                        Cerrada
                                    </span>
                                    <span v-else class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-500/10 text-slate-500 border border-slate-500/20">
                                        Inactivo
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="toggleActivo(c.id_cronograma)"
                                                class="px-3 py-1.5 text-xs font-medium uppercase tracking-wider border border-[var(--border-color)] rounded-sm text-[var(--text-secondary)] hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all duration-200">
                                            {{ c.activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                        <button @click="abrirModalEditar(c)"
                                                class="px-3 py-1.5 text-xs font-medium uppercase tracking-wider border border-[var(--border-color)] rounded-sm text-[var(--text-color)] hover:bg-[var(--text-color)] hover:border-[var(--text-color)] hover:text-[var(--bg-color)] transition-all duration-200">
                                            Editar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="cronogramas.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-[var(--text-muted)] font-light">
                                    No se encontraron cronogramas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de Editar Cronograma -->
        <Teleport to="body">
            <div v-if="mostrarModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-[var(--card-bg)] w-full max-w-md rounded-sm border border-[var(--border-color)] shadow-2xl">
                    <div class="px-6 py-4 border-b border-[var(--border-color)] flex justify-between items-center">
                        <h3 class="text-lg font-medium text-[var(--text-color)]">Editar Cronograma</h3>
                        <button @click="cerrarModal" class="text-[var(--text-muted)] hover:text-[var(--text-color)] transition-colors">&times;</button>
                    </div>

                    <form @submit.prevent="guardar" class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-light text-[var(--text-muted)] mb-1">Nombre</label>
                            <input v-model="form.nombre" type="text" class="w-full bg-transparent border border-[var(--border-color)] rounded-sm px-3 py-2 text-[var(--text-color)] focus:outline-none focus:border-[var(--primary-color)] font-light" required>
                            <div v-if="form.errors.nombre" class="text-rose-500 text-xs mt-1">{{ form.errors.nombre }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-light text-[var(--text-muted)] mb-1">Fecha Inicio</label>
                                <input v-model="form.fecha_inicio" type="date" class="w-full bg-[var(--bg-color)] border border-[var(--border-color)] rounded-sm px-3 py-2 text-[var(--text-color)] focus:outline-none focus:border-[var(--primary-color)] font-light" required>
                                <div v-if="form.errors.fecha_inicio" class="text-rose-500 text-xs mt-1">{{ form.errors.fecha_inicio }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-light text-[var(--text-muted)] mb-1">Fecha Fin</label>
                                <input v-model="form.fecha_fin" type="date" class="w-full bg-[var(--bg-color)] border border-[var(--border-color)] rounded-sm px-3 py-2 text-[var(--text-color)] focus:outline-none focus:border-[var(--primary-color)] font-light" required>
                                <div v-if="form.errors.fecha_fin" class="text-rose-500 text-xs mt-1">{{ form.errors.fecha_fin }}</div>
                            </div>
                        </div>

                        <div class="pt-4 flex justify-end gap-3">
                            <button type="button" @click="cerrarModal" class="px-4 py-2 text-sm font-medium text-[var(--text-muted)] hover:text-[var(--text-color)] transition-colors">Cancelar</button>
                            <button type="submit" :disabled="form.processing" class="bg-[var(--primary-color)] text-[var(--primary-text)] px-5 py-2 rounded-sm text-sm font-medium hover:opacity-90 transition-opacity disabled:opacity-50">
                                Guardar Cronograma
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
