<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import { errEntero, errCodigo } from '@/utils/validacion.js';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ComboSelect from '@/Components/ComboSelect.vue';

const canEdit = computed(() => ['propietario', 'director'].includes(usePage().props.auth?.user?.role));

const props = defineProps({
    periodos:        { type: Array,  default: () => [] },
    materias:        { type: Array,  default: () => [] },
    aulas:           { type: Array,  default: () => [] },
    profesores:      { type: Array,  default: () => [] },
    horarios:        { type: Array,  default: () => [] },
    mallaPorCarrera:    { type: Object, default: () => ({}) },
    ocupadosPorPeriodo: { type: Object, default: () => ({}) },
});

// ── Stats ─────────────────────────────────────────────────────────────────────
const todosGrupos   = computed(() => props.periodos.flatMap(p => p.grupos));
const totalGrupos   = computed(() => todosGrupos.value.length);
const gruposActivos = computed(() => todosGrupos.value.filter(g => g.activo).length);
const conVacantes   = computed(() => todosGrupos.value.filter(g => g.activo && (g.vacantes_max - g.vacantes_ocupadas) > 0).length);

// ── Períodos abiertos/cerrados ─────────────────────────────────────────────────
const periodosAbiertos = ref({});
props.periodos.forEach(p => { periodosAbiertos.value[p.id_periodo] = p.activo; });
const togglePeriodo = (id) => { periodosAbiertos.value[id] = !periodosAbiertos.value[id]; };

// ── Filtros ───────────────────────────────────────────────────────────────────
const filtroCarrera = ref('');
const soloActivos   = ref(true);
const hoy      = new Date().toISOString().slice(0, 10);
const anoActual = new Date().getFullYear();

const optsCarrerasFiltro = computed(() => {
    const seen = new Set();
    const opts = [];
    for (const p of props.periodos) {
        if (p.carrera_nombre && p.id_carrera && !seen.has(p.id_carrera)) {
            seen.add(p.id_carrera);
            opts.push({ value: p.id_carrera, label: p.carrera_nombre });
        }
    }
    return opts.sort((a, b) => a.label.localeCompare(b.label));
});

// Lista filtrada plana (usada por modales de clonar, etc.)
const periodosFiltrados = computed(() => {
    let lista = props.periodos;
    if (soloActivos.value) {
        lista = lista.filter(p =>
            p.activo && (
                new Date(p.fecha_inicio).getFullYear() <= anoActual || // año actual o anterior
                p.grupos.length > 0                                     // o ya tiene grupos
            )
        );
    }
    if (filtroCarrera.value) lista = lista.filter(p => p.id_carrera === filtroCarrera.value);
    return lista;
});

// Agrupado por carrera para el accordion principal
const periodosPorCarrera = computed(() => {
    const map = {};
    for (const p of periodosFiltrados.value) {
        const key = p.id_carrera ?? '__libre__';
        if (!map[key]) {
            map[key] = {
                id_carrera: p.id_carrera ?? key,
                nombre: p.carrera_nombre ?? 'Sin carrera',
                periodos: [],
                totalGrupos: 0,
            };
        }
        map[key].periodos.push(p);
        map[key].totalGrupos += gruposAgrupados(p.grupos).length;
    }
    return Object.values(map).sort((a, b) => a.nombre.localeCompare(b.nombre));
});

// Accordion externo: qué carrera está abierta
const carrerasAbiertas = ref({});
const toggleCarreraGrupos = (id) => {
    carrerasAbiertas.value[id] = !carrerasAbiertas.value[id];
};

// ── Options para ComboSelect ──────────────────────────────────────────────────
const optsPeriodos = computed(() =>
    props.periodos
        .filter(p => p.activo)
        .sort((a, b) => (b.fecha_inicio ?? '').localeCompare(a.fecha_inicio ?? ''))
        .map(p => ({
            value: p.id_periodo,
            label: (p.carrera_nombre ? p.carrera_nombre + ' · ' : '')
                 + (p.nivel_nombre ? p.nivel_nombre + ' — ' : '')
                 + p.nombre,
        }))
);

// ── Filtro de carrera para modal Nuevo Grupo ──────────────────────────────────
const filtroCarreraModal  = ref('');
const soloVigentesModal   = ref(true);
const optsPeriodosModalFiltrados = computed(() => {
    let lista = props.periodos.filter(p => p.activo);
    if (soloVigentesModal.value) lista = lista.filter(p => new Date(p.fecha_inicio).getFullYear() === anoActual);
    if (filtroCarreraModal.value) lista = lista.filter(p => p.id_carrera === filtroCarreraModal.value);
    return lista
        .sort((a, b) => (b.fecha_inicio ?? '').localeCompare(a.fecha_inicio ?? ''))
        .map(p => ({
            value: p.id_periodo,
            label: (filtroCarreraModal.value ? '' : (p.carrera_nombre ? p.carrera_nombre + ' · ' : ''))
                 + (p.nivel_nombre ? p.nivel_nombre + ' — ' : '')
                 + p.nombre,
        }));
});

// ── Filtro de carrera para modal Clonar Oferta ────────────────────────────────
const filtroCarreraClonar  = ref('');
const soloVigentesClonar   = ref(true);
const optsPeriodosOrigenFiltrados = computed(() => {
    let lista = props.periodos;
    if (soloVigentesClonar.value) lista = lista.filter(p => new Date(p.fecha_inicio).getFullYear() === anoActual);
    if (filtroCarreraClonar.value) lista = lista.filter(p => p.id_carrera === filtroCarreraClonar.value);
    return lista
        .sort((a, b) => (b.fecha_inicio ?? '').localeCompare(a.fecha_inicio ?? ''))
        .map(p => ({
            value: p.id_periodo,
            label: (filtroCarreraClonar.value ? '' : (p.carrera_nombre ? p.carrera_nombre + ' · ' : ''))
                 + (p.nivel_nombre ? p.nivel_nombre + ' — ' : '')
                 + p.nombre,
        }));
});
const optsPeriodosDestinoClonarFiltrados = computed(() =>
    optsPeriodosOrigenFiltrados.value.filter(p => p.value !== formClonar.id_periodo_origen)
);

const optsAulas = computed(() => props.aulas.map(a => ({
    value: a.id_aula,
    label: a.nombre + ' (cap. ' + a.capacidad + ')',
})));

const optsProfesores = computed(() => props.profesores.map(p => ({
    value: p.id_profesor,
    label: p.nombre + (p.especialidad ? ' · ' + p.especialidad : ''),
})));

const optsHorarios = computed(() => props.horarios.map(h => ({
    value: h.id_horario,
    label: h.label,
})));

// ── Filtrado anti-conflicto: Nuevo Grupo ──────────────────────────────────────
const ocupadosNuevo = computed(() =>
    !formNuevo.id_periodo ? [] : (props.ocupadosPorPeriodo[formNuevo.id_periodo] ?? [])
);

const optsAulasNuevo = computed(() => {
    const tomadas = new Set();
    if (formNuevo.id_horario) {
        for (const o of ocupadosNuevo.value)
            if (o.id_horario === formNuevo.id_horario) tomadas.add(o.id_aula);
    }
    return props.aulas
        .filter(a => !tomadas.has(a.id_aula))
        .map(a => ({ value: a.id_aula, label: a.nombre + ' (cap. ' + a.capacidad + ')' }));
});

const optsProfesoresNuevo = computed(() => {
    const ocupados = new Set();
    if (formNuevo.id_horario) {
        for (const o of ocupadosNuevo.value)
            if (o.id_horario === formNuevo.id_horario) ocupados.add(o.id_profesor);
    }
    return props.profesores
        .filter(p => !ocupados.has(p.id_profesor))
        .map(p => ({ value: p.id_profesor, label: p.nombre + (p.especialidad ? ' · ' + p.especialidad : '') }));
});

const optsHorariosNuevo = computed(() => {
    const bloqueados = new Set();
    for (const o of ocupadosNuevo.value) {
        if (formNuevo.id_aula    && o.id_aula    === formNuevo.id_aula)    bloqueados.add(o.id_horario);
        if (formNuevo.id_profesor && o.id_profesor === formNuevo.id_profesor) bloqueados.add(o.id_horario);
    }
    return props.horarios
        .filter(h => !bloqueados.has(h.id_horario))
        .map(h => ({ value: h.id_horario, label: h.label }));
});

// ── Filtrado anti-conflicto: Editar Grupo ─────────────────────────────────────
const ocupadosEdit = computed(() => {
    if (!grupoEdit.value) return [];
    const excluir = new Set(grupoEdit.value.ids_oferta ?? [grupoEdit.value.id_oferta]);
    return (props.ocupadosPorPeriodo[grupoEdit.value.id_periodo] ?? [])
        .filter(o => !excluir.has(o.id_oferta));
});

const optsAulasEdit = computed(() => {
    const tomadas = new Set();
    const todosHorarios = [
        ...(grupoEdit.value?.horarios ?? []).map(h => h.id_horario),
        ...(formEdit.dias_agregar ?? []),
    ];
    for (const idH of todosHorarios)
        for (const o of ocupadosEdit.value)
            if (o.id_horario === idH) tomadas.add(o.id_aula);
    return props.aulas
        .filter(a => !tomadas.has(a.id_aula))
        .map(a => ({ value: a.id_aula, label: a.nombre + ' (cap. ' + a.capacidad + ')' }));
});

const optsProfesoresEdit = computed(() => {
    const ocupados = new Set();
    const todosHorarios = [
        ...(grupoEdit.value?.horarios ?? []).map(h => h.id_horario),
        ...(formEdit.dias_agregar ?? []),
    ];
    for (const idH of todosHorarios)
        for (const o of ocupadosEdit.value)
            if (o.id_horario === idH) ocupados.add(o.id_profesor);
    return props.profesores
        .filter(p => !ocupados.has(p.id_profesor))
        .map(p => ({ value: p.id_profesor, label: p.nombre + (p.especialidad ? ' · ' + p.especialidad : '') }));
});

// Horarios disponibles para agregar: mismo rango horario, día distinto a los ya cubiertos, sin conflicto
const diasDisponiblesEdit = computed(() => {
    if (!grupoEdit.value) return [];
    const idsActuales  = new Set((grupoEdit.value.horarios ?? []).map(h => h.id_horario));
    const diasActuales = new Set((grupoEdit.value.horarios ?? []).map(h => h.dia_semana));
    return props.horarios.filter(h =>
        !idsActuales.has(h.id_horario) &&
        !diasActuales.has(h.dia_semana) &&
        h.hora_inicio === grupoEdit.value.hora_inicio &&
        h.hora_fin    === grupoEdit.value.hora_fin &&
        !ocupadosEdit.value.some(o =>
            o.id_horario === h.id_horario &&
            (o.id_aula === formEdit.id_aula || o.id_profesor === formEdit.id_profesor)
        )
    );
});

// ── Modal Crear ───────────────────────────────────────────────────────────────
const showModal = ref(false);
const formNuevo = useForm({
    id_periodo:   '',
    id_materia:   '',
    id_aula:      '',
    id_profesor:  '',
    id_horario:   '',
    vacantes_max: 30,
    codigo_grupo: '',
    dias_extra:   [],
});

const diasAbrev = { lunes: 'Lun', martes: 'Mar', miercoles: 'Mié', jueves: 'Jue', viernes: 'Vie', sabado: 'Sáb' };

const horarioBaseNuevo = computed(() =>
    props.horarios.find(h => h.id_horario === formNuevo.id_horario)
);

// Días con la misma hora que el horario seleccionado, sin conflicto de aula/profesor
// Excluye el día base; incluye días ya marcados en dias_extra para que puedan deseleccionarse
const diasCompatiblesNuevo = computed(() => {
    const base = horarioBaseNuevo.value;
    if (!base || !formNuevo.id_periodo) return [];
    const diasPorId = new Map(props.horarios.map(h => [h.id_horario, h.dia_semana]));
    const diasExtrasSet = new Set(formNuevo.dias_extra.map(id => diasPorId.get(id)).filter(Boolean));
    return props.horarios.filter(h =>
        h.id_horario  !== base.id_horario &&
        h.dia_semana  !== base.dia_semana &&
        h.hora_inicio === base.hora_inicio &&
        h.hora_fin    === base.hora_fin &&
        // Si ya está seleccionado: mostrarlo para que el usuario pueda deseleccionarlo
        // Si no está seleccionado: excluir si el día ya está cubierto por otro extra seleccionado
        (formNuevo.dias_extra.includes(h.id_horario) || !diasExtrasSet.has(h.dia_semana)) &&
        !ocupadosNuevo.value.some(o =>
            o.id_horario === h.id_horario &&
            (o.id_aula === formNuevo.id_aula || o.id_profesor === formNuevo.id_profesor)
        )
    );
});

function toggleDiaExtra(idHorario) {
    const idx = formNuevo.dias_extra.indexOf(idHorario);
    if (idx === -1) formNuevo.dias_extra.push(idHorario);
    else formNuevo.dias_extra.splice(idx, 1);
}

// Período seleccionado en el modal → para filtrar materias por malla
const periodoDelModal = computed(() =>
    props.periodos.find(p => p.id_periodo === formNuevo.id_periodo)
);

// Materias filtradas por malla de la carrera del período seleccionado
const optsMateriasFiltradas = computed(() => {
    const p = periodoDelModal.value;
    if (!p?.id_carrera) {
        return props.materias.map(m => ({ value: m.id_materia, label: m.codigo + ' · ' + m.nombre }));
    }
    const malla = props.mallaPorCarrera[p.id_carrera] ?? [];
    if (malla.length === 0) {
        return props.materias.map(m => ({ value: m.id_materia, label: m.codigo + ' · ' + m.nombre }));
    }
    return malla.map(m => ({
        value: m.id_materia,
        label: 'Año ' + m.numero_nivel + ' · ' + m.codigo + ' · ' + m.nombre,
    }));
});

// Al cambiar período, resetear campos dependientes (cambia la lista de ocupados)
watch(() => formNuevo.id_periodo, () => {
    formNuevo.id_materia  = '';
    formNuevo.id_aula     = '';
    formNuevo.id_profesor = '';
    formNuevo.id_horario  = '';
    formNuevo.dias_extra  = [];
});
// Al cambiar carrera en el modal, resetear período y materia
watch(filtroCarreraModal, () => { formNuevo.id_periodo = ''; formNuevo.id_materia = ''; });

// ── Aula seleccionada → auto-fill vacantes y código ───────────────────────────
const aulaSeleccionada = computed(() =>
    props.aulas.find(a => a.id_aula === formNuevo.id_aula)
);

// Capacidad del aula seleccionada (límite máximo de vacantes)
const aulaCapacidad = computed(() => aulaSeleccionada.value?.capacidad ?? 200);

// Genera código automático: "PROG101-A06" desde sigla de materia + identificador de aula.
// Si el código base ya existe en el período, usa la hora del horario seleccionado como sufijo
// (ej. PROG101-A06-09 para las 09:15). Así grupos de distinta hora tienen códigos descriptivos.
function generarCodigoAuto() {
    const materia = (() => {
        const cid = periodoDelModal.value?.id_carrera;
        if (cid) {
            const enMalla = (props.mallaPorCarrera[cid] ?? []).find(m => m.id_materia === formNuevo.id_materia);
            if (enMalla) return enMalla;
        }
        return props.materias.find(m => m.id_materia === formNuevo.id_materia);
    })();
    const aula = aulaSeleccionada.value;
    if (!materia || !aula) return;

    const matCode  = (materia.codigo ?? '').replace(/[-\s]/g, '');
    const aulaPart = aula.nombre
        .replace(/^aula\s+/i, '')
        .replace(/[-\s]/g, '')
        .normalize('NFD').replace(/[̀-ͯ]/g, '');
    const base = (matCode + '-' + aulaPart).substring(0, 15); // máx 15 para dejar espacio al sufijo

    const codigosUsados = new Set(
        (props.periodos.find(p => p.id_periodo === formNuevo.id_periodo)?.grupos ?? [])
            .map(g => g.codigo_grupo)
    );

    // 1) Sin sufijo
    let candidato = base.substring(0, 20);
    if (!codigosUsados.has(candidato)) { formNuevo.codigo_grupo = candidato; return; }

    // 2) Sufijo con hora del horario (ej. -07 para 07:00, -09 para 09:15)
    const horario = horarioBaseNuevo.value;
    if (horario?.hora_inicio) {
        const hora2  = horario.hora_inicio.substring(0, 2);             // "07"
        const hora4  = horario.hora_inicio.replace(':', '').substring(0, 4); // "0700"

        candidato = (base + '-' + hora2).substring(0, 20);
        if (!codigosUsados.has(candidato)) { formNuevo.codigo_grupo = candidato; return; }

        candidato = (base.substring(0, 14) + '-' + hora4).substring(0, 20);
        if (!codigosUsados.has(candidato)) { formNuevo.codigo_grupo = candidato; return; }
    }

    // 3) Letras como último recurso (A, B, C…)
    for (const letter of 'ABCDEFGHIJKLMNOP') {
        candidato = (base.substring(0, 18) + '-' + letter).substring(0, 20);
        if (!codigosUsados.has(candidato)) { formNuevo.codigo_grupo = candidato; return; }
    }

    formNuevo.codigo_grupo = '';
}

watch(() => formNuevo.id_aula, (newId) => {
    const aula = props.aulas.find(a => a.id_aula === newId);
    if (aula) formNuevo.vacantes_max = aula.capacidad;
    generarCodigoAuto();
    if (formNuevo.id_horario && !optsHorariosNuevo.value.some(h => h.value === formNuevo.id_horario))
        formNuevo.id_horario = '';
    // Quita extras que ahora conflictúan con la nueva aula
    formNuevo.dias_extra = formNuevo.dias_extra.filter(id =>
        !ocupadosNuevo.value.some(o => o.id_horario === id && o.id_aula === newId)
    );
});

watch(() => formNuevo.id_materia, () => {
    generarCodigoAuto();
});

watch(() => formNuevo.id_profesor, (newId) => {
    if (formNuevo.id_horario && !optsHorariosNuevo.value.some(h => h.value === formNuevo.id_horario))
        formNuevo.id_horario = '';
    // Quita extras que ahora conflictúan con el nuevo profesor
    formNuevo.dias_extra = formNuevo.dias_extra.filter(id =>
        !ocupadosNuevo.value.some(o => o.id_horario === id && o.id_profesor === newId)
    );
});

watch(() => formNuevo.id_horario, () => {
    if (formNuevo.id_aula    && !optsAulasNuevo.value.some(a => a.value === formNuevo.id_aula))
        formNuevo.id_aula = '';
    if (formNuevo.id_profesor && !optsProfesoresNuevo.value.some(p => p.value === formNuevo.id_profesor))
        formNuevo.id_profesor = '';
    formNuevo.dias_extra = []; // resetear días al cambiar horario base
});

const abrirModal = (id_periodo = '') => {
    formNuevo.reset();
    formNuevo.id_periodo  = id_periodo;
    formNuevo.vacantes_max = 30;
    if (id_periodo) {
        const p = props.periodos.find(x => x.id_periodo === id_periodo);
        filtroCarreraModal.value = p?.id_carrera ?? '';
    } else {
        filtroCarreraModal.value = '';
    }
    showModal.value = true;
};

const feNuevo = ref({});

function validarNuevo() {
    const e = {};
    const ev = errEntero(formNuevo.vacantes_max, 'Las vacantes', 1, 500); if (ev) e.vacantes_max = ev;
    if (formNuevo.codigo_grupo !== '' && formNuevo.codigo_grupo !== null) {
        const ec = errCodigo(formNuevo.codigo_grupo, 'El código de grupo'); if (ec) e.codigo_grupo = ec;
    }
    feNuevo.value = e;
    return Object.keys(e).length === 0;
}

watch(() => [formNuevo.vacantes_max, formNuevo.codigo_grupo], () => {
    if (Object.keys(feNuevo.value).length) validarNuevo();
});

const guardarNuevo = () => {
    if (!validarNuevo()) return;
    formNuevo.post(route('director.grupos.store'), {
        onSuccess: () => { showModal.value = false; formNuevo.reset(); feNuevo.value = {}; },
    });
};

// ── Modal Editar ──────────────────────────────────────────────────────────────
const showEdit  = ref(false);
const grupoEdit = ref(null);
const formEdit  = useForm({
    vacantes_max:  30,
    codigo_grupo:  '',
    id_aula:       '',
    id_profesor:   '',
    dias_mantener: [],
    dias_agregar:  [],
});

const aulaEditSeleccionada = computed(() =>
    props.aulas.find(a => a.id_aula === formEdit.id_aula)
);
const aulaEditCapacidad = computed(() => aulaEditSeleccionada.value?.capacidad ?? 200);

function generarCodigoEdit() {
    const matCode = (grupoEdit.value?.materia_codigo ?? '').replace(/[-\s]/g, '');
    const aula = aulaEditSeleccionada.value;
    if (!matCode || !aula) return;
    const aulaPart = aula.nombre
        .replace(/^aula\s+/i, '')
        .replace(/[-\s]/g, '')
        .normalize('NFD').replace(/[̀-ͯ]/g, '');
    const base = (matCode + '-' + aulaPart).substring(0, 15);

    // Excluye el propio grupo y sus hermanos (mismo codigo original) para no auto-conflictuar
    const codigoOriginal = grupoEdit.value?.codigo_grupo;
    const codigosUsados = new Set(
        (props.periodos.find(p => p.id_periodo === grupoEdit.value?.id_periodo)?.grupos ?? [])
            .filter(g => g.codigo_grupo !== codigoOriginal)
            .map(g => g.codigo_grupo)
    );

    let candidato = base.substring(0, 20);
    if (!codigosUsados.has(candidato)) { formEdit.codigo_grupo = candidato; return; }

    const horario = props.horarios.find(h => h.id_horario === grupoEdit.value?.id_horario);
    if (horario?.hora_inicio) {
        const hora2 = horario.hora_inicio.substring(0, 2);
        const hora4 = horario.hora_inicio.replace(':', '').substring(0, 4);

        candidato = (base + '-' + hora2).substring(0, 20);
        if (!codigosUsados.has(candidato)) { formEdit.codigo_grupo = candidato; return; }

        candidato = (base.substring(0, 14) + '-' + hora4).substring(0, 20);
        if (!codigosUsados.has(candidato)) { formEdit.codigo_grupo = candidato; return; }
    }

    for (const letter of 'ABCDEFGHIJKLMNOP') {
        candidato = (base.substring(0, 18) + '-' + letter).substring(0, 20);
        if (!codigosUsados.has(candidato)) { formEdit.codigo_grupo = candidato; return; }
    }

    formEdit.codigo_grupo = '';
}

watch(() => formEdit.id_aula, (newId) => {
    const aula = props.aulas.find(a => a.id_aula === newId);
    if (aula) formEdit.vacantes_max = aula.capacidad;
    generarCodigoEdit();
    formEdit.dias_agregar = formEdit.dias_agregar.filter(id =>
        diasDisponiblesEdit.value.some(d => d.id_horario === id)
    );
});

watch(() => formEdit.id_profesor, () => {
    formEdit.dias_agregar = formEdit.dias_agregar.filter(id =>
        diasDisponiblesEdit.value.some(d => d.id_horario === id)
    );
});

const abrirEditar = (grupo) => {
    grupoEdit.value          = grupo;
    formEdit.vacantes_max    = grupo.vacantes_max;
    formEdit.codigo_grupo    = grupo.codigo_grupo ?? '';
    formEdit.id_aula         = grupo.id_aula;
    formEdit.id_profesor     = grupo.id_profesor;
    formEdit.dias_mantener   = [...(grupo.ids_oferta ?? [grupo.id_oferta])];
    formEdit.dias_agregar    = [];
    showEdit.value = true;
};

function toggleDiaMantener(idOferta) {
    const idx = formEdit.dias_mantener.indexOf(idOferta);
    if (idx === -1) {
        formEdit.dias_mantener.push(idOferta);
    } else if (formEdit.dias_mantener.length > 1) {
        formEdit.dias_mantener.splice(idx, 1);
    }
}

function toggleDiaAgregarEdit(idHorario) {
    const idx = formEdit.dias_agregar.indexOf(idHorario);
    if (idx === -1) formEdit.dias_agregar.push(idHorario);
    else formEdit.dias_agregar.splice(idx, 1);
}

const feEdit = ref({});

function validarEdit() {
    const e = {};
    const ev = errEntero(formEdit.vacantes_max, 'Las vacantes', 1, 500); if (ev) e.vacantes_max = ev;
    if (formEdit.codigo_grupo !== '' && formEdit.codigo_grupo !== null) {
        const ec = errCodigo(formEdit.codigo_grupo, 'El código de grupo'); if (ec) e.codigo_grupo = ec;
    }
    feEdit.value = e;
    return Object.keys(e).length === 0;
}

watch(() => [formEdit.vacantes_max, formEdit.codigo_grupo], () => {
    if (Object.keys(feEdit.value).length) validarEdit();
});

const guardarEdit = () => {
    if (!validarEdit()) return;
    formEdit.put(route('director.grupos.update', grupoEdit.value.id_oferta), {
        onSuccess: () => { showEdit.value = false; feEdit.value = {}; },
    });
};

// ── Modal Clonar Oferta ───────────────────────────────────────────────────────
const showClonar  = ref(false);
const formClonar  = useForm({ id_periodo_origen: '', id_periodo_destino: '' });

const optsPeriodosDestino = computed(() =>
    props.periodos
        .filter(p => p.id_periodo !== formClonar.id_periodo_origen)
        .map(p => ({
            value: p.id_periodo,
            label: p.nombre + (p.carrera_nombre ? ' · ' + p.carrera_nombre : ''),
        }))
);

watch(() => formClonar.id_periodo_origen, () => { formClonar.id_periodo_destino = ''; });
// Al cambiar carrera en modal clonar, resetear ambos períodos
watch(filtroCarreraClonar, () => { formClonar.id_periodo_origen = ''; formClonar.id_periodo_destino = ''; });

const gruposOrigen = computed(() => {
    if (!formClonar.id_periodo_origen) return 0;
    return props.periodos.find(p => p.id_periodo === formClonar.id_periodo_origen)?.grupos?.length ?? 0;
});

const abrirClonar = () => {
    filtroCarreraClonar.value  = '';
    soloVigentesClonar.value   = true;
    formClonar.reset();
    showClonar.value = true;
};

const guardarClonar = () => {
    formClonar.post(route('director.grupos.clonar'), {
        onSuccess: () => {
            showClonar.value          = false;
            formClonar.reset();
            filtroCarreraClonar.value = '';
            soloVigentesClonar.value  = true;
        },
    });
};

// ── Toggle / Eliminar ─────────────────────────────────────────────────────────
const toggleGrupo = (grupo) => {
    router.patch(route('director.grupos.toggle', grupo.id_oferta), {}, { preserveScroll: true });
};

const confirmarEliminar = ref(null);
const eliminarGrupo = () => {
    if (!confirmarEliminar.value) return;
    router.delete(route('director.grupos.destroy', confirmarEliminar.value.id_oferta), {
        onSuccess: () => { confirmarEliminar.value = null; },
    });
};

const confirmarVaciarPeriodo = ref(null);
const vaciarPeriodo = () => {
    if (!confirmarVaciarPeriodo.value) return;
    router.delete(route('director.grupos.destroyPeriodo', confirmarVaciarPeriodo.value.id_periodo), {
        onSuccess: () => { confirmarVaciarPeriodo.value = null; },
    });
};

// ── Helpers ───────────────────────────────────────────────────────────────────
const vacantesLibres = (g) => Math.max(0, g.vacantes_max - g.vacantes_ocupadas);

// Ordena días de lunes a sábado
const diasOrden = { lunes: 1, martes: 2, miercoles: 3, jueves: 4, viernes: 5, sabado: 6, domingo: 7 };

// Agrupa filas de grupos por codigo_grupo → muestra un solo registro por bloque diario
function gruposAgrupados(grupos) {
    const map = new Map();
    const sorted = [...grupos].sort((a, b) =>
        (diasOrden[a.dia_semana] ?? 9) - (diasOrden[b.dia_semana] ?? 9)
    );
    for (const g of sorted) {
        const key = g.codigo_grupo ?? ('__' + g.id_oferta);
        if (!map.has(key)) {
            map.set(key, { ...g, horarios: [], ids_oferta: [] });
        }
        const grp = map.get(key);
        grp.horarios.push({
            id_oferta:   g.id_oferta,
            dia_semana:  g.dia_semana,
            hora_inicio: g.hora_inicio,
            hora_fin:    g.hora_fin,
        });
        grp.ids_oferta.push(g.id_oferta);
    }
    return Array.from(map.values());
}

// ── Filtros y orden de grupos ─────────────────────────────────────────────────
const filtroBusqueda      = ref('');
const filtroMateriaGrupo  = ref('');
const filtroProfesorGrupo = ref('');
const filtroDiaGrupo      = ref('');
const sortByGrupo         = ref('codigo');  // 'codigo'|'materia'|'profesor'|'aula'|'horario'
const sortDirGrupo        = ref('asc');

const hayFiltrosGrupo = computed(() =>
    !!(filtroBusqueda.value || filtroMateriaGrupo.value || filtroProfesorGrupo.value || filtroDiaGrupo.value)
);

function limpiarFiltrosGrupo() {
    filtroBusqueda.value      = '';
    filtroMateriaGrupo.value  = '';
    filtroProfesorGrupo.value = '';
    filtroDiaGrupo.value      = '';
}

function toggleSortGrupo(campo) {
    if (sortByGrupo.value === campo) sortDirGrupo.value = sortDirGrupo.value === 'asc' ? 'desc' : 'asc';
    else { sortByGrupo.value = campo; sortDirGrupo.value = 'asc'; }
}

const optsMateriasFiltroGrupo = computed(() => {
    const seen = new Set();
    const opts = [];
    for (const p of periodosFiltrados.value)
        for (const g of p.grupos)
            if (!seen.has(g.id_materia)) {
                seen.add(g.id_materia);
                opts.push({ value: g.id_materia, label: (g.materia_codigo ?? '') + ' · ' + (g.materia_nombre ?? '') });
            }
    return opts.sort((a, b) => a.label.localeCompare(b.label));
});

const optsProfesoresFiltroGrupo = computed(() => {
    const seen = new Set();
    const opts = [];
    for (const p of periodosFiltrados.value)
        for (const g of p.grupos)
            if (g.id_profesor && !seen.has(g.id_profesor)) {
                seen.add(g.id_profesor);
                opts.push({ value: g.id_profesor, label: g.profesor_nombre ?? '—' });
            }
    return opts.sort((a, b) => a.label.localeCompare(b.label));
});

const diasFiltroOpts = [
    { value: 'lunes',     label: 'Lun' },
    { value: 'martes',    label: 'Mar' },
    { value: 'miercoles', label: 'Mié' },
    { value: 'jueves',    label: 'Jue' },
    { value: 'viernes',   label: 'Vie' },
    { value: 'sabado',    label: 'Sáb' },
    { value: 'domingo',   label: 'Dom' },
];

function gruposFiltradosOrdenados(grupos) {
    let lista = gruposAgrupados(grupos);

    if (filtroBusqueda.value) {
        const q = filtroBusqueda.value.toLowerCase();
        lista = lista.filter(g =>
            (g.codigo_grupo    ?? '').toLowerCase().includes(q) ||
            (g.materia_nombre  ?? '').toLowerCase().includes(q) ||
            (g.materia_codigo  ?? '').toLowerCase().includes(q) ||
            (g.profesor_nombre ?? '').toLowerCase().includes(q) ||
            (g.aula_nombre     ?? '').toLowerCase().includes(q)
        );
    }
    if (filtroMateriaGrupo.value)
        lista = lista.filter(g => String(g.id_materia) === String(filtroMateriaGrupo.value));
    if (filtroProfesorGrupo.value)
        lista = lista.filter(g => String(g.id_profesor) === String(filtroProfesorGrupo.value));
    if (filtroDiaGrupo.value)
        lista = lista.filter(g => g.horarios.some(h => h.dia_semana === filtroDiaGrupo.value));

    const dir = sortDirGrupo.value === 'asc' ? 1 : -1;
    lista.sort((a, b) => {
        let va = '', vb = '';
        if (sortByGrupo.value === 'materia')  { va = a.materia_nombre  ?? ''; vb = b.materia_nombre  ?? ''; }
        else if (sortByGrupo.value === 'profesor') { va = a.profesor_nombre ?? ''; vb = b.profesor_nombre ?? ''; }
        else if (sortByGrupo.value === 'aula')     { va = a.aula_nombre     ?? ''; vb = b.aula_nombre     ?? ''; }
        else if (sortByGrupo.value === 'horario')  { va = a.hora_inicio     ?? ''; vb = b.hora_inicio     ?? ''; }
        else                                       { va = a.codigo_grupo    ?? ''; vb = b.codigo_grupo    ?? ''; }
        return va.localeCompare(vb) * dir;
    });
    return lista;
}

const tipoBadge = (tipo) => {
    const map = { semestral: '#6366f1', mensual: '#f59e0b', anual: '#10b981', intensivo: '#ef4444' };
    return map[tipo] ?? '#6b7280';
};

const fmtFecha = (f) => {
    if (!f) return '';
    return new Date(f + 'T12:00:00').toLocaleDateString('es-BO', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <AdminLayout>
        <template #header>
            <h1 class="text-lg font-semibold truncate" style="color: var(--text-color);">
                Grupos / Oferta Académica
            </h1>
        </template>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div v-for="stat in [
                { label: 'Total grupos', value: totalGrupos,   color: '#6366f1' },
                { label: 'Activos',      value: gruposActivos, color: '#10b981' },
                { label: 'Con vacantes', value: conVacantes,   color: '#f59e0b' },
            ]" :key="stat.label"
                class="rounded-xl p-4 border"
                style="background-color: var(--card-bg); border-color: var(--border-color);">
                <p class="text-2xl font-bold" :style="{ color: stat.color }">{{ stat.value }}</p>
                <p class="text-xs mt-0.5" style="color: var(--text-secondary);">{{ stat.label }}</p>
            </div>
        </div>

        <!-- Filtros + acciones -->
        <div class="flex items-center gap-3 mb-4 flex-wrap">
            <div class="flex-1 min-w-44 max-w-xs">
                <ComboSelect
                    v-model="filtroCarrera"
                    :options="optsCarrerasFiltro"
                    placeholder="Todas las carreras"
                    emptyLabel="Todas las carreras" />
            </div>
            <!-- Toggle solo activos -->
            <label class="flex items-center gap-2 cursor-pointer select-none text-sm shrink-0"
                   style="color: var(--text-secondary);">
                <span class="relative inline-block w-9 h-5">
                    <input type="checkbox" v-model="soloActivos" class="sr-only peer" />
                    <span class="block w-full h-full rounded-full transition peer-checked:opacity-100"
                          :style="soloActivos ? 'background-color: var(--primary-color);' : 'background-color: var(--border-color);'"></span>
                    <span class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform"
                          :style="soloActivos ? 'transform:translateX(16px)' : ''"></span>
                </span>
                Solo vigentes
            </label>
            <p v-if="filtroCarrera" class="text-xs shrink-0" style="color: var(--text-secondary);">
                <button @click="filtroCarrera = ''" class="underline" style="color: var(--primary-color);">Limpiar filtro</button>
            </p>
            <div v-if="canEdit" class="ml-auto flex items-center gap-2 shrink-0">
                <button @click="abrirClonar()"
                    class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold border transition"
                    style="border-color: var(--primary-color); color: var(--primary-color); background: transparent;">
                    📋 Clonar Oferta
                </button>
                <button @click="abrirModal()"
                    class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-opacity hover:opacity-80"
                    style="background-color: var(--primary-color); color: var(--primary-text);">
                    + Nuevo Grupo
                </button>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success"
            class="mb-4 rounded-lg px-4 py-3 text-sm font-medium"
            style="background-color: color-mix(in srgb,#10b981 15%,transparent); color:#10b981; border:1px solid color-mix(in srgb,#10b981 30%,transparent);">
            {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.errors?.grupo"
            class="mb-4 rounded-lg px-4 py-3 text-sm font-medium"
            style="background-color: color-mix(in srgb,#ef4444 12%,transparent); color:#ef4444; border:1px solid color-mix(in srgb,#ef4444 30%,transparent);">
            {{ $page.props.errors.grupo }}
        </div>

        <!-- ── Filtros de grupos ── -->
        <div class="rounded-xl border mb-4 px-4 py-3"
             style="background-color: var(--card-bg); border-color: var(--border-color);">
            <div class="flex flex-wrap gap-2 items-center">
                <!-- Búsqueda libre -->
                <div class="relative flex-1 min-w-44">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none"
                          style="color: var(--text-secondary);">🔍</span>
                    <input v-model="filtroBusqueda" type="text"
                           placeholder="Buscar código, materia, profesor, aula…"
                           class="w-full pl-7 pr-3 py-1.5 rounded-lg border text-sm outline-none"
                           style="background-color: var(--bg-color); border-color: var(--border-color); color: var(--text-color);" />
                </div>
                <!-- Materia -->
                <select v-model="filtroMateriaGrupo"
                        class="px-2 py-1.5 rounded-lg border text-xs outline-none min-w-36"
                        style="background-color: var(--bg-color); border-color: var(--border-color); color: var(--text-color);">
                    <option value="">Todas las materias</option>
                    <option v-for="m in optsMateriasFiltroGrupo" :key="m.value" :value="m.value">{{ m.label }}</option>
                </select>
                <!-- Profesor -->
                <select v-model="filtroProfesorGrupo"
                        class="px-2 py-1.5 rounded-lg border text-xs outline-none min-w-36"
                        style="background-color: var(--bg-color); border-color: var(--border-color); color: var(--text-color);">
                    <option value="">Todos los profesores</option>
                    <option v-for="p in optsProfesoresFiltroGrupo" :key="p.value" :value="p.value">{{ p.label }}</option>
                </select>
                <!-- Día -->
                <div class="flex gap-1">
                    <button v-for="d in diasFiltroOpts" :key="d.value"
                            type="button"
                            @click="filtroDiaGrupo = filtroDiaGrupo === d.value ? '' : d.value"
                            class="px-2 py-1 rounded text-[11px] font-medium border transition-all"
                            :style="filtroDiaGrupo === d.value
                                ? 'background:var(--primary-color);color:var(--primary-text);border-color:var(--primary-color);'
                                : 'background:transparent;color:var(--text-secondary);border-color:var(--border-color);'">
                        {{ d.label }}
                    </button>
                </div>
                <!-- Limpiar -->
                <button v-if="hayFiltrosGrupo" type="button"
                        @click="limpiarFiltrosGrupo()"
                        class="text-xs px-3 py-1.5 rounded-lg border font-medium shrink-0"
                        style="border-color: var(--border-color); color: var(--text-secondary);">
                    ✕ Limpiar
                </button>
            </div>
        </div>

        <!-- Sin períodos -->
        <div v-if="periodosPorCarrera.length === 0"
            class="rounded-xl border p-10 text-center"
            style="background-color: var(--card-bg); border-color: var(--border-color);">
            <p class="text-4xl mb-3">📅</p>
            <template v-if="props.periodos.length === 0">
                <p class="font-medium mb-1" style="color: var(--text-color);">Sin períodos configurados</p>
                <p class="text-sm" style="color: var(--text-secondary);">
                    Primero crea períodos académicos en <strong>Períodos Académicos</strong>.
                </p>
            </template>
            <template v-else>
                <p class="font-medium mb-1" style="color: var(--text-color);">Sin períodos vigentes</p>
                <p class="text-sm mb-3" style="color: var(--text-secondary);">
                    No hay períodos activos en la fecha actual ni con grupos creados.
                </p>
                <button @click="soloActivos = false"
                    class="text-sm font-medium underline"
                    style="color: var(--primary-color);">
                    Mostrar todos los períodos
                </button>
            </template>
        </div>

        <!-- Lista agrupada por carrera -->
        <div v-else class="space-y-3">
            <div v-for="carrera in periodosPorCarrera" :key="carrera.id_carrera"
                 class="rounded-xl border overflow-hidden"
                 style="background-color: var(--card-bg); border-color: var(--border-color);">

                <!-- ── Cabecera carrera (accordion externo) ── -->
                <div class="flex items-center justify-between px-5 py-3 cursor-pointer select-none transition-colors"
                     style="background-color: var(--bg-color);"
                     :style="carrerasAbiertas[carrera.id_carrera] ? 'border-bottom:1px solid var(--border-color);' : ''"
                     @click="toggleCarreraGrupos(carrera.id_carrera)">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-xs shrink-0 transition-transform duration-200"
                              :style="carrerasAbiertas[carrera.id_carrera] ? 'transform:rotate(90deg);' : ''"
                              style="color: var(--text-secondary);">▶</span>
                        <span class="font-semibold text-sm truncate" style="color: var(--text-color);">
                            {{ carrera.nombre }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <span class="text-xs" style="color: var(--text-secondary);">
                            {{ carrera.periodos.length }} período(s)
                        </span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                              :style="carrera.totalGrupos > 0
                                ? 'background-color:color-mix(in srgb,var(--primary-color) 12%,transparent);color:var(--primary-color);'
                                : 'background-color:color-mix(in srgb,#6b7280 12%,transparent);color:#6b7280;'">
                            {{ carrera.totalGrupos }} grupo{{ carrera.totalGrupos !== 1 ? 's' : '' }}
                        </span>
                    </div>
                </div>

                <!-- ── Períodos de esta carrera ── -->
                <div v-show="carrerasAbiertas[carrera.id_carrera] || hayFiltrosGrupo" class="space-y-2 p-3">
                    <div v-for="periodo in carrera.periodos" :key="periodo.id_periodo"
                        class="rounded-lg border overflow-hidden"
                        style="border-color: var(--border-color); background-color: var(--card-bg);">

                <!-- Header período -->
                <button @click="togglePeriodo(periodo.id_periodo)"
                    class="w-full flex items-center justify-between px-5 py-4 text-left transition-colors"
                    :style="periodosAbiertos[periodo.id_periodo]
                        ? 'background-color: color-mix(in srgb, var(--primary-color) 8%, transparent);'
                        : ''">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <span class="shrink-0 text-[10px] font-bold uppercase px-2 py-0.5 rounded"
                            :style="{ backgroundColor: tipoBadge(periodo.tipo_periodo) + '22', color: tipoBadge(periodo.tipo_periodo) }">
                            {{ periodo.tipo_periodo }}
                        </span>
                        <div class="min-w-0">
                            <p class="font-semibold text-sm truncate" style="color: var(--text-color);">
                                {{ periodo.nombre }}
                            </p>
                            <p class="text-xs truncate" style="color: var(--text-secondary);">
                                📚 {{ fmtFecha(periodo.fecha_inicio) }} — {{ fmtFecha(periodo.fecha_fin) }}
                                <span v-if="periodo.carrera_nombre"> · {{ periodo.carrera_nombre }}</span>
                                <span v-if="periodo.nivel_nombre"> · {{ periodo.nivel_nombre }}</span>
                            </p>
                            <p class="text-xs truncate"
                               :style="periodo.fecha_inicio_inscripcion
                                   ? 'color: #8b5cf6;'
                                   : 'color: var(--text-secondary); opacity: 0.55;'">
                                <template v-if="periodo.fecha_inicio_inscripcion">
                                    📝 Inscripciones: {{ fmtFecha(periodo.fecha_inicio_inscripcion) }} → {{ fmtFecha(periodo.fecha_fin_inscripcion) }}
                                </template>
                                <template v-else>
                                    📝 Sin ventana de inscripciones definida
                                </template>
                            </p>
                        </div>
                        <span v-if="!periodo.activo"
                            class="shrink-0 text-[10px] font-medium px-2 py-0.5 rounded"
                            style="background-color: color-mix(in srgb,#6b7280 15%,transparent); color:#6b7280;">
                            Inactivo
                        </span>
                    </div>
                    <div class="flex items-center gap-3 shrink-0 ml-3">
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full"
                            style="background-color: color-mix(in srgb,var(--primary-color) 12%,transparent); color: var(--primary-color);">
                            {{ gruposFiltradosOrdenados(periodo.grupos).length }}
                            <template v-if="hayFiltrosGrupo && gruposFiltradosOrdenados(periodo.grupos).length !== gruposAgrupados(periodo.grupos).length">
                                /{{ gruposAgrupados(periodo.grupos).length }}
                            </template>
                            grupo{{ gruposFiltradosOrdenados(periodo.grupos).length !== 1 ? 's' : '' }}
                        </span>
                        <button v-if="canEdit" @click.stop="abrirModal(periodo.id_periodo)"
                            class="text-xs px-2.5 py-1 rounded-md font-medium transition-opacity hover:opacity-80"
                            style="background-color: var(--primary-color); color: var(--primary-text);">
                            + Grupo
                        </button>
                        <button v-if="gruposAgrupados(periodo.grupos).length > 0"
                            @click.stop="confirmarVaciarPeriodo = periodo"
                            class="text-xs px-2.5 py-1 rounded-md font-medium border transition-opacity hover:opacity-70"
                            style="border-color:#ef4444; color:#ef4444; background:transparent;"
                            title="Eliminar todos los grupos de este período">
                            🗑️ Vaciar
                        </button>
                        <span class="text-xs opacity-40 transition-transform duration-200"
                            :style="periodosAbiertos[periodo.id_periodo] ? 'transform:rotate(180deg)' : ''">▾</span>
                    </div>
                </button>

                <!-- Tabla de grupos -->
                <div v-show="periodosAbiertos[periodo.id_periodo] || hayFiltrosGrupo">
                    <div v-if="periodo.grupos.length === 0"
                        class="px-6 py-6 text-center border-t"
                        style="border-color: var(--border-color);">
                        <p class="text-sm" style="color: var(--text-secondary);">Sin grupos en este período.</p>
                    </div>

                    <div v-else class="overflow-x-auto border-t" style="border-color: var(--border-color);">
                        <table class="w-full text-sm">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-color); background-color: color-mix(in srgb, var(--text-color) 3%, transparent);">
                                    <th @click="toggleSortGrupo('codigo')"
                                        class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide cursor-pointer select-none hover:opacity-70 transition-opacity"
                                        style="color: var(--text-secondary);">
                                        Código <span v-if="sortByGrupo==='codigo'">{{ sortDirGrupo==='asc'?'↑':'↓' }}</span>
                                    </th>
                                    <th @click="toggleSortGrupo('materia')"
                                        class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide cursor-pointer select-none hover:opacity-70 transition-opacity"
                                        style="color: var(--text-secondary);">
                                        Materia <span v-if="sortByGrupo==='materia'">{{ sortDirGrupo==='asc'?'↑':'↓' }}</span>
                                    </th>
                                    <th @click="toggleSortGrupo('aula')"
                                        class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide cursor-pointer select-none hover:opacity-70 transition-opacity"
                                        style="color: var(--text-secondary);">
                                        Aula <span v-if="sortByGrupo==='aula'">{{ sortDirGrupo==='asc'?'↑':'↓' }}</span>
                                    </th>
                                    <th @click="toggleSortGrupo('profesor')"
                                        class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide cursor-pointer select-none hover:opacity-70 transition-opacity"
                                        style="color: var(--text-secondary);">
                                        Profesor <span v-if="sortByGrupo==='profesor'">{{ sortDirGrupo==='asc'?'↑':'↓' }}</span>
                                    </th>
                                    <th @click="toggleSortGrupo('horario')"
                                        class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide cursor-pointer select-none hover:opacity-70 transition-opacity"
                                        style="color: var(--text-secondary);">
                                        Horario <span v-if="sortByGrupo==='horario'">{{ sortDirGrupo==='asc'?'↑':'↓' }}</span>
                                    </th>
                                    <th class="text-center px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Vacantes</th>
                                    <th class="text-center px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Estado</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="hayFiltrosGrupo && gruposFiltradosOrdenados(periodo.grupos).length === 0 && gruposAgrupados(periodo.grupos).length > 0">
                                    <td colspan="8" class="px-4 py-4 text-center text-xs" style="color: var(--text-secondary);">
                                        Sin grupos que coincidan con el filtro en este período.
                                    </td>
                                </tr>
                                <tr v-for="grupo in gruposFiltradosOrdenados(periodo.grupos)" :key="grupo.id_oferta"
                                    class="transition-colors"
                                    style="border-bottom: 1px solid var(--border-color);"
                                    onmouseover="this.style.backgroundColor='color-mix(in srgb,var(--text-color) 3%,transparent)'"
                                    onmouseout="this.style.backgroundColor='transparent'">

                                    <td class="px-4 py-3">
                                        <code class="text-xs font-bold px-1.5 py-0.5 rounded"
                                            style="background-color: color-mix(in srgb,var(--primary-color) 12%,transparent); color: var(--primary-color);">
                                            {{ grupo.codigo_grupo }}
                                        </code>
                                    </td>

                                    <td class="px-4 py-3">
                                        <p class="font-medium text-xs" style="color: var(--text-color);">{{ grupo.materia_nombre }}</p>
                                        <p class="text-[11px]" style="color: var(--text-secondary);">{{ grupo.materia_codigo }}</p>
                                    </td>

                                    <td class="px-4 py-3">
                                        <p class="text-xs" style="color: var(--text-color);">{{ grupo.aula_nombre }}</p>
                                        <p class="text-[11px]" style="color: var(--text-secondary);">Cap. {{ grupo.aula_capacidad }}</p>
                                    </td>

                                    <td class="px-4 py-3">
                                        <p class="text-xs" style="color: var(--text-color);">{{ grupo.profesor_nombre }}</p>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1 mb-0.5">
                                            <span v-for="h in grupo.horarios" :key="h.id_oferta"
                                                  class="px-1.5 py-0.5 rounded text-[10px] capitalize font-medium"
                                                  style="background: color-mix(in srgb,var(--primary-color) 12%,transparent); color: var(--primary-color);">
                                                {{ h.dia_semana.slice(0,3) }}
                                            </span>
                                        </div>
                                        <p class="text-[11px]" style="color: var(--text-secondary);">{{ grupo.hora_inicio }}–{{ grupo.hora_fin }}</p>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs font-bold"
                                            :style="{ color: vacantesLibres(grupo) === 0 ? '#ef4444' : vacantesLibres(grupo) <= 5 ? '#f59e0b' : '#10b981' }">
                                            {{ vacantesLibres(grupo) }}
                                        </span>
                                        <span class="text-[11px]" style="color: var(--text-secondary);">/{{ grupo.vacantes_max }}</span>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full"
                                            :style="grupo.activo
                                                ? 'background-color: color-mix(in srgb,#10b981 15%,transparent); color:#10b981;'
                                                : 'background-color: color-mix(in srgb,#6b7280 15%,transparent); color:#6b7280;'">
                                            {{ grupo.activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <a :href="route('director.grupos.inscritos', grupo.id_oferta)"
                                               class="p-1.5 rounded text-xs" title="Ver inscritos"
                                               style="color: var(--text-secondary);"
                                               onmouseover="this.style.color='#10b981'"
                                               onmouseout="this.style.color='var(--text-secondary)'">👥</a>
                                            <a v-if="['propietario', 'director', 'secretaria'].includes(usePage().props.auth?.user?.role)"
                                               :href="route('director.grupos.notas', grupo.id_oferta)"
                                               class="p-1.5 rounded text-xs" title="Administrar notas"
                                               style="color: var(--text-secondary);"
                                               onmouseover="this.style.color='#6366f1'"
                                               onmouseout="this.style.color='var(--text-secondary)'">📊</a>
                                            <template v-if="canEdit">
                                                <button @click="abrirEditar(grupo)" class="p-1.5 rounded text-xs" title="Editar"
                                                    style="color: var(--text-secondary);"
                                                    onmouseover="this.style.color='var(--primary-color)'"
                                                    onmouseout="this.style.color='var(--text-secondary)'">✏️</button>
                                                <button @click="toggleGrupo(grupo)" class="p-1.5 rounded text-xs"
                                                    :title="grupo.activo ? 'Desactivar' : 'Activar'"
                                                    style="color: var(--text-secondary);"
                                                    onmouseover="this.style.color='#f59e0b'"
                                                    onmouseout="this.style.color='var(--text-secondary)'">
                                                    {{ grupo.activo ? '🔒' : '🔓' }}
                                                </button>
                                                <button @click="confirmarEliminar = grupo" class="p-1.5 rounded text-xs" title="Eliminar"
                                                    style="color: var(--text-secondary);"
                                                    onmouseover="this.style.color='#ef4444'"
                                                    onmouseout="this.style.color='var(--text-secondary)'">🗑️</button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </div>
                    </div> <!-- fin período -->
                </div> <!-- fin lista períodos carrera -->
            </div> <!-- fin carrera -->
        </div>

        <!-- ══ MODAL: Nuevo Grupo ══════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background-color: rgba(0,0,0,0.5);"
                @click.self="showModal = false">
                <div class="w-full max-w-lg rounded-2xl border shadow-xl overflow-y-auto max-h-[90vh]"
                    style="background-color: var(--card-bg); border-color: var(--border-color);">
                    <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color: var(--border-color);">
                        <h2 class="font-bold text-base" style="color: var(--text-color);">Nuevo Grupo</h2>
                        <button @click="showModal = false" class="text-lg leading-none" style="color: var(--text-secondary);">✕</button>
                    </div>

                    <div class="px-6 py-5 space-y-4">
                        <div v-if="formNuevo.errors.grupo"
                            class="rounded-lg px-3 py-2 text-xs font-medium"
                            style="background-color: color-mix(in srgb,#ef4444 12%,transparent); color:#ef4444; border:1px solid color-mix(in srgb,#ef4444 30%,transparent);">
                            {{ formNuevo.errors.grupo }}
                        </div>

                        <!-- Carrera + toggle vigentes (pre-filtro) -->
                        <div class="flex gap-3 items-end">
                            <div class="flex-1">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Carrera</label>
                                <select v-model="filtroCarreraModal"
                                    class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                    style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);">
                                    <option value="">— Todas las carreras —</option>
                                    <option v-for="c in optsCarrerasFiltro" :key="c.value" :value="c.value">{{ c.label }}</option>
                                </select>
                            </div>
                            <label class="flex items-center gap-1.5 cursor-pointer select-none pb-2 shrink-0">
                                <span class="relative inline-block w-8 h-4">
                                    <input type="checkbox" v-model="soloVigentesModal" class="sr-only peer" />
                                    <span class="block w-full h-full rounded-full transition"
                                          :style="soloVigentesModal ? 'background-color:var(--primary-color)' : 'background-color:var(--border-color)'"></span>
                                    <span class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full shadow transition-transform"
                                          :style="soloVigentesModal ? 'transform:translateX(16px)' : ''"></span>
                                </span>
                                <span class="text-xs" style="color: var(--text-secondary);">{{ anoActual }}</span>
                            </label>
                        </div>

                        <!-- Período -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Período *</label>
                            <ComboSelect v-model="formNuevo.id_periodo" :options="optsPeriodosModalFiltrados"
                                placeholder="— Seleccionar período —" emptyLabel="" />
                            <p v-if="formNuevo.errors.id_periodo" class="text-xs mt-1" style="color:#ef4444;">{{ formNuevo.errors.id_periodo }}</p>
                        </div>

                        <!-- Materia (filtrada por malla de la carrera del período) -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                Materia *
                                <span v-if="periodoDelModal?.id_carrera && mallaPorCarrera[periodoDelModal.id_carrera]?.length"
                                    class="font-normal opacity-60 ml-1">
                                    — {{ mallaPorCarrera[periodoDelModal.id_carrera].length }} en malla de {{ periodoDelModal.carrera_nombre }}
                                </span>
                            </label>
                            <ComboSelect v-model="formNuevo.id_materia" :options="optsMateriasFiltradas"
                                placeholder="— Seleccionar materia —" emptyLabel="" />
                            <p v-if="formNuevo.errors.id_materia" class="text-xs mt-1" style="color:#ef4444;">{{ formNuevo.errors.id_materia }}</p>
                        </div>

                        <!-- Aula -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                Aula *
                                <span v-if="formNuevo.id_horario && optsAulasNuevo.length < optsAulas.length"
                                      class="font-normal ml-1" style="color: var(--primary-color);">
                                    — {{ optsAulasNuevo.length }}/{{ optsAulas.length }} disponibles
                                </span>
                            </label>
                            <ComboSelect v-model="formNuevo.id_aula" :options="optsAulasNuevo"
                                placeholder="— Seleccionar aula —" emptyLabel="" />
                            <p v-if="formNuevo.errors.id_aula" class="text-xs mt-1" style="color:#ef4444;">{{ formNuevo.errors.id_aula }}</p>
                        </div>

                        <!-- Profesor -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                Profesor *
                                <span v-if="formNuevo.id_horario && optsProfesoresNuevo.length < optsProfesores.length"
                                      class="font-normal ml-1" style="color: var(--primary-color);">
                                    — {{ optsProfesoresNuevo.length }}/{{ optsProfesores.length }} disponibles
                                </span>
                            </label>
                            <ComboSelect v-model="formNuevo.id_profesor" :options="optsProfesoresNuevo"
                                placeholder="— Seleccionar profesor —" emptyLabel="" />
                            <p v-if="formNuevo.errors.id_profesor" class="text-xs mt-1" style="color:#ef4444;">{{ formNuevo.errors.id_profesor }}</p>
                        </div>

                        <!-- Horario -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                Horario *
                                <span v-if="(formNuevo.id_aula || formNuevo.id_profesor) && optsHorariosNuevo.length < optsHorarios.length"
                                      class="font-normal ml-1" style="color: var(--primary-color);">
                                    — {{ optsHorariosNuevo.length }}/{{ optsHorarios.length }} disponibles
                                </span>
                            </label>
                            <ComboSelect v-model="formNuevo.id_horario" :options="optsHorariosNuevo"
                                placeholder="— Seleccionar horario —" emptyLabel="" />
                            <p v-if="formNuevo.errors.id_horario" class="text-xs mt-1" style="color:#ef4444;">{{ formNuevo.errors.id_horario }}</p>
                        </div>

                        <!-- Replicar a otros días (misma hora) -->
                        <div v-if="horarioBaseNuevo && diasCompatiblesNuevo.length > 0"
                             class="rounded-lg border p-3"
                             style="border-color: color-mix(in srgb,var(--primary-color) 30%,transparent); background-color: color-mix(in srgb,var(--primary-color) 5%,transparent);">
                            <p class="text-xs font-semibold mb-2" style="color: var(--text-color);">
                                Replicar a otros días
                                <span class="font-normal ml-1" style="color: var(--text-secondary);">
                                    (misma hora {{ horarioBaseNuevo.hora_inicio }}–{{ horarioBaseNuevo.hora_fin }})
                                </span>
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                <!-- Día base: no clickable -->
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold"
                                      style="background-color: var(--primary-color); color: var(--primary-text);">
                                    {{ diasAbrev[horarioBaseNuevo.dia_semana] ?? horarioBaseNuevo.dia_semana }} ✓
                                </span>
                                <!-- Días disponibles -->
                                <button v-for="d in diasCompatiblesNuevo" :key="d.id_horario"
                                        type="button"
                                        @click="toggleDiaExtra(d.id_horario)"
                                        class="px-2.5 py-1 rounded-full text-xs font-medium border transition-all"
                                        :style="formNuevo.dias_extra.includes(d.id_horario)
                                            ? 'background-color:var(--primary-color);color:var(--primary-text);border-color:var(--primary-color);'
                                            : 'background-color:transparent;color:var(--text-secondary);border-color:var(--border-color);'">
                                    {{ diasAbrev[d.dia_semana] ?? d.dia_semana }}
                                </button>
                            </div>
                            <p v-if="formNuevo.dias_extra.length > 0"
                               class="text-[11px] mt-2 font-medium"
                               style="color: var(--primary-color);">
                                Se crearán {{ formNuevo.dias_extra.length + 1 }} grupos en total
                            </p>
                        </div>

                        <!-- Vacantes + Código -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                    Vacantes máx. *
                                    <span v-if="aulaSeleccionada" class="font-normal opacity-60 ml-1">(máx. {{ aulaCapacidad }})</span>
                                </label>
                                <input v-model.number="formNuevo.vacantes_max" type="number" min="1"
                                    :max="aulaCapacidad"
                                    class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                    style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);" />
                                <p v-if="formNuevo.vacantes_max > aulaCapacidad" class="text-xs mt-1" style="color:#ef4444;">
                                    Supera capacidad del aula ({{ aulaCapacidad }})
                                </p>
                                <p v-else-if="feNuevo.vacantes_max || formNuevo.errors.vacantes_max" class="text-xs mt-1" style="color:#ef4444;">{{ feNuevo.vacantes_max || formNuevo.errors.vacantes_max }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                    Código grupo <span class="font-normal opacity-60">(opcional)</span>
                                </label>
                                <input v-model="formNuevo.codigo_grupo" type="text" maxlength="20"
                                    placeholder="Ej: PROG101-A"
                                    class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                    style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);" />
                                <p v-if="feNuevo.codigo_grupo || formNuevo.errors.codigo_grupo" class="text-xs mt-1" style="color:#ef4444;">{{ feNuevo.codigo_grupo || formNuevo.errors.codigo_grupo }}</p>
                                <p v-else class="text-[11px] mt-0.5 opacity-50" style="color: var(--text-secondary);">Auto: G-{ID} si vacío</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 px-6 py-4 border-t" style="border-color: var(--border-color);">
                        <button @click="showModal = false"
                            class="px-4 py-2 rounded-lg text-sm font-medium border"
                            style="border-color: var(--border-color); color: var(--text-secondary);">
                            Cancelar
                        </button>
                        <button @click="guardarNuevo" :disabled="formNuevo.processing"
                            class="px-5 py-2 rounded-lg text-sm font-medium transition-opacity"
                            :style="formNuevo.processing ? 'opacity:0.6;' : ''"
                            style="background-color: var(--primary-color); color: var(--primary-text);">
                            {{ formNuevo.processing ? 'Guardando...' : 'Crear Grupo' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ══ MODAL: Clonar Oferta (Maestro de Oferta) ══════════════════════ -->
        <Teleport to="body">
            <div v-if="showClonar"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background-color: rgba(0,0,0,0.5);"
                @click.self="showClonar = false; filtroCarreraClonar = ''">
                <div class="w-full max-w-md rounded-2xl border shadow-xl flex flex-col"
                    style="background-color: var(--card-bg); border-color: var(--border-color); max-height: 92vh;">
                    <div class="flex items-center justify-between px-6 py-4 border-b shrink-0" style="border-color: var(--border-color);">
                        <div>
                            <h2 class="font-bold text-base" style="color: var(--text-color);">📋 Clonar Oferta Académica</h2>
                            <p class="text-xs mt-0.5" style="color: var(--text-secondary);">
                                Copia todos los grupos de un período a otro. Solo modifica los que cambien.
                            </p>
                        </div>
                        <button @click="showClonar = false; filtroCarreraClonar = ''" class="text-lg leading-none" style="color: var(--text-secondary);">✕</button>
                    </div>

                    <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4">
                        <div v-if="formClonar.errors.grupo"
                            class="rounded-lg px-3 py-2 text-xs font-medium"
                            style="background-color: color-mix(in srgb,#ef4444 12%,transparent); color:#ef4444; border:1px solid color-mix(in srgb,#ef4444 30%,transparent);">
                            {{ formClonar.errors.grupo }}
                        </div>

                        <!-- Carrera + toggle vigentes (pre-filtro) -->
                        <div class="flex gap-3 items-end">
                            <div class="flex-1">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Carrera</label>
                                <select v-model="filtroCarreraClonar"
                                    class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                    style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);">
                                    <option value="">— Todas las carreras —</option>
                                    <option v-for="c in optsCarrerasFiltro" :key="c.value" :value="c.value">{{ c.label }}</option>
                                </select>
                            </div>
                            <label class="flex items-center gap-1.5 cursor-pointer select-none pb-2 shrink-0">
                                <span class="relative inline-block w-8 h-4">
                                    <input type="checkbox" v-model="soloVigentesClonar" class="sr-only peer" />
                                    <span class="block w-full h-full rounded-full transition"
                                          :style="soloVigentesClonar ? 'background-color:var(--primary-color)' : 'background-color:var(--border-color)'"></span>
                                    <span class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full shadow transition-transform"
                                          :style="soloVigentesClonar ? 'transform:translateX(16px)' : ''"></span>
                                </span>
                                <span class="text-xs" style="color: var(--text-secondary);">{{ anoActual }}</span>
                            </label>
                        </div>

                        <!-- Período origen -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                Período plantilla (origen) *
                            </label>
                            <ComboSelect v-model="formClonar.id_periodo_origen" :options="optsPeriodosOrigenFiltrados"
                                placeholder="— Seleccionar período origen —" emptyLabel="" />
                            <p v-if="gruposOrigen > 0" class="text-[11px] mt-1" style="color: var(--primary-color);">
                                {{ gruposOrigen }} grupo(s) disponibles para clonar
                            </p>
                            <p v-else-if="formClonar.id_periodo_origen" class="text-[11px] mt-1" style="color: #f59e0b;">
                                Este período no tiene grupos aún.
                            </p>
                        </div>

                        <!-- Período destino -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                Período destino *
                            </label>
                            <ComboSelect v-model="formClonar.id_periodo_destino" :options="optsPeriodosDestinoClonarFiltrados"
                                placeholder="— Seleccionar período destino —" emptyLabel="" />
                        </div>

                        <!-- Info -->
                        <div v-if="formClonar.id_periodo_origen && formClonar.id_periodo_destino"
                            class="rounded-lg p-3 border text-xs"
                            style="background-color: color-mix(in srgb,var(--primary-color) 6%,transparent); border-color: color-mix(in srgb,var(--primary-color) 25%,transparent); color: var(--text-secondary);">
                            Se copiarán <strong style="color:var(--primary-color);">{{ gruposOrigen }}</strong> grupo(s)
                            con las mismas materias, profesores, aulas y horarios.
                            Los grupos con conflictos de horario serán omitidos.
                            Luego puedes editar individualmente los que cambien.
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 px-6 py-4 border-t shrink-0" style="border-color: var(--border-color);">
                        <button @click="showClonar = false; filtroCarreraClonar = ''"
                            class="px-4 py-2 rounded-lg text-sm font-medium border"
                            style="border-color: var(--border-color); color: var(--text-secondary);">
                            Cancelar
                        </button>
                        <button @click="guardarClonar"
                            :disabled="formClonar.processing || !formClonar.id_periodo_origen || !formClonar.id_periodo_destino || gruposOrigen === 0"
                            class="px-5 py-2 rounded-lg text-sm font-medium transition-opacity"
                            :style="(formClonar.processing || !formClonar.id_periodo_origen || !formClonar.id_periodo_destino || gruposOrigen === 0) ? 'opacity:0.5;' : ''"
                            style="background-color: var(--primary-color); color: var(--primary-text);">
                            {{ formClonar.processing ? 'Clonando...' : `Clonar ${gruposOrigen} grupo(s)` }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ══ MODAL: Editar Grupo ════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showEdit"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background-color: rgba(0,0,0,0.5);"
                @click.self="showEdit = false">
                <div class="w-full max-w-lg rounded-2xl border shadow-xl flex flex-col"
                    style="background-color: var(--card-bg); border-color: var(--border-color); max-height: 92vh;">
                    <div class="flex items-center justify-between px-6 py-4 border-b shrink-0" style="border-color: var(--border-color);">
                        <h2 class="font-bold text-base" style="color: var(--text-color);">Editar Grupo</h2>
                        <button @click="showEdit = false" class="text-lg leading-none" style="color: var(--text-secondary);">✕</button>
                    </div>
                    <div class="px-6 pt-4 pb-2 shrink-0">
                        <p class="text-sm font-medium" style="color: var(--text-color);">{{ grupoEdit?.materia_nombre }}</p>
                        <p class="text-xs" style="color: var(--text-secondary);">{{ grupoEdit?.materia_codigo }}</p>
                    </div>
                    <div class="overflow-y-auto flex-1 px-6 py-4 space-y-4">
                        <div v-if="formEdit.errors.grupo"
                            class="rounded-lg px-3 py-2 text-xs font-medium"
                            style="background-color: color-mix(in srgb,#ef4444 12%,transparent); color:#ef4444; border:1px solid color-mix(in srgb,#ef4444 30%,transparent);">
                            {{ formEdit.errors.grupo }}
                        </div>

                        <!-- Aula -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                Aula *
                                <span v-if="optsAulasEdit.length < optsAulas.length"
                                      class="font-normal ml-1" style="color: var(--primary-color);">
                                    — {{ optsAulasEdit.length }}/{{ optsAulas.length }} disponibles
                                </span>
                            </label>
                            <ComboSelect v-model="formEdit.id_aula" :options="optsAulasEdit" placeholder="Seleccionar aula" />
                            <p v-if="aulaEditSeleccionada" class="text-[11px] mt-1 opacity-60" style="color: var(--text-secondary);">
                                Capacidad máx: {{ aulaEditSeleccionada.capacidad }}
                            </p>
                        </div>

                        <!-- Profesor -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                Profesor *
                                <span v-if="optsProfesoresEdit.length < optsProfesores.length"
                                      class="font-normal ml-1" style="color: var(--primary-color);">
                                    — {{ optsProfesoresEdit.length }}/{{ optsProfesores.length }} disponibles
                                </span>
                            </label>
                            <ComboSelect v-model="formEdit.id_profesor" :options="optsProfesoresEdit" placeholder="Seleccionar profesor" />
                        </div>

                        <!-- Días del grupo -->
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                Días
                                <span v-if="diasDisponiblesEdit.length > 0" class="font-normal ml-1" style="color: var(--primary-color);">
                                    — puedes agregar más días
                                </span>
                            </label>
                            <div class="flex flex-wrap gap-1.5">
                                <!-- Días actuales: click para quitar (mínimo 1) -->
                                <button v-for="h in grupoEdit?.horarios" :key="h.id_oferta"
                                        type="button"
                                        @click="toggleDiaMantener(h.id_oferta)"
                                        :disabled="formEdit.dias_mantener.length === 1 && formEdit.dias_mantener.includes(h.id_oferta)"
                                        :title="formEdit.dias_mantener.includes(h.id_oferta) ? 'Click para quitar este día' : 'Click para restaurar'"
                                        class="px-2.5 py-1 rounded-full text-xs font-medium border transition-all flex items-center gap-1"
                                        :style="formEdit.dias_mantener.includes(h.id_oferta)
                                            ? 'background:var(--primary-color);color:var(--primary-text);border-color:var(--primary-color);'
                                            : 'background:color-mix(in srgb,#ef4444 12%,transparent);color:#ef4444;border-color:#ef4444;text-decoration:line-through;opacity:0.75;'">
                                    {{ diasAbrev[h.dia_semana] ?? h.dia_semana }}
                                    <span v-if="formEdit.dias_mantener.includes(h.id_oferta) && (grupoEdit?.horarios?.length ?? 0) > 1"
                                          style="font-size:9px;opacity:0.7;">✕</span>
                                </button>
                                <!-- Días disponibles para agregar -->
                                <button v-for="d in diasDisponiblesEdit" :key="d.id_horario"
                                        type="button"
                                        @click="toggleDiaAgregarEdit(d.id_horario)"
                                        :title="'Agregar ' + (diasAbrev[d.dia_semana] ?? d.dia_semana)"
                                        class="px-2.5 py-1 rounded-full text-xs font-medium border transition-all"
                                        :style="formEdit.dias_agregar.includes(d.id_horario)
                                            ? 'background:var(--primary-color);color:var(--primary-text);border-color:var(--primary-color);'
                                            : 'background:transparent;color:var(--text-secondary);border-color:var(--border-color);'">
                                    + {{ diasAbrev[d.dia_semana] ?? d.dia_semana }}
                                </button>
                            </div>
                            <p class="text-[11px] mt-1.5" style="color: var(--text-secondary);">
                                {{ grupoEdit?.hora_inicio }} – {{ grupoEdit?.hora_fin }}
                                <span v-if="formEdit.dias_agregar.length > 0" style="color:var(--primary-color);">
                                    · +{{ formEdit.dias_agregar.length }} día(s) por agregar
                                </span>
                                <span v-if="formEdit.dias_mantener.length < (grupoEdit?.horarios?.length ?? 0)"
                                      style="color:#ef4444;">
                                    · {{ (grupoEdit?.horarios?.length ?? 0) - formEdit.dias_mantener.length }} día(s) por eliminar
                                </span>
                            </p>
                        </div>

                        <!-- Vacantes -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Vacantes máximas *</label>
                            <input v-model.number="formEdit.vacantes_max" type="number" min="1" :max="aulaEditCapacidad"
                                class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);" />
                            <p class="text-[11px] mt-1 opacity-60" style="color: var(--text-secondary);">
                                Ocupadas: {{ grupoEdit?.vacantes_ocupadas ?? 0 }}
                                <span v-if="aulaEditSeleccionada"> · Máx aula: {{ aulaEditCapacidad }}</span>
                            </p>
                            <p v-if="formEdit.vacantes_max > aulaEditCapacidad" class="text-xs mt-1" style="color:#ef4444;">
                                Supera la capacidad del aula ({{ aulaEditCapacidad }})
                            </p>
                            <p v-if="feEdit.vacantes_max || formEdit.errors.vacantes_max" class="text-xs mt-1" style="color:#ef4444;">{{ feEdit.vacantes_max || formEdit.errors.vacantes_max }}</p>
                        </div>

                        <!-- Código grupo -->
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">
                                Código grupo
                                <button type="button" @click="generarCodigoEdit"
                                    class="ml-2 text-[10px] px-2 py-0.5 rounded"
                                    style="background-color: var(--primary-color); color: var(--primary-text);">
                                    ↺ Auto
                                </button>
                            </label>
                            <input v-model="formEdit.codigo_grupo" type="text" maxlength="20"
                                class="w-full rounded-lg border px-3 py-2 text-sm outline-none"
                                style="background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-color);" />
                            <p v-if="feEdit.codigo_grupo || formEdit.errors.codigo_grupo" class="text-xs mt-1" style="color:#ef4444;">{{ feEdit.codigo_grupo || formEdit.errors.codigo_grupo }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 px-6 py-4 border-t shrink-0" style="border-color: var(--border-color);">
                        <button @click="showEdit = false"
                            class="px-4 py-2 rounded-lg text-sm font-medium border"
                            style="border-color: var(--border-color); color: var(--text-secondary);">
                            Cancelar
                        </button>
                        <button @click="guardarEdit" :disabled="formEdit.processing"
                            class="px-5 py-2 rounded-lg text-sm font-medium transition-opacity"
                            :style="formEdit.processing ? 'opacity:0.6;' : ''"
                            style="background-color: var(--primary-color); color: var(--primary-text);">
                            {{ formEdit.processing ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ══ MODAL: Confirmar Eliminar ══════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="confirmarEliminar"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background-color: rgba(0,0,0,0.5);"
                @click.self="confirmarEliminar = null">
                <div class="w-full max-w-sm rounded-2xl border shadow-xl p-6"
                    style="background-color: var(--card-bg); border-color: var(--border-color);">
                    <p class="text-2xl mb-3">⚠️</p>
                    <p class="font-bold mb-1" style="color: var(--text-color);">¿Eliminar grupo?</p>
                    <p class="text-sm mb-5" style="color: var(--text-secondary);">
                        <strong>{{ confirmarEliminar.codigo_grupo }}</strong> — {{ confirmarEliminar.materia_nombre }}.
                        Si tiene inscritos, la eliminación será rechazada.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button @click="confirmarEliminar = null"
                            class="px-4 py-2 rounded-lg text-sm font-medium border"
                            style="border-color: var(--border-color); color: var(--text-secondary);">
                            Cancelar
                        </button>
                        <button @click="eliminarGrupo"
                            class="px-5 py-2 rounded-lg text-sm font-medium"
                            style="background-color: #ef4444; color: white;">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ══ MODAL: Confirmar vaciar período ════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="confirmarVaciarPeriodo"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background-color: rgba(0,0,0,0.5);"
                @click.self="confirmarVaciarPeriodo = null">
                <div class="w-full max-w-sm rounded-2xl border shadow-xl p-6"
                    style="background-color: var(--card-bg); border-color: var(--border-color);">
                    <h2 class="font-bold text-base mb-3" style="color: var(--text-color);">Vaciar oferta del período</h2>
                    <p class="text-sm mb-2" style="color: var(--text-secondary);">
                        Se eliminarán <strong>todos</strong> los grupos de:
                    </p>
                    <p class="text-sm font-semibold mb-1" style="color: var(--text-color);">
                        {{ confirmarVaciarPeriodo.nombre }}
                        <span v-if="confirmarVaciarPeriodo.carrera_nombre"> · {{ confirmarVaciarPeriodo.carrera_nombre }}</span>
                    </p>
                    <p class="text-sm mb-4" style="color: var(--text-secondary);">
                        {{ gruposAgrupados(confirmarVaciarPeriodo.grupos).length }} grupo(s) lógico(s).
                        Si alguno tiene inscritos, la operación será rechazada.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button @click="confirmarVaciarPeriodo = null"
                            class="px-4 py-2 rounded-lg text-sm font-medium border"
                            style="border-color: var(--border-color); color: var(--text-secondary);">
                            Cancelar
                        </button>
                        <button @click="vaciarPeriodo"
                            class="px-5 py-2 rounded-lg text-sm font-medium"
                            style="background-color: #ef4444; color: white;">
                            Eliminar todos
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
