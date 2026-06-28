<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ComboSelect from '@/Components/ComboSelect.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    carreras:          Array,
    nivelesSelect:     Array,
    plantillas:        { type: Array, default: () => [] },
    cronogramasClases: { type: Array, default: () => [] },
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

const optsNiveles = computed(() =>
    nivelesParaModal.value.flatMap(grupo =>
        grupo.niveles.map(n => ({
            value: n.id_nivel,
            label: grupo.carrera + ' — ' + (n.nombre ?? 'Año ' + n.numero_nivel),
        }))
    )
);

// ── Modal agregar / editar ───────────────────────────────────────────────────
const modalOpen     = ref(false);
const editando      = ref(null);
const esCursoLibre  = ref(false);
const nombreCarreraModal = ref('');
const cronogramaModal = ref(null);

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
    modalOpen.value     = false;
    cronogramaModal.value = null;
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

// ── Modal Lote ────────────────────────────────────────────────────────────────
const cronogramaLote = ref(null);
const modalLote   = ref(false);
const formLote    = useForm({
    nombre:       '',
    fecha_inicio: '',
    fecha_fin:    '',
    max_materias: 5,
    id_niveles:   [],
    niveles:      [],  // construido antes de enviar: [{id_nivel, tipo_periodo}]
});

// Tipo y fechas por carrera: { [carrera_nombre]: { tipo, fecha_inicio, fecha_fin } }
const tipoPorCarrera   = ref({});
const fechasPorCarrera = ref({});  // { [carrera]: { fecha_inicio: '', fecha_fin: '' } }

const plantillaSeleccionada = ref('');

const optsPlantillas = computed(() =>
    props.plantillas.map((p, i) => ({ value: i, label: p.label }))
);

function aplicarPlantilla(idx) {
    if (idx === '' || idx === null || idx === undefined) return;
    const p = props.plantillas[idx];
    if (!p) return;
    formLote.nombre       = p.nombre;
    formLote.max_materias = p.max_materias;
    // Aplica tipo de la plantilla a todas las carreras como punto de partida
    for (const key of Object.keys(tipoPorCarrera.value)) {
        tipoPorCarrera.value[key] = p.tipo_periodo;
    }
    // Auto-marcar los mismos niveles
    formLote.id_niveles = [...p.id_niveles];
}

const todosNiveles = computed(() =>
    (props.nivelesSelect ?? []).map(n => n.id_nivel)
);

const todosSeleccionados = computed(() =>
    todosNiveles.value.length > 0 &&
    todosNiveles.value.every(id => formLote.id_niveles.includes(id))
);

function toggleTodos() {
    if (todosSeleccionados.value) {
        formLote.id_niveles = [];
    } else {
        formLote.id_niveles = [...todosNiveles.value];
    }
}

function toggleNivel(id) {
    const idx = formLote.id_niveles.indexOf(id);
    if (idx === -1) formLote.id_niveles.push(id);
    else formLote.id_niveles.splice(idx, 1);
}

function abrirLote() {
    formLote.reset();
    formLote.max_materias = 5;
    plantillaSeleccionada.value = '';
    cronogramaLote.value = null;
    // Inicializar tipo 'semestral' y fechas vacías para cada carrera
    const tipos   = {};
    const fechas  = {};
    for (const g of nivelesParaLote.value) {
        tipos[g.carrera]  = 'semestral';
        fechas[g.carrera] = { fecha_inicio: '', fecha_fin: '' };
    }
    tipoPorCarrera.value   = tipos;
    fechasPorCarrera.value = fechas;
    // Cerrar todos los acordeones
    loteAbiertos.value = {};
    modalLote.value = true;
}

function guardarLote() {
    // Construir array niveles con tipo + fechas por carrera (vacías = usa global)
    const niveles = [];
    for (const grupo of nivelesParaLote.value) {
        const tipo  = tipoPorCarrera.value[grupo.carrera] ?? 'semestral';
        const fcs   = fechasPorCarrera.value[grupo.carrera] ?? {};
        for (const n of grupo.niveles) {
            if (formLote.id_niveles.includes(n.id_nivel)) {
                niveles.push({
                    id_nivel:     n.id_nivel,
                    tipo_periodo: tipo,
                    fecha_inicio: fcs.fecha_inicio || null,
                    fecha_fin:    fcs.fecha_fin    || null,
                });
            }
        }
    }
    formLote.niveles = niveles;
    formLote.post(route('director.periodos.lote'), {
        onSuccess: () => { modalLote.value = false; formLote.reset(); },
    });
}

// Estado abierto/cerrado por carrera en el acordeón del lote
const loteAbiertos = ref({});

function toggleCarrera(grupo) {
    const ids = grupo.niveles.map(n => n.id_nivel);
    const todosCheck = ids.every(id => formLote.id_niveles.includes(id));
    if (todosCheck) {
        formLote.id_niveles = formLote.id_niveles.filter(id => !ids.includes(id));
    } else {
        ids.forEach(id => {
            if (!formLote.id_niveles.includes(id)) formLote.id_niveles.push(id);
        });
    }
}

// Niveles agrupados por carrera para el checklist del lote
const nivelesParaLote = computed(() => {
    const map = {};
    for (const n of (props.nivelesSelect ?? [])) {
        if (!map[n.id_carrera]) {
            map[n.id_carrera] = { carrera: n.carrera_nombre, niveles: [] };
        }
        map[n.id_carrera].niveles.push(n);
    }
    return Object.values(map);
});

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

// ── Selector cronograma → auto-rellena fechas ────────────────────────────────
const MODALIDAD_LABELS = {
    mensual:   'Mensual',
    semestral: 'Semestral',
    anual:     'Anual',
    intensivo: 'Intensivo',
};

function cronogramasFiltrados(modalidadFiltro = null) {
    return props.cronogramasClases
        .filter(c => !modalidadFiltro || c.modalidad === null || c.modalidad === modalidadFiltro);
}

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

// ── Clonar año siguiente ─────────────────────────────────────────────────────
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
        niveles: (c.niveles ?? []).map(n => ({
            ...n,
            periodos: n.periodos.filter(p =>
                p.fecha_inicio && new Date(p.fecha_inicio).getFullYear() === anoActual
            ),
        })),
        periodos_directos: (c.periodos_directos ?? []).filter(p =>
            p.fecha_inicio && new Date(p.fecha_inicio).getFullYear() === anoActual
        ),
    }));
});

function totalPeriodosCarrera(carrera) {
    const dePeriodos = carrera.periodos_directos?.length ?? 0;
    const deNiveles  = (carrera.niveles ?? []).reduce((s, n) => s + n.periodos.length, 0);
    return dePeriodos + deNiveles;
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
                <span class="text-xs font-medium" style="color: var(--text-secondary);">
                    Solo vigentes
                </span>
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

                <!-- Cabecera CLICKEABLE -->
                <div class="px-5 py-3 flex items-center justify-between cursor-pointer select-none"
                     style="background-color: var(--bg-color);"
                     :style="carreraAbierta === carrera.id_carrera ? 'border-bottom: 1px solid var(--border-color);' : ''"
                     @click="toggleCarreraPanel(carrera.id_carrera)">
                    <div class="flex items-center gap-3 min-w-0">
                        <!-- Flecha -->
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
                        <!-- Resumen rápido -->
                        <span class="text-xs" style="color: var(--text-secondary);">
                            <span v-if="carrera.tipo !== 'curso_libre'">{{ carrera.niveles.length }} año(s) · </span>
                            <span :style="totalPeriodosCarrera(carrera) > 0 ? 'color:#10b981;font-weight:600;' : 'color:var(--text-muted);'">
                                {{ totalPeriodosCarrera(carrera) }} período(s)
                            </span>
                        </span>
                        <!-- Botón + para curso libre -->
                        <button v-if="carrera.tipo === 'curso_libre' && carrera.periodos_directos.length === 0"
                            @click.stop="abrirAgregar('', carrera.id_carrera, true, carrera.nombre)"
                            class="text-xs font-semibold px-2.5 py-1 rounded-md transition"
                            style="background-color: color-mix(in srgb, var(--primary-color) 12%, transparent); color: var(--primary-color);">
                            + Período
                        </button>
                        <span v-else-if="carrera.tipo === 'curso_libre'" class="text-xs font-medium" style="color: #10b981;">✓</span>
                    </div>
                </div>

                <!-- ── CURSO LIBRE: período directo ──────────────────────────── -->
                <div v-if="carrera.tipo === 'curso_libre'"
                     v-show="carreraAbierta === carrera.id_carrera"
                     class="px-5 py-4">
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
                <div v-else
                     v-show="carreraAbierta === carrera.id_carrera"
                     class="divide-y" style="border-color: var(--border-color);">
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
                        <ComboSelect
                            v-model="form.id_nivel"
                            :options="optsNiveles"
                            placeholder="— Selecciona nivel —"
                            emptyLabel="" />
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

                    <!-- Tomar fechas del cronograma -->
                    <div v-if="cronogramasClases.length > 0">
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">📅 Tomar fechas del cronograma</label>
                        <select
                            v-model="cronogramaModal"
                            class="input-field"
                            @change="aplicarCronograma(cronogramaModal, form)">
                            <option value="">— Seleccionar cronograma (opcional) —</option>
                            <option v-for="c in cronogramasFiltrados(form.tipo_periodo)"
                                    :key="c.id_cronograma"
                                    :value="c.id_cronograma">
                                {{ labelCronograma(c) }}
                            </option>
                        </select>
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

    <!-- ── Modal Crear en Lote ──────────────────────────────────────────── -->
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
                        <p class="text-xs mt-0.5" style="color: var(--text-secondary);">Fechas comunes · tipo independiente por carrera</p>
                    </div>
                    <button @click="modalLote = false" class="text-lg leading-none" style="color: var(--text-secondary);">✕</button>
                </div>

                <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5">

                    <!-- Copiar desde plantilla existente -->
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
                            Auto-rellena campos y pre-marca los mismos niveles. Solo cambia las fechas.
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

                    <!-- Tomar fechas del cronograma -->
                    <div v-if="cronogramasClases.length > 0">
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">📅 Tomar fechas del cronograma</label>
                        <select
                            v-model="cronogramaLote"
                            class="input-field"
                            @change="aplicarCronograma(cronogramaLote, formLote)">
                            <option value="">— Seleccionar cronograma (opcional) —</option>
                            <option v-for="c in cronogramasFiltrados()"
                                    :key="c.id_cronograma"
                                    :value="c.id_cronograma">
                                {{ labelCronograma(c) }}
                            </option>
                        </select>
                        <p class="text-[11px] mt-1" style="color: var(--text-muted);">Auto-rellena las fechas globales · podés ajustar por carrera debajo</p>
                    </div>

                    <!-- Fechas -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Fecha inicio *</label>
                            <input v-model="formLote.fecha_inicio" type="date" class="input-field" />
                            <p v-if="formLote.errors.fecha_inicio" class="text-xs mt-1" style="color:#ef4444;">{{ formLote.errors.fecha_inicio }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Fecha fin *</label>
                            <input v-model="formLote.fecha_fin" type="date" class="input-field" :min="formLote.fecha_inicio || ''" />
                            <p v-if="formLote.errors.fecha_fin" class="text-xs mt-1" style="color:#ef4444;">{{ formLote.errors.fecha_fin }}</p>
                        </div>
                    </div>

                    <!-- Máx materias -->
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Máx. materias por período *</label>
                        <input v-model.number="formLote.max_materias" type="number" min="1" max="30" class="input-field" style="max-width: 120px;" />
                        <p v-if="formLote.errors.max_materias" class="text-xs mt-1" style="color:#ef4444;">{{ formLote.errors.max_materias }}</p>
                    </div>

                    <!-- Checklist de niveles — acordeón por carrera -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);">
                                Niveles a aplicar *
                                <span class="font-normal opacity-70">({{ formLote.id_niveles.length }} seleccionados)</span>
                            </label>
                            <button type="button" @click="toggleTodos"
                                class="text-xs font-medium"
                                style="color: var(--primary-color);">
                                {{ todosSeleccionados ? 'Desmarcar todos' : 'Seleccionar todos' }}
                            </button>
                        </div>
                        <p v-if="formLote.errors['niveles']" class="text-xs mb-2" style="color:#ef4444;">{{ formLote.errors['niveles'] }}</p>

                        <div class="space-y-2">
                            <div v-for="grupo in nivelesParaLote" :key="grupo.carrera"
                                class="rounded-lg border overflow-hidden"
                                style="border-color: var(--border-color);">

                                <!-- Header carrera colapsable -->
                                <button type="button"
                                    @click="loteAbiertos[grupo.carrera] = !loteAbiertos[grupo.carrera]"
                                    class="w-full flex items-center justify-between px-3 py-2.5 transition-colors"
                                    :style="loteAbiertos[grupo.carrera]
                                        ? 'background-color: color-mix(in srgb,var(--primary-color) 10%,transparent);'
                                        : 'background-color: color-mix(in srgb,var(--text-color) 4%,transparent);'">
                                    <div class="flex items-center gap-2">
                                        <!-- Checkbox carrera (selecciona/deselecciona todos sus niveles) -->
                                        <input type="checkbox"
                                            :checked="grupo.niveles.every(n => formLote.id_niveles.includes(n.id_nivel))"
                                            :indeterminate="grupo.niveles.some(n => formLote.id_niveles.includes(n.id_nivel)) && !grupo.niveles.every(n => formLote.id_niveles.includes(n.id_nivel))"
                                            @click.stop="toggleCarrera(grupo)"
                                            style="accent-color: var(--primary-color);" />
                                        <span class="text-xs font-bold uppercase tracking-wide" style="color: var(--text-color);">
                                            {{ grupo.carrera }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="text-[11px] font-medium"
                                            :style="grupo.niveles.some(n => formLote.id_niveles.includes(n.id_nivel))
                                                ? 'color: var(--primary-color);'
                                                : 'color: var(--text-secondary);'">
                                            {{ grupo.niveles.filter(n => formLote.id_niveles.includes(n.id_nivel)).length }}/{{ grupo.niveles.length }}
                                        </span>
                                        <span class="text-[10px] opacity-50 transition-transform duration-200"
                                            :style="loteAbiertos[grupo.carrera] ? 'transform:rotate(180deg)' : ''">▾</span>
                                    </div>
                                </button>

                                <!-- Niveles (colapsable) -->
                                <div v-show="loteAbiertos[grupo.carrera]">
                                    <!-- Tipo + fechas por carrera -->
                                    <div class="px-4 py-3 border-t space-y-2.5"
                                         style="border-color: var(--border-color); background-color: color-mix(in srgb,var(--text-color) 2%,transparent);">
                                        <!-- Tipo -->
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-[11px] font-medium shrink-0 w-14" style="color: var(--text-secondary);">Tipo:</span>
                                            <div class="flex gap-1.5 flex-wrap">
                                                <button v-for="(label, key) in TIPO_LABELS" :key="key"
                                                    type="button"
                                                    @click="tipoPorCarrera[grupo.carrera] = key"
                                                    class="px-2 py-0.5 rounded text-[11px] font-semibold border transition"
                                                    :style="(tipoPorCarrera[grupo.carrera] ?? 'semestral') === key
                                                        ? `background-color:color-mix(in srgb,${TIPO_COLORS[key]} 20%,transparent);color:${TIPO_COLORS[key]};border-color:${TIPO_COLORS[key]};`
                                                        : 'background:transparent;color:var(--text-secondary);border-color:var(--border-color);'">
                                                    {{ label }}
                                                </button>
                                            </div>
                                        </div>
                                        <!-- Fechas opcionales (sobreescriben las globales si se llenan) -->
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-[11px] font-medium shrink-0 w-14" style="color: var(--text-secondary);">Fechas:</span>
                                            <div class="flex items-center gap-2 flex-1 flex-wrap">
                                                <input type="date"
                                                    v-model="fechasPorCarrera[grupo.carrera].fecha_inicio"
                                                    class="rounded border px-2 py-1 text-[11px] outline-none"
                                                    style="background-color: var(--bg-color); border-color: var(--border-color); color: var(--text-color); min-width: 130px;"
                                                    :placeholder="formLote.fecha_inicio || 'Inicio'" />
                                                <span class="text-[11px] opacity-40">→</span>
                                                <input type="date"
                                                    v-model="fechasPorCarrera[grupo.carrera].fecha_fin"
                                                    class="rounded border px-2 py-1 text-[11px] outline-none"
                                                    style="background-color: var(--bg-color); border-color: var(--border-color); color: var(--text-color); min-width: 130px;"
                                                    :placeholder="formLote.fecha_fin || 'Fin'" />
                                                <span v-if="fechasPorCarrera[grupo.carrera].fecha_inicio || fechasPorCarrera[grupo.carrera].fecha_fin"
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
                                    <label v-for="n in grupo.niveles" :key="n.id_nivel"
                                        class="flex items-center gap-3 px-4 py-2.5 cursor-pointer transition-colors"
                                        style="border-top: 1px solid var(--border-color);"
                                        onmouseover="this.style.backgroundColor='color-mix(in srgb,var(--primary-color) 6%,transparent)'"
                                        onmouseout="this.style.backgroundColor='transparent'">
                                        <input type="checkbox"
                                            :checked="formLote.id_niveles.includes(n.id_nivel)"
                                            @change="toggleNivel(n.id_nivel)"
                                            style="accent-color: var(--primary-color);" />
                                        <span class="text-sm" style="color: var(--text-color);">
                                            {{ n.nombre ?? 'Año ' + n.numero_nivel }}
                                        </span>
                                        <span class="text-[11px] ml-auto" style="color: var(--text-secondary);">
                                            Año {{ n.numero_nivel }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between px-6 py-4 border-t shrink-0" style="border-color: var(--border-color);">
                    <p class="text-xs" style="color: var(--text-secondary);">
                        Se crearán <strong style="color:var(--primary-color);">{{ formLote.id_niveles.length }}</strong> período(s)
                        <span v-if="formLote.id_niveles.length > 0" class="opacity-60"> · tipo por carrera</span>
                    </p>
                    <div class="flex gap-3">
                        <button @click="modalLote = false" class="btn-secondary">Cancelar</button>
                        <button @click="guardarLote" :disabled="formLote.processing || formLote.id_niveles.length === 0"
                            class="btn-primary"
                            :style="(formLote.processing || formLote.id_niveles.length === 0) ? 'opacity:0.5;' : ''">
                            {{ formLote.processing ? 'Creando...' : `Crear ${formLote.id_niveles.length} período(s)` }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- ── Confirm Clonar año siguiente ───────────────────────────────────── -->
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
                    Si ya existe un período para ese nivel en {{ anoSiguiente }} se omite automáticamente.
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
