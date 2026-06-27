<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ComboSelect from '@/Components/ComboSelect.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    carrera:             Object,
    porNivel:            Array,
    materiasDisponibles: Array,
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

// ── Modal: Agregar Nivel ──────────────────────────────────────────────────────
const modalNivel = ref(false);
const formNivel = useForm({ nombre: '', descripcion: '' });

function abrirModalNivel() {
    formNivel.reset();
    modalNivel.value = true;
}

function guardarNivel() {
    formNivel.post(route('director.malla.nivel.store', props.carrera.id_carrera), {
        preserveScroll: true,
        onSuccess: () => { modalNivel.value = false; },
    });
}

// ── Modal: Asignar/Crear Materia ──────────────────────────────────────────────
const modalMateria    = ref(false);
const nivelActivo     = ref(null);   // nivel objeto completo
const tabMateria      = ref('existente'); // 'existente' | 'nueva'

const formAsignar = useForm({ id_materia: '', obligatoria: true });
const formNueva   = useForm({
    codigo:               '',
    nombre:               '',
    duracion_meses:       1,
    costo_mensual:        '',
    creditos:             '',
    id_materia_requisito: '',
    obligatoria:          true,
    es_base:              true,
});

// Materias ya en el nivel activo (para filtrar el dropdown)
const materiasEnNivel = computed(() => {
    if (!nivelActivo.value) return new Set();
    return new Set(nivelActivo.value.materias.map(m => m.id_materia));
});

// Materias disponibles filtradas (excluye las ya en el nivel) → formato ComboSelect
const materiasParaAsignar = computed(() =>
    props.materiasDisponibles
        .filter(m => !materiasEnNivel.value.has(m.id_materia))
        .map(m => ({ value: m.id_materia, label: `${m.codigo} — ${m.nombre}` }))
);

// Todas las materias → formato ComboSelect (para selector de prerequisito)
const opcionesPrerequisito = computed(() =>
    props.materiasDisponibles.map(m => ({ value: m.id_materia, label: `${m.codigo} — ${m.nombre}` }))
);

// Última materia del nivel (para sugerir como prerequisito)
const ultimaMateriaDelNivel = computed(() => {
    if (!nivelActivo.value || !nivelActivo.value.materias.length) return null;
    const sorted = [...nivelActivo.value.materias].sort(
        (a, b) => (a.orden_en_nivel ?? 999) - (b.orden_en_nivel ?? 999)
    );
    return sorted[sorted.length - 1];
});

function abrirModalMateria(nivel) {
    nivelActivo.value = nivel;
    tabMateria.value  = 'existente';
    formAsignar.reset();
    formNueva.reset();

    // Smart prerequisito: si el nivel ya tiene materias, pre-selecciona la última
    if (ultimaMateriaDelNivel.value) {
        formNueva.es_base              = false;
        formNueva.id_materia_requisito = ultimaMateriaDelNivel.value.id_materia;
    } else {
        formNueva.es_base              = true;
        formNueva.id_materia_requisito = '';
    }
    formNueva.duracion_meses = 1;
    formNueva.obligatoria    = true;
    formAsignar.obligatoria  = true;

    modalMateria.value = true;
}

function guardarAsignar() {
    formAsignar.transform(data => ({
        ...data,
        id_carrera: props.carrera.id_carrera,
        id_nivel:   nivelActivo.value.id_nivel,
    })).post(route('director.malla.store'), {
        preserveScroll: true,
        onSuccess: () => { modalMateria.value = false; },
    });
}

function guardarNueva() {
    const payload = {
        ...formNueva.data(),
        id_nivel: nivelActivo.value.id_nivel,
    };
    if (formNueva.es_base) payload.id_materia_requisito = null;

    formNueva.transform(() => payload).post(
        route('director.malla.materia.store', props.carrera.id_carrera),
        {
            preserveScroll: true,
            onSuccess: () => { modalMateria.value = false; },
        }
    );
}

// Cuando cambia es_base en el form nueva
function onEsBaseChange() {
    if (formNueva.es_base) {
        formNueva.id_materia_requisito = '';
    } else {
        formNueva.id_materia_requisito = ultimaMateriaDelNivel.value?.id_materia ?? '';
    }
}

// ── Quitar materia de la malla ────────────────────────────────────────────────
const confirmMalla = ref(null); // id_malla a eliminar

function quitarMateria(idMalla) {
    confirmMalla.value = idMalla;
}

function confirmarQuitarMateria() {
    router.delete(route('director.malla.destroy', confirmMalla.value), {
        preserveScroll: true,
        onSuccess: () => { confirmMalla.value = null; },
    });
}

// ── Eliminar nivel ────────────────────────────────────────────────────────────
const confirmNivel = ref(null); // id_nivel a eliminar

function eliminarNivel(idNivel) {
    confirmNivel.value = idNivel;
}

function confirmarEliminarNivel() {
    router.delete(route('director.malla.nivel.destroy', confirmNivel.value), {
        preserveScroll: true,
        onSuccess: () => { confirmNivel.value = null; },
    });
}
</script>

<template>
    <Head :title="`Malla — ${carrera.nombre}`" />

    <AdminLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight" style="color: var(--text-color);">
                    {{ carrera.nombre }}
                </h2>
                <p class="text-xs mt-0.5" style="color: var(--text-secondary);">
                    {{ TIPOS[carrera.tipo] ?? carrera.tipo }}
                    · {{ carrera.duracion_niveles }} nivel(es)
                    · {{ formatCosto(carrera.costo_carrera_completa) }}
                </p>
                <Link :href="route('director.carreras.index')"
                    class="inline-flex items-center gap-1 text-xs font-medium mt-2 hover:underline"
                    style="color: var(--primary-color);">
                    ← Volver a todas las carreras
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Toolbar superior -->
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium" style="color: var(--text-secondary);">
                        {{ porNivel.length }} nivel(es) configurado(s)
                    </p>
                    <button @click="abrirModalNivel"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition"
                        style="background-color: var(--primary-color); color: var(--primary-text);">
                        + Agregar Nivel
                    </button>
                </div>

                <!-- Sin niveles -->
                <div v-if="porNivel.length === 0"
                     class="rounded-xl p-10 text-center"
                     style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <p class="text-3xl mb-3">📋</p>
                    <p class="font-semibold text-sm" style="color: var(--text-color);">Sin niveles configurados</p>
                    <p class="text-sm mt-1" style="color: var(--text-secondary);">
                        Agrega el primer nivel para comenzar a construir la malla curricular.
                    </p>
                    <button @click="abrirModalNivel"
                        class="inline-block mt-5 rounded-lg px-5 py-2.5 text-sm font-semibold"
                        style="background-color: var(--primary-color); color: var(--primary-text);">
                        + Agregar Primer Nivel
                    </button>
                </div>

                <!-- Niveles -->
                <div v-for="nivel in porNivel" :key="nivel.id_nivel"
                     class="rounded-xl overflow-hidden"
                     style="background-color: var(--card-bg); border: 1px solid var(--border-color);">

                    <!-- Cabecera nivel -->
                    <div class="flex items-center justify-between px-5 py-3"
                         style="background-color: var(--bg-color); border-bottom: 1px solid var(--border-color);">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold flex-shrink-0"
                                 style="background-color: var(--primary-color); color: var(--primary-text);">
                                {{ nivel.numero_nivel }}
                            </div>
                            <div>
                                <p class="font-semibold text-sm" style="color: var(--text-color);">{{ nivel.nombre_nivel }}</p>
                                <p class="text-xs" style="color: var(--text-secondary);">{{ nivel.materias.length }} materia(s)</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="abrirModalMateria(nivel)"
                                class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                style="background-color: var(--primary-color); color: var(--primary-text);">
                                + Asignar Materia
                            </button>
                            <button v-if="nivel.materias.length === 0"
                                @click="eliminarNivel(nivel.id_nivel)"
                                class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium border transition"
                                style="color: #ef4444; border-color: #ef4444; background: transparent;">
                                Eliminar
                            </button>
                        </div>
                    </div>

                    <!-- Sin materias en nivel -->
                    <div v-if="nivel.materias.length === 0" class="px-5 py-6 text-center">
                        <p class="text-sm" style="color: var(--text-secondary);">
                            Nivel vacío. Asigna materias del catálogo o crea una nueva.
                        </p>
                    </div>

                    <!-- Tabla materias del nivel -->
                    <table v-else class="min-w-full">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color: var(--text-secondary);">Ord.</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color: var(--text-secondary);">Código</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color: var(--text-secondary);">Materia</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider hidden md:table-cell whitespace-nowrap" style="color: var(--text-secondary);">Duración</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider hidden md:table-cell whitespace-nowrap" style="color: var(--text-secondary);">Costo/mes</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color: var(--text-secondary);">Tipo</th>
                                <th class="px-4 py-2 whitespace-nowrap"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in nivel.materias" :key="m.id_malla"
                                class="border-t" style="border-color: var(--border-color);">
                                <td class="px-4 py-3 text-sm" style="color: var(--text-secondary);">
                                    {{ m.orden_en_nivel ?? '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-mono text-sm font-semibold" style="color: var(--primary-color);">{{ m.codigo }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-sm" style="color: var(--text-color);">{{ m.nombre }}</div>
                                    <div v-if="m.creditos" class="text-xs mt-0.5" style="color: var(--text-secondary);">{{ m.creditos }} créditos</div>
                                </td>
                                <td class="px-4 py-3 text-sm hidden md:table-cell whitespace-nowrap" style="color: var(--text-color);">
                                    {{ m.duracion_meses }} mes(es)
                                </td>
                                <td class="px-4 py-3 text-sm hidden md:table-cell whitespace-nowrap" style="color: var(--text-color);">
                                    {{ formatCosto(m.costo_mensual) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['badge', m.obligatoria ? 'badge-obligatoria' : 'badge-electiva']">
                                        {{ m.obligatoria ? 'Obligatoria' : 'Electiva' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <button @click="quitarMateria(m.id_malla)"
                                        class="text-xs font-medium hover:underline"
                                        style="color: #ef4444;">
                                        Quitar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Botón ir a gestionar materias -->
                <div v-if="porNivel.length > 0" class="flex justify-end">
                    <Link :href="route('director.materias.index')"
                        class="rounded-lg px-4 py-2 text-sm font-medium border transition"
                        style="color: var(--primary-color); border-color: var(--primary-color); background: transparent;">
                        Gestionar catálogo de materias →
                    </Link>
                </div>

            </div>
        </div>
    </AdminLayout>

    <!-- ── Modal: Agregar Nivel ──────────────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="modalNivel"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: rgba(0,0,0,0.5);">
            <div class="w-full max-w-md rounded-2xl shadow-2xl p-6"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-semibold" style="color: var(--text-color);">Agregar Nivel</h3>
                    <button @click="modalNivel = false" class="text-lg leading-none" style="color: var(--text-secondary);">✕</button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Nombre del nivel</label>
                        <input v-model="formNivel.nombre" type="text"
                            :placeholder="`Ej: Nivel ${(porNivel.length + 1)} – Fundamentos`"
                            class="input-field" />
                        <p v-if="formNivel.errors.nombre" class="text-xs mt-1" style="color:#ef4444;">{{ formNivel.errors.nombre }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Descripción <span class="opacity-50">(opcional)</span></label>
                        <textarea v-model="formNivel.descripcion" rows="2" class="input-field resize-none"
                            placeholder="Descripción breve del nivel..."></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button @click="modalNivel = false" class="btn-secondary">Cancelar</button>
                    <button @click="guardarNivel" :disabled="formNivel.processing" class="btn-primary">
                        {{ formNivel.processing ? 'Guardando...' : 'Agregar Nivel' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- ── Modal: Asignar / Crear Materia ────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="modalMateria && nivelActivo"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: rgba(0,0,0,0.5);">
            <div class="w-full max-w-lg rounded-2xl shadow-2xl"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color); max-height: 90vh; overflow-y: auto;">
                <div class="flex items-center justify-between p-6 pb-4">
                    <div>
                        <h3 class="text-base font-semibold" style="color: var(--text-color);">Agregar Materia</h3>
                        <p class="text-xs mt-0.5" style="color: var(--text-secondary);">
                            {{ nivelActivo.nombre_nivel }} · {{ carrera.nombre }}
                        </p>
                    </div>
                    <button @click="modalMateria = false" class="text-lg leading-none" style="color: var(--text-secondary);">✕</button>
                </div>

                <!-- Tabs -->
                <div class="flex border-b mx-6" style="border-color: var(--border-color);">
                    <button @click="tabMateria = 'existente'"
                        :class="['tab-btn', tabMateria === 'existente' ? 'tab-active' : '']">
                        Usar del catálogo
                    </button>
                    <button @click="tabMateria = 'nueva'"
                        :class="['tab-btn', tabMateria === 'nueva' ? 'tab-active' : '']">
                        Crear nueva materia
                    </button>
                </div>

                <div class="p-6 pt-5">

                    <!-- TAB: Existente -->
                    <div v-if="tabMateria === 'existente'" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Materia *</label>
                            <ComboSelect
                                v-model="formAsignar.id_materia"
                                :options="materiasParaAsignar"
                                placeholder="— Selecciona una materia —"
                            />
                            <p v-if="!materiasParaAsignar.length" class="text-xs mt-1" style="color: var(--text-secondary);">
                                Todas las materias ya están asignadas a este nivel.
                            </p>
                            <p v-if="formAsignar.errors.materia" class="text-xs mt-1" style="color:#ef4444;">{{ formAsignar.errors.materia }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="oblig-asignar" v-model="formAsignar.obligatoria" class="w-4 h-4" />
                            <label for="oblig-asignar" class="text-sm" style="color: var(--text-color);">Obligatoria</label>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button @click="modalMateria = false" class="btn-secondary">Cancelar</button>
                            <button @click="guardarAsignar"
                                :disabled="!formAsignar.id_materia || formAsignar.processing"
                                class="btn-primary">
                                {{ formAsignar.processing ? 'Asignando...' : 'Asignar a Nivel' }}
                            </button>
                        </div>
                    </div>

                    <!-- TAB: Nueva -->
                    <div v-if="tabMateria === 'nueva'" class="space-y-4">

                        <!-- Tipo: base o con prerequisito -->
                        <div class="rounded-lg p-3" style="background-color: var(--bg-color); border: 1px solid var(--border-color);">
                            <p class="text-xs font-semibold mb-2" style="color: var(--text-secondary);">PREREQUISITO</p>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" :value="true" v-model="formNueva.es_base" @change="onEsBaseChange" class="w-4 h-4" />
                                    <span class="text-sm" style="color: var(--text-color);">Materia base <span class="opacity-50 text-xs">(sin prerequisito)</span></span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" :value="false" v-model="formNueva.es_base" @change="onEsBaseChange" class="w-4 h-4" />
                                    <span class="text-sm" style="color: var(--text-color);">Requiere prerequisito</span>
                                </label>
                            </div>
                            <!-- Selector prerequisito -->
                            <div v-if="!formNueva.es_base" class="mt-3">
                                <ComboSelect
                                    v-model="formNueva.id_materia_requisito"
                                    :options="opcionesPrerequisito"
                                    placeholder="— Sin prerequisito —"
                                    empty-label="— Sin prerequisito —"
                                />
                                <p v-if="ultimaMateriaDelNivel" class="text-xs mt-1" style="color: var(--primary-color);">
                                    💡 Sugerido: {{ ultimaMateriaDelNivel.codigo }} (última del nivel)
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Código *</label>
                                <input v-model="formNueva.codigo" type="text" placeholder="Ej: MAT-101" class="input-field" />
                                <p v-if="formNueva.errors.codigo" class="text-xs mt-1" style="color:#ef4444;">{{ formNueva.errors.codigo }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Créditos</label>
                                <input v-model="formNueva.creditos" type="number" min="0" placeholder="Ej: 4" class="input-field" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Nombre *</label>
                            <input v-model="formNueva.nombre" type="text" placeholder="Ej: Matemáticas I" class="input-field" />
                            <p v-if="formNueva.errors.nombre" class="text-xs mt-1" style="color:#ef4444;">{{ formNueva.errors.nombre }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Duración (meses) *</label>
                                <input v-model="formNueva.duracion_meses" type="number" min="1" class="input-field" />
                                <p v-if="formNueva.errors.duracion_meses" class="text-xs mt-1" style="color:#ef4444;">{{ formNueva.errors.duracion_meses }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Costo mensual (Bs) *</label>
                                <input v-model="formNueva.costo_mensual" type="number" min="0" step="0.01" placeholder="Ej: 500.00" class="input-field" />
                                <p v-if="formNueva.errors.costo_mensual" class="text-xs mt-1" style="color:#ef4444;">{{ formNueva.errors.costo_mensual }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="oblig-nueva" v-model="formNueva.obligatoria" class="w-4 h-4" />
                            <label for="oblig-nueva" class="text-sm" style="color: var(--text-color);">Obligatoria en la malla</label>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button @click="modalMateria = false" class="btn-secondary">Cancelar</button>
                            <button @click="guardarNueva" :disabled="formNueva.processing" class="btn-primary">
                                {{ formNueva.processing ? 'Creando...' : 'Crear y Asignar' }}
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </Teleport>

    <!-- ── Confirm: Quitar materia ────────────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="confirmMalla !== null"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: rgba(0,0,0,0.5);">
            <div class="w-full max-w-sm rounded-2xl shadow-2xl p-6 text-center"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <p class="text-2xl mb-3">⚠️</p>
                <p class="font-semibold mb-1" style="color: var(--text-color);">¿Quitar materia de la malla?</p>
                <p class="text-sm mb-5" style="color: var(--text-secondary);">La materia seguirá en el catálogo, solo se desvincula de este nivel.</p>
                <div class="flex justify-center gap-3">
                    <button @click="confirmMalla = null" class="btn-secondary">Cancelar</button>
                    <button @click="confirmarQuitarMateria" class="btn-danger">Sí, quitar</button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- ── Confirm: Eliminar nivel ────────────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="confirmNivel !== null"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: rgba(0,0,0,0.5);">
            <div class="w-full max-w-sm rounded-2xl shadow-2xl p-6 text-center"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <p class="text-2xl mb-3">🗑️</p>
                <p class="font-semibold mb-1" style="color: var(--text-color);">¿Eliminar este nivel?</p>
                <p class="text-sm mb-5" style="color: var(--text-secondary);">Esta acción no se puede deshacer.</p>
                <div class="flex justify-center gap-3">
                    <button @click="confirmNivel = null" class="btn-secondary">Cancelar</button>
                    <button @click="confirmarEliminarNivel" class="btn-danger">Sí, eliminar</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.badge { display: inline-flex; border-radius: 9999px; padding: 0.125rem 0.625rem; font-size: 0.75rem; font-weight: 600; }
.badge-obligatoria { background: rgba(59,130,246,0.2);  color: #60a5fa; }
.badge-electiva    { background: rgba(245,158,11,0.2);  color: #fbbf24; }

.input-field {
    width: 100%;
    border-radius: 0.5rem;
    border: 1px solid var(--border-color);
    background-color: var(--bg-color);
    color: var(--text-color);
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    outline: none;
    transition: border-color 0.15s;
}
.input-field:focus { border-color: var(--primary-color); }

.btn-primary {
    border-radius: 0.5rem;
    padding: 0.5rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    background-color: var(--primary-color);
    color: var(--primary-text);
    transition: opacity 0.15s;
}
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-secondary {
    border-radius: 0.5rem;
    padding: 0.5rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid var(--border-color);
    background: transparent;
    color: var(--text-secondary);
    transition: border-color 0.15s;
}

.btn-danger {
    border-radius: 0.5rem;
    padding: 0.5rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    background-color: #ef4444;
    color: #fff;
}

.tab-btn {
    padding: 0.5rem 1rem;
    font-size: 0.8125rem;
    font-weight: 500;
    border-bottom: 2px solid transparent;
    color: var(--text-secondary);
    transition: all 0.15s;
    margin-bottom: -1px;
}
.tab-active {
    border-bottom-color: var(--primary-color);
    color: var(--primary-color);
    font-weight: 600;
}
</style>
