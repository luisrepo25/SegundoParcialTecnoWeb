<script setup>
import SecretariaLayout from '@/Layouts/SecretariaLayout.vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    cronogramas: Object,
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
    nombre: '',
    tipo_periodo: 'inscripcion',
    fecha_inicio: '',
    fecha_fin: '',
    id_carrera: '',
});

function abrirModal() {
    form.reset();
    form.clearErrors();
    mostrarModal.value = true;
}

function cerrarModal() {
    mostrarModal.value = false;
}

function guardar() {
    form.post(route('secretaria.cronogramas.store'), {
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

    <SecretariaLayout>
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Header de la página -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-light text-[var(--text-color)] tracking-tight">Cronogramas de Inscripción</h2>
                    <p class="text-[var(--text-muted)] mt-1 font-light">Gestiona las ventanas de tiempo del instituto.</p>
                </div>
                <button @click="abrirModal" class="bg-[var(--primary-color)] text-[var(--primary-text)] px-5 py-2.5 rounded-sm font-medium hover:opacity-90 transition-opacity flex items-center shadow-sm">
                    <span>+ Nuevo Cronograma</span>
                </button>
            </div>

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
                            <tr v-for="c in cronogramas.data" :key="c.id_cronograma" class="hover:bg-[var(--bg-color)]/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-[var(--text-color)]">{{ c.nombre }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-[var(--text-muted)] capitalize">{{ c.tipo_periodo }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-[var(--text-color)]">{{ c.fecha_inicio }} a {{ c.fecha_fin }}</div>
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
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="toggleActivo(c.id_cronograma)" :class="c.activo ? 'text-rose-500 hover:text-rose-400' : 'text-emerald-500 hover:text-emerald-400'" class="transition-colors font-light">
                                        {{ c.activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="cronogramas.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-[var(--text-muted)] font-light">
                                    No se encontraron cronogramas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Paginación -->
            <div class="mt-4 flex justify-between items-center" v-if="cronogramas.data.length > 0">
                <div class="text-sm text-[var(--text-muted)] font-light">
                    Mostrando {{ cronogramas.from }} a {{ cronogramas.to }} de {{ cronogramas.total }} resultados
                </div>
                <div class="flex gap-2">
                    <template v-for="(link, p) in cronogramas.links" :key="p">
                        <component 
                            :is="link.url ? 'Link' : 'span'"
                            :href="link.url"
                            class="px-3 py-1 text-sm rounded-sm border font-light"
                            :class="[
                                link.active ? 'bg-[var(--primary-color)] text-[var(--primary-text)] border-[var(--primary-color)]' : 'border-[var(--border-color)] text-[var(--text-color)]',
                                !link.url ? 'opacity-50 cursor-not-allowed' : 'hover:border-[var(--primary-color)] transition-colors'
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- Modal de Nuevo Cronograma -->
        <div v-if="mostrarModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-[var(--card-bg)] w-full max-w-md rounded-sm border border-[var(--border-color)] shadow-2xl">
                <div class="px-6 py-4 border-b border-[var(--border-color)] flex justify-between items-center">
                    <h3 class="text-lg font-medium text-[var(--text-color)]">Nuevo Cronograma</h3>
                    <button @click="cerrarModal" class="text-[var(--text-muted)] hover:text-[var(--text-color)] transition-colors">&times;</button>
                </div>
                
                <form @submit.prevent="guardar" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-light text-[var(--text-muted)] mb-1">Nombre</label>
                        <input v-model="form.nombre" type="text" class="w-full bg-transparent border border-[var(--border-color)] rounded-sm px-3 py-2 text-[var(--text-color)] focus:outline-none focus:border-[var(--primary-color)] font-light" required>
                        <div v-if="form.errors.nombre" class="text-rose-500 text-xs mt-1">{{ form.errors.nombre }}</div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-light text-[var(--text-muted)] mb-1">Tipo de Evento</label>
                        <select v-model="form.tipo_periodo" class="w-full bg-[var(--bg-color)] border border-[var(--border-color)] rounded-sm px-3 py-2 text-[var(--text-color)] focus:outline-none focus:border-[var(--primary-color)] font-light appearance-none" required>
                            <option value="inscripcion">Inscripciones</option>
                            <option value="clases">Clases Regulares</option>
                            <option value="examenes">Exámenes</option>
                            <option value="receso">Receso Académico</option>
                        </select>
                        <div v-if="form.errors.tipo_periodo" class="text-rose-500 text-xs mt-1">{{ form.errors.tipo_periodo }}</div>
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
                    
                    <div>
                        <label class="block text-sm font-light text-[var(--text-muted)] mb-1">Alcance (Opcional)</label>
                        <select v-model="form.id_carrera" class="w-full bg-[var(--bg-color)] border border-[var(--border-color)] rounded-sm px-3 py-2 text-[var(--text-color)] focus:outline-none focus:border-[var(--primary-color)] font-light appearance-none">
                            <option value="">Global (Todas las carreras)</option>
                            <option v-for="carrera in carreras" :key="carrera.id_carrera" :value="carrera.id_carrera">
                                {{ carrera.nombre }}
                            </option>
                        </select>
                        <div v-if="form.errors.id_carrera" class="text-rose-500 text-xs mt-1">{{ form.errors.id_carrera }}</div>
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
    </SecretariaLayout>
</template>
