<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ComboSelect from '@/Components/ComboSelect.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    carreras:               Array,
    carrerasSelect:         { type: Array, default: () => [] },
    plantillas:             { type: Array, default: () => [] },
    cronogramasClases:      { type: Array, default: () => [] },
    cronogramasInscripcion: { type: Array, default: () => [] },
});

const page = usePage();
const flash  = computed(() => page.props.flash  ?? {});
const errors = computed(() => page.props.errors ?? {});

const TIPO_LABELS = {
    mensual:   'Mensual',
    semestral: 'Semestral',
    anual:     'Anual',
    intensivo: 'Intensivo',
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

// ── Opciones para selects del modal individual ────────────────────────────────
const optsCarreras = computed(() =>
    props.carrerasSelect.map(c => ({
        value: c.id_carrera,
        label: c.nombre + (c.tipo === 'curso_libre' ? ' (Libre)' : ''),
    }))
);

// ── Modal agregar / editar ────────────────────────────────────────────────────
const modalOpen  = ref(false);
const editando   = ref(null);
const cronogramaModal = ref(null);
const cronogramaInscripcionModal = ref(null);

const form = useForm({
    id_carrera:               '',
    nombre:                   '',
    tipo_periodo:             'semestral',
    fecha_inicio:             '',
    fecha_fin:                '',
    fecha_inicio_inscripcion: '',
    fecha_fin_inscripcion:    '',
});

function abrirAgregar(carreraId = '') {
    editando.value     = null;
    form.reset();
    form.id_carrera    = carreraId;
    form.tipo_periodo  = 'semestral';
    cronogramaModal.value = null;
    form.clearErrors();
    modalOpen.value = true;
}

function abrirEditar(p) {
    editando.value                = p;
    form.id_carrera               = '';
    form.nombre                   = p.nombre;
    form.tipo_periodo             = p.tipo_periodo;
    form.fecha_inicio             = p.fecha_inicio ?? '';
    form.fecha_fin                = p.fecha_fin ?? '';
    form.fecha_inicio_inscripcion = p.fecha_inicio_inscripcion ?? '';
    form.fecha_fin_inscripcion    = p.fecha_fin_inscripcion ?? '';
    form.clearErrors();

    // Pre-seleccionar cronograma de clases cuyas fechas coincidan con el período
    const matchClases = p.fecha_inicio && p.fecha_fin
        ? props.cronogramasClases.find(c =>
            c.fecha_inicio === p.fecha_inicio && c.fecha_fin === p.fecha_fin)
        : null;
    cronogramaModal.value = matchClases ? matchClases.id_cronograma : null;

    // Pre-seleccionar cronograma de inscripción cuyas fechas coincidan
    const matchInsc = p.fecha_inicio_inscripcion && p.fecha_fin_inscripcion
        ? props.cronogramasInscripcion.find(c =>
            c.fecha_inicio === p.fecha_inicio_inscripcion && c.fecha_fin === p.fecha_fin_inscripcion)
        : null;
    cronogramaInscripcionModal.value = matchInsc ? matchInsc.id_cronograma : null;

    modalOpen.value = true;
}

function cerrar() {
    modalOpen.value = false;
    cronogramaModal.value = null;
    cronogramaInscripcionModal.value = null;
    form.reset();
    editando.value = null;
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

// ── Toggle activo ─────────────────────────────────────────────────────────────
function toggleActivo(p) {
    router.patch(route('director.periodos.toggle', p.id_periodo), {}, { preserveScroll: true });
}

// ── Confirmar eliminar ────────────────────────────────────────────────────────
const confirmEliminar   = ref(null);
const eliminandoPeriodo = ref(false);

function confirmarEliminar() {
    if (eliminandoPeriodo.value) return;
    eliminandoPeriodo.value = true;
    router.delete(route('director.periodos.destroy', confirmEliminar.value.id_periodo), {
        preserveScroll: true,
        onFinish: () => { confirmEliminar.value = null; eliminandoPeriodo.value = false; },
    });
}

// ── Modal Lote ────────────────────────────────────────────────────────────────
const cronogramaLote = ref(null);
const cronogramaInscripcionLote = ref(null);
const modalLote  = ref(false);
const formLote   = useForm({
    nombre:                   '',
    fecha_inicio:             '',
    fecha_fin:                '',
    fecha_inicio_inscripcion: '',
    fecha_fin_inscripcion:    '',
    id_carreras:              [],
    carreras:                 [],
});

const tipoPorCarrera   = ref({});
const fechasPorCarrera = ref({});

const plantillaSeleccionada = ref('');
const optsPlantillas = computed(() =>
    props.plantillas.map((p, i) => ({ value: i, label: p.label }))
);

function aplicarPlantilla(idx) {
    if (idx === '' || idx === null || idx === undefined) return;
    const p = props.plantillas[idx];
    if (!p) return;
    formLote.nombre = p.nombre;
    for (const key of Object.keys(tipoPorCarrera.value)) {
        tipoPorCarrera.value[key] = p.tipo_periodo;
    }
    formLote.id_carreras = [...(p.id_carreras ?? [])];
}

const todosIds = computed(() => props.carrerasSelect.map(c => c.id_carrera));
const todosSeleccionados = computed(() =>
    todosIds.value.length > 0 && todosIds.value.every(id => formLote.id_carreras.includes(id))
);

function toggleTodos() {
    formLote.id_carreras = todosSeleccionados.value ? [] : [...todosIds.value];
}

function toggleCarreraLote(id) {
    const idx = formLote.id_carreras.indexOf(id);
    if (idx === -1) formLote.id_carreras.push(id);
    else formLote.id_carreras.splice(idx, 1);
}

// Mapea modalidad de carrera al tipo_periodo más probable
function modalidadATipo(modalidad) {
    const map = { anual: 'anual', semestral: 'semestral', mensual: 'mensual' };
    return map[modalidad] ?? 'semestral';
}

function abrirLote() {
    formLote.reset();
    plantillaSeleccionada.value = '';
    cronogramaLote.value = null;
    cronogramaInscripcionLote.value = null;
    const tipos  = {};
    const fechas = {};
    for (const c of props.carrerasSelect) {
        tipos[c.id_carrera]  = modalidadATipo(c.modalidad);
        fechas[c.id_carrera] = { fecha_inicio: '', fecha_fin: '' };
    }
    tipoPorCarrera.value   = tipos;
    fechasPorCarrera.value = fechas;
    modalLote.value = true;
}

function guardarLote() {
    const carreras = [];
    for (const c of props.carrerasSelect) {
        if (formLote.id_carreras.includes(c.id_carrera)) {
            const tipo = tipoPorCarrera.value[c.id_carrera] ?? 'semestral';
            const fcs  = fechasPorCarrera.value[c.id_carrera] ?? {};
            carreras.push({
                id_carrera:   c.id_carrera,
                tipo_periodo: tipo,
                fecha_inicio: fcs.fecha_inicio || null,
                fecha_fin:    fcs.fecha_fin    || null,
            });
        }
    }
    formLote.carreras = carreras;
    formLote.post(route('director.periodos.lote'), {
        onSuccess: () => { modalLote.value = false; formLote.reset(); },
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmtFecha(f) {
    if (!f) return '–';
    const [y, m, d] = f.split('-');
    return `${d}/${m}/${y}`;
}

function duracionDias(inicio, fin) {
    if (!inicio || !fin) return '';
    const d = Math.round((new Date(fin) - new Date(inicio)) / 86400000);
    return d > 0 ? `${d} días` : '';
}

// ── Cronograma → auto-rellena fechas ─────────────────────────────────────────
const MODALIDAD_LABELS = { mensual: 'Mensual', semestral: 'Semestral', anual: 'Anual', intensivo: 'Intensivo' };

// Año del período siendo editado/creado — guía el filtro de cronogramas mostrados
const anioFormulario = computed(() => {
    if (form.fecha_inicio) return new Date(form.fecha_inicio + 'T12:00:00').getFullYear();
    return new Date().getFullYear();
});

// Cronogramas de clases del mismo año del período, filtrados por modalidad
function cronogramasFiltrados(modalidadFiltro = null) {
    return props.cronogramasClases.filter(c => {
        const anio = new Date(c.fecha_inicio + 'T12:00:00').getFullYear();
        if (anio !== anioFormulario.value) return false;
        return !modalidadFiltro || c.modalidad === null || c.modalidad === modalidadFiltro;
    });
}

// Cronogramas de inscripción del mismo año del período
const cronogramasInscripcionFiltrados = computed(() =>
    props.cronogramasInscripcion.filter(c => {
        const anio = new Date(c.fecha_inicio + 'T12:00:00').getFullYear();
        return anio === anioFormulario.value;
    })
);

function labelCronograma(c) {
    const mod = c.modalidad ? MODALIDAD_LABELS[c.modalidad] ?? c.modalidad : 'Global';
    return `${c.nombre}  (${fmtFecha(c.fecha_inicio)} → ${fmtFecha(c.fecha_fin)})  · ${mod}`;
}

function aplicarCronograma(idCronograma, targetForm) {
    if (!idCronograma) return;
    const c = props.cronogramasClases.find(x => String(x.id_cronograma) === String(idCronograma));
    if (!c) return;
    targetForm.fecha_inicio = c.fecha_inicio;
    targetForm.fecha_fin    = c.fecha_fin;
}

function aplicarCronogramaInscripcion(idCronograma, targetForm) {
    if (!idCronograma) return;
    const c = props.cronogramasInscripcion.find(x => String(x.id_cronograma) === String(idCronograma));
    if (!c) return;
    targetForm.fecha_inicio_inscripcion = c.fecha_inicio;
    targetForm.fecha_fin_inscripcion    = c.fecha_fin;
}

// ── Clonar año siguiente ──────────────────────────────────────────────────────
const confirmClonar = ref(false);
const anoSiguiente  = new Date().getFullYear() + 1;
const clonando      = ref(false);

function clonarSiguienteAnio() {
    clonando.value = true;
    router.post(route('director.periodos.siguiente-anio'), {}, {
        preserveScroll: true,
        onSuccess: () => { confirmClonar.value = false; clonando.value = false; },
        onError:   () => { confirmClonar.value = false; clonando.value = false; },
    });
}

// ── Accordion carreras ────────────────────────────────────────────────────────
const carreraAbierta = ref(null);
const buscarCarrera  = ref('');
const soloAnoActual  = ref(true);
const anoActual      = new Date().getFullYear();

const toggleCarreraPanel = (id) => {
    carreraAbierta.value = carreraAbierta.value === id ? null : id;
};

const carrerasFiltradas = computed(() => {
    const q = buscarCarrera.value.trim().toLowerCase();
    let lista = props.carreras;
    if (q) lista = lista.filter(c => c.nombre.toLowerCase().includes(q));

    if (!soloAnoActual.value) return lista;

    return lista.map(c => ({
        ...c,
        periodos: (c.periodos ?? []).filter(p =>
            p.fecha_inicio && new Date(p.fecha_inicio).getFullYear() === anoActual
        ),
    }));
});
</script>

<template>
    <Head title="Períodos Académicos" />

    <AdminLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight" style="color: var(--text-color);">Períodos Académicos</h2>
                <p class="text-xs mt-0.5" style="color: var(--text-secondary);">
                    Define los períodos de dictado por carrera (un período por semestre/año).
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

        <div class="flex justify-end gap-2 mb-4">
            <button @click="confirmClonar = true"
                class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold transition border"
                style="border-color: #8b5cf6; color: #8b5cf6; background: transparent;">
                📅 Clonar {{ anoSiguiente }}
            </button>
            <button @click="abrirLote()"
                class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold transition border"
                style="border-color: var(--primary-color); color: var(--primary-color); background: transparent;">
                ⚡ Crear en Lote
            </button>
            <button @click="abrirAgregar()"
                class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold transition"
                style="background-color: var(--primary-color); color: var(--primary-text);">
                + Nuevo Período
            </button>
        </div>

        <!-- Buscador + filtro año -->
        <div class="flex items-center gap-3 mb-3 flex-wrap">
            <input v-model="buscarCarrera" type="text" placeholder="Buscar carrera..."
                class="rounded-lg border px-3 py-2 text-sm outline-none"
                style="width: 220px; background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);" />

            <label class="flex items-center gap-2 cursor-pointer select-none">
                <div class="relative" @click="soloAnoActual = !soloAnoActual">
                    <div class="w-9 h-5 rounded-full transition-colors duration-200"
                         :style="soloAnoActual ? 'background-color: var(--primary-color);' : 'background-color: var(--border-color);'"></div>
                    <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"
                         :style="soloAnoActual ? 'transform: translateX(16px);' : ''"></div>
                </div>
                <span class="text-xs font-medium" style="color: var(--text-secondary);">Solo vigentes</span>
            </label>
        </div>

        <div class="space-y-2">

            <div v-if="carrerasFiltradas.length === 0"
                 class="rounded-xl p-12 text-center"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <p class="text-3xl mb-3">📅</p>
                <p class="font-semibold text-sm" style="color: var(--text-color);">Sin carreras registradas</p>
            </div>

            <!-- Card por carrera (accordion) -->
            <div v-for="carrera in carrerasFiltradas" :key="carrera.id_carrera"
                 class="rounded-xl overflow-hidden"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">

                <!-- Cabecera -->
                <div class="px-5 py-3 flex items-center justify-between cursor-pointer select-none"
                     style="background-color: var(--bg-color);"
                     :style="carreraAbierta === carrera.id_carrera ? 'border-bottom: 1px solid var(--border-color);' : ''"
                     @click="toggleCarreraPanel(carrera.id_carrera)">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-xs shrink-0 transition-transform duration-200"
                              :style="carreraAbierta === carrera.id_carrera ? 'transform:rotate(90deg);' : ''"
                              style="color: var(--text-secondary);">▶</span>
                        <div class="min-w-0">
                            <span class="font-semibold text-sm" style="color: var(--text-color);">{{ carrera.nombre }}</span>
                            <span class="ml-2 text-xs" style="color: var(--text-muted);">
                                {{ CARRERA_TIPOS[carrera.tipo] ?? carrera.tipo }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <span class="text-xs"
                              :style="(carrera.periodos?.length ?? 0) > 0 ? 'color:#10b981;font-weight:600;' : 'color:var(--text-muted);'">
                            {{ carrera.periodos?.length ?? 0 }} período(s)
                        </span>
                        <button @click.stop="abrirAgregar(carrera.id_carrera)"
                            class="text-xs font-semibold px-2.5 py-1 rounded-md transition"
                            style="background-color: color-mix(in srgb, var(--primary-color) 12%, transparent); color: var(--primary-color);">
                            + Período
                        </button>
                    </div>
                </div>

                <!-- Lista de períodos -->
                <div v-show="carreraAbierta === carrera.id_carrera" class="px-5 py-4">
                    <p v-if="(carrera.periodos?.length ?? 0) === 0"
                       class="text-sm italic" style="color: var(--text-muted);">
                        Sin períodos — agrega el primer período de esta carrera.
                    </p>
                    <div v-else class="space-y-2">
                        <div v-for="p in carrera.periodos" :key="p.id_periodo"
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
                                    <span class="opacity-70 text-[10px] mr-1">📚</span>
                                    {{ fmtFecha(p.fecha_inicio) }} → {{ fmtFecha(p.fecha_fin) }}
                                    <span v-if="duracionDias(p.fecha_inicio, p.fecha_fin)" class="ml-1 opacity-50">
                                        ({{ duracionDias(p.fecha_inicio, p.fecha_fin) }})
                                    </span>
                                </p>
                                <p v-if="p.fecha_inicio_inscripcion" class="text-xs mt-0.5" style="color: #10b981;">
                                    <span class="opacity-70 text-[10px] mr-1">📝</span>
                                    Inscripciones: {{ fmtFecha(p.fecha_inicio_inscripcion) }} → {{ fmtFecha(p.fecha_fin_inscripcion) }}
                                </p>
                                <p v-else class="text-[10px] mt-0.5 opacity-40" style="color: var(--text-secondary);">
                                    📝 Sin ventana de inscripción definida
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
    </AdminLayout>

    <!-- ── Modal Agregar / Editar ─────────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="modalOpen"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: rgba(0,0,0,0.5);"
             @click.self="cerrar">
            <div class="w-full max-w-lg rounded-2xl shadow-2xl flex flex-col"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color); max-height: 92vh;">

                <!-- Header fijo -->
                <div class="flex items-center justify-between px-6 py-4 border-b shrink-0"
                     style="border-color: var(--border-color);">
                    <h3 class="text-base font-semibold" style="color: var(--text-color);">
                        {{ editando ? 'Editar Período' : 'Nuevo Período' }}
                    </h3>
                    <button @click="cerrar" class="text-lg leading-none" style="color: var(--text-secondary);">✕</button>
                </div>

                <!-- Contenido scrollable -->
                <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4">

                    <!-- Carrera selector (solo al crear) -->
                    <div v-if="!editando">
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Carrera *</label>
                        <ComboSelect
                            v-model="form.id_carrera"
                            :options="optsCarreras"
                            placeholder="— Selecciona carrera —"
                            emptyLabel="" />
                        <p v-if="form.errors.id_carrera" class="text-xs mt-1" style="color:#ef4444;">{{ form.errors.id_carrera }}</p>
                    </div>

                    <!-- Nombre -->
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Nombre *</label>
                        <input v-model="form.nombre" type="text"
                            placeholder="Ej: Semestre 1-2026"
                            class="input-field" maxlength="50" />
                        <p v-if="form.errors.nombre" class="text-xs mt-1" style="color:#ef4444;">{{ form.errors.nombre }}</p>
                    </div>

                    <!-- Tipo período -->
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Tipo de período *</label>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
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

                    <!-- Fechas de clases -->
                    <div class="rounded-lg p-4 border" style="border-color: color-mix(in srgb,#8b5cf6 30%,transparent); background-color: color-mix(in srgb,#8b5cf6 4%,transparent);">
                        <p class="text-[11px] font-semibold mb-2 flex items-center gap-1.5" style="color: #8b5cf6;">
                            📚 Fechas de clases
                        </p>
                        <div v-if="cronogramasFiltrados(form.tipo_periodo).length > 0" class="mb-3">
                            <select v-model="cronogramaModal" class="input-field text-xs w-full"
                                    @change="aplicarCronograma(cronogramaModal, form)">
                                <option value="">— Tomar fechas del cronograma de clases —</option>
                                <option v-for="c in cronogramasFiltrados(form.tipo_periodo)"
                                        :key="c.id_cronograma" :value="c.id_cronograma">
                                    {{ labelCronograma(c) }}
                                </option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Inicio clases *</label>
                                <input v-model="form.fecha_inicio" type="date" class="input-field w-full" />
                                <p v-if="form.errors.fecha_inicio" class="text-xs mt-1" style="color:#ef4444;">{{ form.errors.fecha_inicio }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Fin clases *</label>
                                <input v-model="form.fecha_fin" type="date" class="input-field w-full" :min="form.fecha_inicio || ''" />
                                <p v-if="form.errors.fecha_fin" class="text-xs mt-1" style="color:#ef4444;">{{ form.errors.fecha_fin }}</p>
                            </div>
                        </div>
                        <p v-if="form.fecha_inicio && form.fecha_fin && duracionDias(form.fecha_inicio, form.fecha_fin)"
                           class="text-xs mt-2" style="color: var(--text-secondary);">
                            Duración: <strong style="color: var(--text-color);">{{ duracionDias(form.fecha_inicio, form.fecha_fin) }}</strong>
                        </p>
                    </div>

                    <!-- Fechas de inscripción -->
                    <div class="rounded-lg p-4 border" style="border-color: color-mix(in srgb,#10b981 30%,transparent); background-color: color-mix(in srgb,#10b981 4%,transparent);">
                        <p class="text-[11px] font-semibold mb-2 flex items-center gap-1.5" style="color: #10b981;">
                            📝 Período de inscripciones
                        </p>
                        <div v-if="cronogramasInscripcionFiltrados.length > 0" class="mb-3">
                            <select v-model="cronogramaInscripcionModal" class="input-field text-xs w-full"
                                    @change="aplicarCronogramaInscripcion(cronogramaInscripcionModal, form)">
                                <option value="">— Tomar fechas del cronograma de inscripciones —</option>
                                <option v-for="c in cronogramasInscripcionFiltrados"
                                        :key="c.id_cronograma" :value="c.id_cronograma">
                                    {{ labelCronograma(c) }}
                                </option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Inicio inscripciones</label>
                                <input v-model="form.fecha_inicio_inscripcion" type="date" class="input-field w-full" />
                                <p v-if="form.errors.fecha_inicio_inscripcion" class="text-xs mt-1" style="color:#ef4444;">{{ form.errors.fecha_inicio_inscripcion }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Cierre inscripciones</label>
                                <input v-model="form.fecha_fin_inscripcion" type="date" class="input-field w-full" :min="form.fecha_inicio_inscripcion || ''" />
                                <p v-if="form.errors.fecha_fin_inscripcion" class="text-xs mt-1" style="color:#ef4444;">{{ form.errors.fecha_fin_inscripcion }}</p>
                            </div>
                        </div>
                        <p v-if="form.fecha_inicio_inscripcion && form.fecha_fin_inscripcion && duracionDias(form.fecha_inicio_inscripcion, form.fecha_fin_inscripcion)"
                           class="text-xs mt-2" style="color: var(--text-secondary);">
                            Ventana de inscripción: <strong style="color: var(--text-color);">{{ duracionDias(form.fecha_inicio_inscripcion, form.fecha_fin_inscripcion) }}</strong>
                        </p>
                        <p v-else class="text-xs mt-2 opacity-60" style="color: var(--text-secondary);">
                            Opcional — los estudiantes se inscriben en grupos durante este período.
                        </p>
                    </div>

                </div>

                <!-- Footer fijo -->
                <div class="flex justify-end gap-3 px-6 py-4 border-t shrink-0" style="border-color: var(--border-color);">
                    <button @click="cerrar" class="btn-secondary">Cancelar</button>
                    <button @click="guardar" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Guardando…' : (editando ? 'Actualizar' : 'Crear Período') }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- ── Modal Crear en Lote ────────────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="modalLote"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: rgba(0,0,0,0.5);"
             @click.self="modalLote = false">
            <div class="w-full max-w-xl rounded-2xl shadow-2xl flex flex-col"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color); max-height: 90vh;">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b shrink-0" style="border-color: var(--border-color);">
                    <div>
                        <h3 class="text-base font-semibold" style="color: var(--text-color);">⚡ Crear Períodos en Lote</h3>
                        <p class="text-xs mt-0.5" style="color: var(--text-secondary);">Mismo nombre · tipo independiente por carrera</p>
                    </div>
                    <button @click="modalLote = false" class="text-lg leading-none" style="color: var(--text-secondary);">✕</button>
                </div>

                <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5">

                    <!-- Copiar desde plantilla -->
                    <div v-if="plantillas.length > 0" class="rounded-lg p-3 border"
                         style="background-color: color-mix(in srgb,var(--primary-color) 6%,transparent); border-color: color-mix(in srgb,var(--primary-color) 25%,transparent);">
                        <p class="text-xs font-semibold mb-2" style="color: var(--primary-color);">
                            📋 Copiar configuración de período existente
                        </p>
                        <ComboSelect
                            v-model="plantillaSeleccionada"
                            :options="optsPlantillas"
                            placeholder="— Seleccionar período como plantilla —"
                            emptyLabel=""
                            @update:modelValue="aplicarPlantilla" />
                        <p class="text-[11px] mt-1.5" style="color: var(--text-secondary);">
                            Auto-rellena campos y pre-marca las mismas carreras. Solo cambia las fechas.
                        </p>
                    </div>

                    <!-- Nombre -->
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Nombre del período *</label>
                        <input v-model="formLote.nombre" type="text" maxlength="50"
                            placeholder="Ej: Semestre 1-2027"
                            class="input-field" />
                        <p v-if="formLote.errors.nombre" class="text-xs mt-1" style="color:#ef4444;">{{ formLote.errors.nombre }}</p>
                    </div>

                    <!-- Fechas de clases globales -->
                    <div class="rounded-lg p-3 border" style="border-color: color-mix(in srgb,#8b5cf6 30%,transparent); background-color: color-mix(in srgb,#8b5cf6 4%,transparent);">
                        <p class="text-[11px] font-semibold mb-2" style="color: #8b5cf6;">📚 Fechas de clases</p>
                        <div v-if="cronogramasClases.length > 0" class="mb-2">
                            <select v-model="cronogramaLote" class="input-field text-xs"
                                    @change="aplicarCronograma(cronogramaLote, formLote)">
                                <option value="">— Tomar del cronograma de clases —</option>
                                <option v-for="c in cronogramasFiltrados()"
                                        :key="c.id_cronograma" :value="c.id_cronograma">
                                    {{ labelCronograma(c) }}
                                </option>
                            </select>
                            <p class="text-[11px] mt-1 opacity-70" style="color: var(--text-secondary);">Auto-rellena fechas globales · podés ajustar por carrera</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Inicio clases *</label>
                                <input v-model="formLote.fecha_inicio" type="date" class="input-field" />
                                <p v-if="formLote.errors.fecha_inicio" class="text-xs mt-1" style="color:#ef4444;">{{ formLote.errors.fecha_inicio }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Fin clases *</label>
                                <input v-model="formLote.fecha_fin" type="date" class="input-field" :min="formLote.fecha_inicio || ''" />
                                <p v-if="formLote.errors.fecha_fin" class="text-xs mt-1" style="color:#ef4444;">{{ formLote.errors.fecha_fin }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Fechas de inscripción globales -->
                    <div class="rounded-lg p-3 border" style="border-color: color-mix(in srgb,#10b981 30%,transparent); background-color: color-mix(in srgb,#10b981 4%,transparent);">
                        <p class="text-[11px] font-semibold mb-2" style="color: #10b981;">📝 Período de inscripciones</p>
                        <div v-if="cronogramasInscripcion.length > 0" class="mb-2">
                            <select v-model="cronogramaInscripcionLote" class="input-field text-xs"
                                    @change="aplicarCronogramaInscripcion(cronogramaInscripcionLote, formLote)">
                                <option value="">— Tomar del cronograma de inscripciones —</option>
                                <option v-for="c in cronogramasInscripcion"
                                        :key="c.id_cronograma" :value="c.id_cronograma">
                                    {{ labelCronograma(c) }}
                                </option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Inicio inscripciones</label>
                                <input v-model="formLote.fecha_inicio_inscripcion" type="date" class="input-field" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Cierre inscripciones</label>
                                <input v-model="formLote.fecha_fin_inscripcion" type="date" class="input-field" :min="formLote.fecha_inicio_inscripcion || ''" />
                            </div>
                        </div>
                        <p v-if="!formLote.fecha_inicio_inscripcion" class="text-[11px] mt-1.5 opacity-60" style="color: var(--text-secondary);">
                            Opcional — controla cuándo pueden inscribirse los estudiantes.
                        </p>
                    </div>

                    <!-- Checklist de carreras -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);">
                                Carreras a aplicar *
                                <span class="font-normal opacity-70">({{ formLote.id_carreras.length }} seleccionadas)</span>
                            </label>
                            <button type="button" @click="toggleTodos"
                                class="text-xs font-medium" style="color: var(--primary-color);">
                                {{ todosSeleccionados ? 'Desmarcar todas' : 'Seleccionar todas' }}
                            </button>
                        </div>
                        <p v-if="formLote.errors['carreras']" class="text-xs mb-2" style="color:#ef4444;">{{ formLote.errors['carreras'] }}</p>

                        <div class="space-y-1 rounded-lg border overflow-hidden" style="border-color: var(--border-color);">
                            <label v-for="c in carrerasSelect" :key="c.id_carrera"
                                class="flex items-center gap-3 px-4 py-2.5 cursor-pointer transition-colors"
                                :class="formLote.id_carreras.includes(c.id_carrera) ? 'bg-opacity-5' : ''"
                                :style="formLote.id_carreras.includes(c.id_carrera)
                                    ? 'background-color: color-mix(in srgb,var(--primary-color) 6%,transparent); border-bottom: 1px solid var(--border-color);'
                                    : 'border-bottom: 1px solid var(--border-color);'"
                                onmouseover="this.style.backgroundColor='color-mix(in srgb,var(--primary-color) 6%,transparent)'"
                                onmouseout="if(!this.querySelector('input').checked) this.style.backgroundColor='transparent'">
                                <input type="checkbox"
                                    :checked="formLote.id_carreras.includes(c.id_carrera)"
                                    @change="toggleCarreraLote(c.id_carrera)"
                                    style="accent-color: var(--primary-color);" />
                                <span class="text-sm flex-1" style="color: var(--text-color);">{{ c.nombre }}</span>
                                <span v-if="c.modalidad" class="text-[10px] font-semibold px-1.5 py-0.5 rounded"
                                      :style="c.modalidad === 'anual'
                                          ? 'background-color:color-mix(in srgb,#10b981 15%,transparent);color:#10b981;'
                                          : c.modalidad === 'mensual'
                                          ? 'background-color:color-mix(in srgb,#3b82f6 15%,transparent);color:#3b82f6;'
                                          : 'background-color:color-mix(in srgb,#8b5cf6 15%,transparent);color:#8b5cf6;'">
                                    {{ c.modalidad }}
                                </span>
                            </label>
                        </div>

                        <!-- Tipo + fechas opcionales por carrera (para las seleccionadas) -->
                        <div v-if="formLote.id_carreras.length > 0" class="mt-3 space-y-2">
                            <p class="text-xs font-semibold" style="color: var(--text-secondary);">
                                Configuración por carrera <span class="font-normal opacity-60">(opcional)</span>
                            </p>
                            <div v-for="c in carrerasSelect.filter(x => formLote.id_carreras.includes(x.id_carrera))"
                                 :key="c.id_carrera"
                                 class="rounded-lg p-3 border"
                                 style="background-color: color-mix(in srgb,var(--text-color) 2%,transparent); border-color: var(--border-color);">
                                <p class="text-xs font-bold mb-2" style="color: var(--text-color);">{{ c.nombre }}</p>
                                <!-- Tipo -->
                                <div class="flex items-center gap-2 flex-wrap mb-2">
                                    <span class="text-[11px] font-medium shrink-0 w-14" style="color: var(--text-secondary);">Tipo:</span>
                                    <div class="flex gap-1.5 flex-wrap">
                                        <button v-for="(label, key) in TIPO_LABELS" :key="key"
                                            type="button"
                                            @click="tipoPorCarrera[c.id_carrera] = key"
                                            class="px-2 py-0.5 rounded text-[11px] font-semibold border transition"
                                            :style="(tipoPorCarrera[c.id_carrera] ?? 'semestral') === key
                                                ? `background-color:color-mix(in srgb,${TIPO_COLORS[key]} 20%,transparent);color:${TIPO_COLORS[key]};border-color:${TIPO_COLORS[key]};`
                                                : 'background:transparent;color:var(--text-secondary);border-color:var(--border-color);'">
                                            {{ label }}
                                        </button>
                                    </div>
                                </div>
                                <!-- Fechas opcionales -->
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-[11px] font-medium shrink-0 w-14" style="color: var(--text-secondary);">Fechas:</span>
                                    <input type="date"
                                        v-model="fechasPorCarrera[c.id_carrera].fecha_inicio"
                                        class="rounded border px-2 py-1 text-[11px] outline-none"
                                        style="background-color: var(--bg-color); border-color: var(--border-color); color: var(--text-color); min-width: 130px;" />
                                    <span class="text-[11px] opacity-40">→</span>
                                    <input type="date"
                                        v-model="fechasPorCarrera[c.id_carrera].fecha_fin"
                                        class="rounded border px-2 py-1 text-[11px] outline-none"
                                        style="background-color: var(--bg-color); border-color: var(--border-color); color: var(--text-color); min-width: 130px;" />
                                    <span v-if="fechasPorCarrera[c.id_carrera].fecha_inicio || fechasPorCarrera[c.id_carrera].fecha_fin"
                                          class="text-[10px] px-1.5 py-0.5 rounded font-medium"
                                          style="background-color:color-mix(in srgb,#f59e0b 15%,transparent); color:#f59e0b;">
                                        Personalizado
                                    </span>
                                    <span v-else class="text-[10px] opacity-40" style="color: var(--text-secondary);">
                                        (usa fechas globales)
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between px-6 py-4 border-t shrink-0" style="border-color: var(--border-color);">
                    <p class="text-xs" style="color: var(--text-secondary);">
                        Se crearán <strong style="color:var(--primary-color);">{{ formLote.id_carreras.length }}</strong> período(s)
                    </p>
                    <div class="flex gap-3">
                        <button @click="modalLote = false" class="btn-secondary">Cancelar</button>
                        <button @click="guardarLote" :disabled="formLote.processing || formLote.id_carreras.length === 0"
                            class="btn-primary"
                            :style="(formLote.processing || formLote.id_carreras.length === 0) ? 'opacity:0.5;' : ''">
                            {{ formLote.processing ? 'Creando...' : `Crear ${formLote.id_carreras.length} período(s)` }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- ── Confirm Clonar año siguiente ──────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="confirmClonar"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: rgba(0,0,0,0.5);">
            <div class="w-full max-w-sm rounded-2xl shadow-2xl p-6"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <p class="text-2xl mb-3 text-center">📅</p>
                <p class="font-semibold text-center mb-1" style="color: var(--text-color);">
                    Clonar períodos para {{ anoSiguiente }}
                </p>
                <p class="text-sm text-center mb-1" style="color: var(--text-secondary);">
                    Se copiarán todos los períodos activos del año actual con fechas +1 año.
                </p>
                <p class="text-xs text-center mb-5" style="color: var(--text-muted);">
                    Si ya existe un período con el mismo nombre para esa carrera en {{ anoSiguiente }}, se omite.
                </p>
                <div class="flex justify-center gap-3">
                    <button @click="confirmClonar = false" class="btn-secondary">Cancelar</button>
                    <button @click="clonarSiguienteAnio" :disabled="clonando"
                        class="inline-flex items-center gap-1.5 rounded-lg px-5 py-2 text-sm font-semibold transition"
                        style="background-color: #8b5cf6; color: #fff;"
                        :style="clonando ? 'opacity:0.6;' : ''">
                        {{ clonando ? 'Clonando...' : `Clonar ${anoSiguiente}` }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- ── Confirm Eliminar ───────────────────────────────────────────────── -->
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
                    <button @click="confirmEliminar = null" :disabled="eliminandoPeriodo" class="btn-secondary">Cancelar</button>
                    <button @click="confirmarEliminar" :disabled="eliminandoPeriodo" class="btn-danger"
                            :style="eliminandoPeriodo ? 'opacity:0.6;cursor:not-allowed;' : ''">
                        {{ eliminandoPeriodo ? 'Eliminando...' : 'Sí, eliminar' }}
                    </button>
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
