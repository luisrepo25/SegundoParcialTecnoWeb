<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    carreras:      Array,
    nivelesSelect: Array,
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const errors = computed(() => page.props.errors ?? {});

const TIPO_LABELS = {
    mensual:    'Mensual',
    semestral:  'Semestral',
    anual:      'Anual',
    intensivo:  'Intensivo',
};

const TIPO_COLORS = {
    mensual:   '#3b82f6',
    semestral: '#8b5cf6',
    anual:     '#10b981',
    intensivo: '#f59e0b',
};

const CARRERA_TIPOS = {
    tecnico:          'Técnico',
    tecnico_superior: 'Técnico Superior',
    curso_libre:      'Curso Libre',
};

// ── Niveles agrupados por carrera para el selector del form ──────────────────
const nivelesAgrupados = computed(() => {
    const map = {};
    for (const n of (props.nivelesSelect ?? [])) {
        if (!map[n.id_carrera]) {
            map[n.id_carrera] = { id_carrera: n.id_carrera, carrera: n.carrera_nombre, niveles: [] };
        }
        map[n.id_carrera].niveles.push(n);
    }
    return Object.values(map);
});

const carreraFiltro = ref(null);

const nivelesParaModal = computed(() => {
    if (carreraFiltro.value !== null) {
        return nivelesAgrupados.value.filter(g => g.id_carrera === carreraFiltro.value);
    }
    return nivelesAgrupados.value;
});

// ── Modal agregar / editar ───────────────────────────────────────────────────
const modalOpen     = ref(false);
const editando      = ref(null);
const esCursoLibre  = ref(false);
const nombreCarreraModal = ref('');

const form = useForm({
    id_nivel:       '',
    id_carrera:     '',
    es_curso_libre: false,
    nombre:         '',
    tipo_periodo:   'semestral',
    fecha_inicio:   '',
    fecha_fin:      '',
    max_materias:   5,
});

function abrirAgregar(nivelId = '', carreraId = null, cursoLibre = false, nombreCarrera = '') {
    editando.value          = null;
    esCursoLibre.value      = cursoLibre;
    carreraFiltro.value     = carreraId;
    nombreCarreraModal.value = nombreCarrera;
    form.reset();
    form.id_nivel       = nivelId;
    form.id_carrera     = carreraId ?? '';
    form.es_curso_libre = cursoLibre;
    form.tipo_periodo   = 'semestral';
    form.max_materias   = 5;
    form.clearErrors();
    modalOpen.value = true;
}

function abrirEditar(p) {
    editando.value       = p;
    esCursoLibre.value   = false;
    form.id_nivel        = p.id_nivel ?? '';
    form.id_carrera      = '';
    form.es_curso_libre  = false;
    form.nombre          = p.nombre;
    form.tipo_periodo    = p.tipo_periodo;
    form.fecha_inicio    = p.fecha_inicio ?? '';
    form.fecha_fin       = p.fecha_fin ?? '';
    form.max_materias    = p.max_materias ?? 5;
    form.clearErrors();
    modalOpen.value = true;
}

function cerrar() {
    modalOpen.value = false;
    form.reset();
    editando.value     = null;
    esCursoLibre.value = false;
}

function guardar() {
    if (editando.value) {
        form.put(route('director.periodos.update', editando.value.id_periodo), {
            preserveScroll: true,
            onSuccess: cerrar,
        });
    } else {
        form.post(route('director.periodos.store'), {
            preserveScroll: true,
            onSuccess: cerrar,
        });
    }
}

// ── Toggle activo ────────────────────────────────────────────────────────────
function toggleActivo(p) {
    router.patch(route('director.periodos.toggle', p.id_periodo), {}, {
        preserveScroll: true,
    });
}

// ── Confirmar eliminar ────────────────────────────────────────────────────────
const confirmEliminar = ref(null);

function confirmarEliminar() {
    router.delete(route('director.periodos.destroy', confirmEliminar.value.id_periodo), {
        preserveScroll: true,
        onSuccess: () => { confirmEliminar.value = null; },
    });
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function fmtFecha(f) {
    if (!f) return '–';
    const [y, m, d] = f.split('-');
    return `${d}/${m}/${y}`;
}

function duracionDias(inicio, fin) {
    if (!inicio || !fin) return '';
    const d = Math.round((new Date(fin) - new Date(inicio)) / 86400000);
    if (d <= 0) return '';
    return `${d} días`;
}
</script>

<template>
    <Head title="Períodos Académicos" />

    <AdminLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight" style="color: var(--text-color);">Períodos Académicos</h2>
                <p class="text-xs mt-0.5" style="color: var(--text-secondary);">
                    Define los períodos de dictado por nivel de cada carrera.
                </p>
            </div>
        </template>

        <!-- Flash -->
        <div v-if="flash.success" class="mb-4 rounded-lg px-4 py-3 text-sm font-medium"
             style="background-color: color-mix(in srgb,#10b981 15%,transparent); color:#10b981; border:1px solid color-mix(in srgb,#10b981 30%,transparent);">
            {{ flash.success }}
        </div>
        <div v-if="errors.periodo" class="mb-4 rounded-lg px-4 py-3 text-sm font-medium"
             style="background-color: color-mix(in srgb,#ef4444 15%,transparent); color:#ef4444; border:1px solid color-mix(in srgb,#ef4444 30%,transparent);">
            {{ errors.periodo }}
        </div>

        <div class="flex justify-end mb-4">
            <button @click="abrirAgregar()"
                class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold transition"
                style="background-color: var(--primary-color); color: var(--primary-text);">
                + Nuevo Período
            </button>
        </div>

        <div class="space-y-6">

            <div v-if="carreras.length === 0"
                 class="rounded-xl p-12 text-center"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <p class="text-3xl mb-3">📅</p>
                <p class="font-semibold text-sm" style="color: var(--text-color);">Sin carreras registradas</p>
            </div>

            <!-- Card por carrera -->
            <div v-for="carrera in carreras" :key="carrera.id_carrera"
                 class="rounded-xl overflow-hidden"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">

                <!-- Cabecera -->
                <div class="px-5 py-3 flex items-center justify-between"
                     style="background-color: var(--bg-color); border-bottom: 1px solid var(--border-color);">
                    <div>
                        <span class="font-semibold text-sm" style="color: var(--text-color);">{{ carrera.nombre }}</span>
                        <span class="ml-2 text-xs" style="color: var(--text-muted);">
                            {{ CARRERA_TIPOS[carrera.tipo] ?? carrera.tipo }}
                        </span>
                    </div>
                    <!-- Botón + para curso libre (solo si aún no tiene período) -->
                    <button v-if="carrera.tipo === 'curso_libre' && carrera.periodos_directos.length === 0"
                        @click="abrirAgregar('', carrera.id_carrera, true, carrera.nombre)"
                        class="text-xs font-semibold px-2.5 py-1 rounded-md transition"
                        style="background-color: color-mix(in srgb, var(--primary-color) 12%, transparent); color: var(--primary-color);">
                        + Período
                    </button>
                    <span v-else-if="carrera.tipo === 'curso_libre'" class="text-xs font-medium" style="color: #10b981;">
                        Período único definido ✓
                    </span>
                    <span v-else class="text-xs" style="color: var(--text-secondary);">
                        {{ carrera.niveles.length }} nivel(es)
                    </span>
                </div>

                <!-- ── CURSO LIBRE: período directo ──────────────────────────── -->
                <div v-if="carrera.tipo === 'curso_libre'" class="px-5 py-4">
                    <p v-if="carrera.periodos_directos.length === 0"
                       class="text-sm italic" style="color: var(--text-muted);">
                        Sin período — agrega el período único de este curso.
                    </p>
                    <div v-else class="space-y-2">
                        <div v-for="p in carrera.periodos_directos" :key="p.id_periodo"
                             class="flex items-center gap-3 rounded-lg px-3 py-2.5"
                             style="background-color: var(--bg-color); border: 1px solid var(--border-color);">
                            <span class="shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded"
                                  :style="`background-color: color-mix(in srgb, ${TIPO_COLORS[p.tipo_periodo] ?? '#6b7280'} 15%, transparent); color: ${TIPO_COLORS[p.tipo_periodo] ?? '#6b7280'};`">
                                {{ TIPO_LABELS[p.tipo_periodo] ?? p.tipo_periodo }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-medium truncate" style="color: var(--text-color);">{{ p.nombre }}</p>
                                    <span v-if="!p.activo" class="shrink-0 text-[10px] font-semibold px-1.5 py-0.5 rounded"
                                          style="background-color:color-mix(in srgb,#ef4444 12%,transparent); color:#ef4444;">Inactivo</span>
                                </div>
                                <p class="text-xs mt-0.5" style="color: var(--text-secondary);">
                                    {{ fmtFecha(p.fecha_inicio) }} → {{ fmtFecha(p.fecha_fin) }}
                                    <span v-if="duracionDias(p.fecha_inicio, p.fecha_fin)" class="ml-1 opacity-60">({{ duracionDias(p.fecha_inicio, p.fecha_fin) }})</span>
                                    <span v-if="p.max_materias" class="ml-2 font-semibold" style="color: var(--primary-color);">· {{ p.max_materias }} materias</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button @click="toggleActivo(p)" class="text-[11px] font-medium"
                                        :style="p.activo ? 'color:#f59e0b;' : 'color:#10b981;'">
                                    {{ p.activo ? 'Desactivar' : 'Activar' }}
                                </button>
                                <span style="color: var(--border-color);">|</span>
                                <button @click="abrirEditar(p)" class="text-[11px] font-medium transition"
                                        style="color: var(--text-secondary);"
                                        onmouseover="this.style.color='var(--primary-color)'"
                                        onmouseout="this.style.color='var(--text-secondary)'">Editar</button>
                                <span style="color: var(--border-color);">|</span>
                                <button @click="confirmEliminar = p" class="text-[11px] font-medium" style="color:#ef4444;">Eliminar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── CARRERA NORMAL: niveles ───────────────────────────────── -->
                <div v-else class="divide-y" style="border-color: var(--border-color);">
                    <div v-for="nivel in carrera.niveles" :key="nivel.id_nivel" class="px-5 py-4">

                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                                     style="background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);">
                                    {{ nivel.numero_nivel }}
                                </div>
                                <span class="text-sm font-medium" style="color: var(--text-color);">
                                    {{ nivel.nombre_nivel ?? `Año ${nivel.numero_nivel}` }}
                                </span>
                                <span class="text-xs" style="color: var(--text-muted);">· {{ nivel.periodos.length }} período(s)</span>
                            </div>
                            <button @click="abrirAgregar(nivel.id_nivel, carrera.id_carrera)"
                                class="text-xs font-semibold px-2.5 py-1 rounded-md transition"
                                style="background-color: color-mix(in srgb, var(--primary-color) 12%, transparent); color: var(--primary-color);">
                                + Período
                            </button>
                        </div>

                        <p v-if="nivel.periodos.length === 0" class="text-xs italic pl-8" style="color: var(--text-muted);">
                            Sin períodos — agrega el primer semestre de este nivel.
                        </p>

                        <div v-else class="space-y-2 pl-8">
                            <div v-for="p in nivel.periodos" :key="p.id_periodo"
                                 class="flex items-center gap-3 rounded-lg px-3 py-2.5"
                                 style="background-color: var(--bg-color); border: 1px solid var(--border-color);">
                                <span class="shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded"
                                      :style="`background-color: color-mix(in srgb, ${TIPO_COLORS[p.tipo_periodo] ?? '#6b7280'} 15%, transparent); color: ${TIPO_COLORS[p.tipo_periodo] ?? '#6b7280'};`">
                                    {{ TIPO_LABELS[p.tipo_periodo] ?? p.tipo_periodo }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium truncate" style="color: var(--text-color);">{{ p.nombre }}</p>
                                        <span v-if="!p.activo" class="shrink-0 text-[10px] font-semibold px-1.5 py-0.5 rounded"
                                              style="background-color:color-mix(in srgb,#ef4444 12%,transparent); color:#ef4444;">Inactivo</span>
                                    </div>
                                    <p class="text-xs mt-0.5" style="color: var(--text-secondary);">
                                        {{ fmtFecha(p.fecha_inicio) }} → {{ fmtFecha(p.fecha_fin) }}
                                        <span v-if="duracionDias(p.fecha_inicio, p.fecha_fin)" class="ml-1 opacity-60">({{ duracionDias(p.fecha_inicio, p.fecha_fin) }})</span>
                                        <span v-if="p.max_materias" class="ml-2 font-semibold" style="color: var(--primary-color);">· {{ p.max_materias }} materias</span>
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button @click="toggleActivo(p)" class="text-[11px] font-medium"
                                            :style="p.activo ? 'color:#f59e0b;' : 'color:#10b981;'">
                                        {{ p.activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                    <span style="color: var(--border-color);">|</span>
                                    <button @click="abrirEditar(p)" class="text-[11px] font-medium transition"
                                            style="color: var(--text-secondary);"
                                            onmouseover="this.style.color='var(--primary-color)'"
                                            onmouseout="this.style.color='var(--text-secondary)'">Editar</button>
                                    <span style="color: var(--border-color);">|</span>
                                    <button @click="confirmEliminar = p" class="text-[11px] font-medium" style="color:#ef4444;">Eliminar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </AdminLayout>

    <!-- ── Modal Agregar / Editar ──────────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="modalOpen"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: rgba(0,0,0,0.5);">
            <div class="w-full max-w-md rounded-2xl shadow-2xl p-6"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">

                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-base font-semibold" style="color: var(--text-color);">
                            {{ editando ? 'Editar Período' : 'Nuevo Período' }}
                        </h3>
                        <p v-if="esCursoLibre && nombreCarreraModal" class="text-xs mt-0.5" style="color: var(--text-secondary);">
                            {{ nombreCarreraModal }}
                        </p>
                    </div>
                    <button @click="cerrar" class="text-lg leading-none" style="color: var(--text-secondary);">✕</button>
                </div>

                <div class="space-y-4">

                    <!-- Nivel selector (solo carreras normales al crear) -->
                    <div v-if="!editando && !esCursoLibre">
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Nivel / Año *</label>
                        <select v-model="form.id_nivel" class="input-field">
                            <option value="">— Selecciona nivel —</option>
                            <template v-for="grupo in nivelesParaModal" :key="grupo.id_carrera">
                                <optgroup :label="grupo.carrera">
                                    <option v-for="n in grupo.niveles" :key="n.id_nivel" :value="n.id_nivel">
                                        {{ n.nombre ?? `Año ${n.numero_nivel}` }}
                                    </option>
                                </optgroup>
                            </template>
                        </select>
                        <p v-if="form.errors.id_nivel" class="text-xs mt-1" style="color:#ef4444;">{{ form.errors.id_nivel }}</p>
                    </div>

                    <!-- Nombre -->
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Nombre *</label>
                        <input v-model="form.nombre" type="text"
                            :placeholder="esCursoLibre ? 'Ej: Período Único 2026' : 'Ej: Semestre I 2026'"
                            class="input-field" maxlength="50" />
                        <p v-if="form.errors.nombre" class="text-xs mt-1" style="color:#ef4444;">{{ form.errors.nombre }}</p>
                    </div>

                    <!-- Tipo período -->
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Tipo de período *</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button v-for="(label, key) in TIPO_LABELS" :key="key"
                                type="button"
                                @click="form.tipo_periodo = key"
                                class="px-3 py-2 rounded-lg text-sm font-medium border transition"
                                :style="form.tipo_periodo === key
                                    ? `background-color: color-mix(in srgb,${TIPO_COLORS[key]} 20%,transparent); color:${TIPO_COLORS[key]}; border-color:${TIPO_COLORS[key]};`
                                    : 'background:transparent; color:var(--text-secondary); border-color:var(--border-color);'">
                                {{ label }}
                            </button>
                        </div>
                        <p v-if="form.errors.tipo_periodo" class="text-xs mt-1" style="color:#ef4444;">{{ form.errors.tipo_periodo }}</p>
                    </div>

                    <!-- Fechas -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Fecha inicio *</label>
                            <input v-model="form.fecha_inicio" type="date" class="input-field" />
                            <p v-if="form.errors.fecha_inicio" class="text-xs mt-1" style="color:#ef4444;">{{ form.errors.fecha_inicio }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Fecha fin *</label>
                            <input v-model="form.fecha_fin" type="date" class="input-field" :min="form.fecha_inicio || ''" />
                            <p v-if="form.errors.fecha_fin" class="text-xs mt-1" style="color:#ef4444;">{{ form.errors.fecha_fin }}</p>
                        </div>
                    </div>

                    <p v-if="form.fecha_inicio && form.fecha_fin && duracionDias(form.fecha_inicio, form.fecha_fin)"
                       class="text-xs" style="color: var(--text-secondary);">
                        Duración: <strong style="color: var(--text-color);">{{ duracionDias(form.fecha_inicio, form.fecha_fin) }}</strong>
                    </p>

                    <!-- Máx. materias -->
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">
                            Máx. materias por período *
                        </label>
                        <input v-model.number="form.max_materias" type="number" min="1" max="30" class="input-field" />
                        <p v-if="form.errors.max_materias" class="text-xs mt-1" style="color:#ef4444;">{{ form.errors.max_materias }}</p>
                    </div>

                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button @click="cerrar" class="btn-secondary">Cancelar</button>
                    <button @click="guardar" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Guardando…' : (editando ? 'Actualizar' : 'Crear Período') }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- ── Confirm Eliminar ──────────────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="confirmEliminar"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: rgba(0,0,0,0.5);">
            <div class="w-full max-w-sm rounded-2xl shadow-2xl p-6 text-center"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <p class="text-2xl mb-3">🗑️</p>
                <p class="font-semibold mb-1" style="color: var(--text-color);">¿Eliminar período?</p>
                <p class="text-sm font-medium mb-4" style="color: var(--primary-color);">{{ confirmEliminar.nombre }}</p>
                <div class="flex justify-center gap-3">
                    <button @click="confirmEliminar = null" class="btn-secondary">Cancelar</button>
                    <button @click="confirmarEliminar" class="btn-danger">Sí, eliminar</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
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
}

.btn-danger {
    border-radius: 0.5rem;
    padding: 0.5rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    background-color: #ef4444;
    color: #fff;
}
</style>
