<script setup>
import DirectorLayout from '@/Layouts/DirectorLayout.vue';
import ComboSelect from '@/Components/ComboSelect.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    materias:          Object,
    todasLasMaterias:  Array,
    filtros:           Object,
});

// ── Filtros ───────────────────────────────────────────────────────────────────
const buscar       = ref(props.filtros?.buscar ?? '');
const activoFiltro = ref(props.filtros?.activo ?? 'todos');

let timeout = null;
watch(buscar, () => { clearTimeout(timeout); timeout = setTimeout(aplicarFiltros, 400); });
watch(activoFiltro, aplicarFiltros);

function aplicarFiltros() {
    router.get(route('director.materias.index'), {
        buscar: buscar.value || undefined,
        activo: activoFiltro.value !== 'todos' ? activoFiltro.value : undefined,
    }, { preserveState: true, replace: true });
}

// ── Modal ─────────────────────────────────────────────────────────────────────
const showModal   = ref(false);
const modoEdicion = ref(false);
const editandoId  = ref(null);

const form = useForm({
    codigo: '', nombre: '', duracion_meses: 1,
    costo_mensual: '', creditos: '', id_materia_requisito: '',
});

function abrirCrear() {
    form.reset(); form.clearErrors();
    modoEdicion.value = false;
    editandoId.value  = null;
    showModal.value   = true;
}

function abrirEditar(m) {
    form.reset(); form.clearErrors();
    form.codigo               = m.codigo;
    form.nombre               = m.nombre;
    form.duracion_meses       = m.duracion_meses;
    form.costo_mensual        = m.costo_mensual;
    form.creditos             = m.creditos ?? '';
    form.id_materia_requisito = m.id_materia_requisito ?? '';
    modoEdicion.value = true;
    editandoId.value  = m.id_materia;
    showModal.value   = true;
}

function cerrar() { showModal.value = false; form.reset(); form.clearErrors(); }

function guardar() {
    if (modoEdicion.value) {
        form.put(route('director.materias.update', editandoId.value), { onSuccess: cerrar });
    } else {
        form.post(route('director.materias.store'), { onSuccess: cerrar });
    }
}

function toggleActivo(m) {
    const accion = m.activo ? 'desactivar' : 'activar';
    if (!confirm(`¿Desea ${accion} la materia "${m.nombre}"?`)) return;
    router.patch(route('director.materias.toggle-activo', m.id_materia));
}

// Opciones de requisito para ComboSelect (excluye la materia que se está editando)
const opcionesRequisito = computed(() =>
    (props.todasLasMaterias ?? [])
        .filter(m => m.id_materia !== editandoId.value)
        .map(m => ({ value: m.id_materia, label: `${m.codigo} — ${m.nombre}` }))
);

function formatCosto(val) {
    if (!val) return '—';
    return 'Bs ' + parseFloat(val).toLocaleString('es-BO', { minimumFractionDigits: 2 });
}
</script>

<template>
    <Head title="Gestión de Materias" />

    <DirectorLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--text-color);">
                Gestión de Materias
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <!-- Flash -->
                <div v-if="$page.props.flash?.success" class="mb-4 rounded-lg p-4 text-sm font-medium"
                     style="background-color: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3);">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error" class="mb-4 rounded-lg p-4 text-sm font-medium"
                     style="background-color: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3);">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Barra superior -->
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex gap-2 flex-1 flex-wrap">
                        <input v-model="buscar" type="text" placeholder="Buscar por nombre o código..."
                            class="flex-1 min-w-[200px] rounded-lg px-3 py-2 text-sm focus:outline-none"
                            style="background-color: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color);" />
                        <select v-model="activoFiltro"
                            class="rounded-lg px-3 py-2 text-sm focus:outline-none"
                            style="background-color: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color);">
                            <option value="todos">Todas</option>
                            <option value="1">Activas</option>
                            <option value="0">Inactivas</option>
                        </select>
                    </div>
                    <button @click="abrirCrear"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition"
                        style="background-color: var(--primary-color); color: var(--primary-text);">
                        + Nueva Materia
                    </button>
                </div>

                <!-- Tabla -->
                <div class="overflow-hidden rounded-xl shadow" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <table class="min-w-full">
                        <thead>
                            <tr style="background-color: var(--bg-color);">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Código</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Materia</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider hidden md:table-cell" style="color: var(--text-secondary);">Duración</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider hidden md:table-cell" style="color: var(--text-secondary);">Costo/mes</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider hidden lg:table-cell" style="color: var(--text-secondary);">Requisito</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Estado</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in materias.data" :key="m.id_materia"
                                class="border-t" style="border-color: var(--border-color);">
                                <td class="px-4 py-3">
                                    <span class="font-mono text-sm font-semibold" style="color: var(--primary-color);">{{ m.codigo }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-sm" style="color: var(--text-color);">{{ m.nombre }}</div>
                                    <div v-if="m.creditos" class="text-xs mt-0.5" style="color: var(--text-secondary);">{{ m.creditos }} créditos</div>
                                </td>
                                <td class="px-4 py-3 text-sm hidden md:table-cell" style="color: var(--text-color);">
                                    {{ m.duracion_meses }} <span class="text-xs" style="color: var(--text-secondary);">mes(es)</span>
                                </td>
                                <td class="px-4 py-3 text-sm hidden md:table-cell" style="color: var(--text-color);">
                                    {{ formatCosto(m.costo_mensual) }}
                                </td>
                                <td class="px-4 py-3 text-sm hidden lg:table-cell" style="color: var(--text-secondary);">
                                    {{ m.requisito ? m.requisito.nombre : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['badge', m.activo ? 'badge-activo' : 'badge-inactivo']">
                                        {{ m.activo ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-1">
                                        <button @click="abrirEditar(m)" class="btn-accion" style="color: #d97706;">Editar</button>
                                        <button @click="toggleActivo(m)" class="btn-accion"
                                            :style="m.activo ? 'color: #dc2626;' : 'color: #059669;'">
                                            {{ m.activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="materias.data.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-sm" style="color: var(--text-secondary);">
                                    No se encontraron materias.
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Paginación -->
                    <div v-if="materias.last_page > 1" class="flex items-center justify-between gap-2 px-4 py-3 border-t"
                         style="border-color: var(--border-color);">
                        <p class="text-sm" style="color: var(--text-secondary);">
                            Mostrando {{ materias.from }}–{{ materias.to }} de {{ materias.total }} materias
                        </p>
                        <div class="flex gap-1">
                            <template v-for="link in materias.links" :key="link.label">
                                <button v-if="link.url" @click="router.get(link.url)"
                                    class="px-3 py-1 rounded text-sm border transition"
                                    :style="link.active
                                        ? 'background-color: var(--primary-color); color: var(--primary-text); border-color: var(--primary-color);'
                                        : 'background-color: var(--card-bg); color: var(--text-color); border-color: var(--border-color);'"
                                    v-html="link.label" />
                                <span v-else class="px-3 py-1 rounded text-sm border"
                                    style="color: var(--text-secondary); border-color: var(--border-color); opacity: 0.5;"
                                    v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Modal ──────────────────────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="cerrar">
                <div class="modal-box">
                    <div class="modal-header">
                        <h3 class="modal-title">{{ modoEdicion ? 'Editar Materia' : 'Nueva Materia' }}</h3>
                        <button @click="cerrar" class="modal-close">&times;</button>
                    </div>

                    <form @submit.prevent="guardar" class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="field-label">Código *</label>
                                <input v-model="form.codigo" type="text" class="field-input" placeholder="Ej: MAT-001" />
                                <p v-if="form.errors.codigo" class="field-error">{{ form.errors.codigo }}</p>
                            </div>
                            <div>
                                <label class="field-label">Créditos</label>
                                <input v-model="form.creditos" type="number" min="0" class="field-input" placeholder="Ej: 4" />
                                <p v-if="form.errors.creditos" class="field-error">{{ form.errors.creditos }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="field-label">Nombre *</label>
                            <input v-model="form.nombre" type="text" class="field-input" placeholder="Ej: Matemáticas I" />
                            <p v-if="form.errors.nombre" class="field-error">{{ form.errors.nombre }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="field-label">Duración (meses) *</label>
                                <input v-model="form.duracion_meses" type="number" min="1" class="field-input" placeholder="Ej: 3" />
                                <p v-if="form.errors.duracion_meses" class="field-error">{{ form.errors.duracion_meses }}</p>
                            </div>
                            <div>
                                <label class="field-label">Costo mensual (Bs) *</label>
                                <input v-model="form.costo_mensual" type="number" min="0" step="0.01" class="field-input" placeholder="Ej: 500.00" />
                                <p v-if="form.errors.costo_mensual" class="field-error">{{ form.errors.costo_mensual }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="field-label">Materia requisito</label>
                            <ComboSelect
                                v-model="form.id_materia_requisito"
                                :options="opcionesRequisito"
                                placeholder="Sin requisito"
                                empty-label="Sin requisito"
                            />
                            <p v-if="form.errors.id_materia_requisito" class="field-error">{{ form.errors.id_materia_requisito }}</p>
                        </div>

                        <div class="modal-footer border-t pt-4" style="border-color: var(--border-color);">
                            <button type="button" @click="cerrar" class="btn-secondary">Cancelar</button>
                            <button type="submit" :disabled="form.processing"
                                class="rounded-lg px-4 py-2 text-sm font-medium transition disabled:opacity-50"
                                style="background-color: var(--primary-color); color: var(--primary-text);">
                                {{ form.processing ? 'Guardando...' : (modoEdicion ? 'Actualizar' : 'Registrar Materia') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </DirectorLayout>
</template>

<style scoped>
.modal-overlay {
    position: fixed; inset: 0; z-index: 50;
    display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,0.55); padding: 1rem;
}
.modal-box {
    width: 100%; max-width: 32rem; border-radius: 1rem; overflow: hidden;
    background-color: var(--card-bg); border: 1px solid var(--border-color);
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4);
}
.modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color);
}
.modal-title  { font-size: 1rem; font-weight: 600; color: var(--text-color); }
.modal-close  { font-size: 1.5rem; line-height: 1; color: var(--text-secondary); background: none; border: none; cursor: pointer; }
.modal-close:hover { color: var(--text-color); }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; }

.field-label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; color: var(--text-color); }
.field-input {
    width: 100%; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem;
    background-color: var(--bg-color); color: var(--text-color);
    border: 1px solid var(--border-color); outline: none;
}
.field-input:focus { border-color: var(--primary-color); }
.field-error  { margin-top: 0.25rem; font-size: 0.75rem; color: #ef4444; }

.btn-secondary {
    border-radius: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem;
    background: none; cursor: pointer; transition: opacity 0.15s;
    color: var(--text-color); border: 1px solid var(--border-color);
}
.btn-accion {
    padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500;
    background: none; border: none; cursor: pointer; transition: opacity 0.15s;
}
.btn-accion:hover { opacity: 0.7; }

.badge { display: inline-flex; border-radius: 9999px; padding: 0.125rem 0.625rem; font-size: 0.75rem; font-weight: 600; }
.badge-activo   { background: rgba(16,185,129,0.2); color: #34d399; }
.badge-inactivo { background: rgba(239,68,68,0.2);  color: #f87171; }
</style>
