<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import EstudianteLayout from '@/Layouts/EstudianteLayout.vue';

const props = defineProps({
    estudiante:             { type: Object, default: null },
    afiliacion:             { type: Object, default: null },
    pagoCarrera:            { type: Object, default: null },
    planOpciones:           { type: Object, default: () => ({}) },
    inscripciones:          { type: Array,  default: () => [] },
    gruposDisponibles:      { type: Array,  default: () => [] },
    ofertaGeneral:          { type: Array,  default: () => [] },
    proximaMateria:         { type: Object, default: null },
    materiaEnCurso:         { type: Object, default: null },
    cronogramaInscripcion:  { type: Object, default: null },
});

const page  = usePage();
const user  = computed(() => page.props.auth?.user);
const assetUrl = computed(() => page.props.asset_url || '');
const flash = computed(() => page.props.flash ?? {});
const errs  = computed(() => page.props.errors ?? {});

// Tab activa — si no tiene afiliación, mostrar plan primero
const tabActiva = ref(props.afiliacion ? 'disponibles' : 'plan');

// Detectar semestre actual
const hoy = new Date().toISOString().split('T')[0];
const esPeriodoActual = (g) => g.periodo_inicio <= hoy && hoy <= g.periodo_fin;

// Agrupar grupos disponibles por período y por código de grupo (un grupo puede
// tener una fila por cada día de la semana — se fusionan en una sola tarjeta
// con todos sus horarios, igual que en "Oferta del Semestre")
const gruposPorPeriodo = computed(() => {
    const map = {};
    for (const g of props.gruposDisponibles) {
        if (!map[g.periodo_nombre]) {
            map[g.periodo_nombre] = { gruposMap: new Map(), esActual: esPeriodoActual(g), inicio: g.periodo_inicio };
        }
        const periodo  = map[g.periodo_nombre];
        const grupoKey = g.codigo_grupo ?? ('_' + g.id_oferta);
        if (!periodo.gruposMap.has(grupoKey)) {
            periodo.gruposMap.set(grupoKey, {
                id_oferta:         g.id_oferta,
                codigo_grupo:      g.codigo_grupo,
                materia_nombre:    g.materia_nombre,
                materia_codigo:    g.materia_codigo,
                vacantes_max:      g.vacantes_max,
                vacantes_ocupadas: g.vacantes_ocupadas,
                aula_nombre:       g.aula_nombre,
                profesor_nombre:   g.profesor_nombre,
                profesor_cv:       g.profesor_cv,
                horarios:          [],
            });
        }
        periodo.gruposMap.get(grupoKey).horarios.push({
            dia_semana:  g.dia_semana,
            hora_inicio: g.hora_inicio,
            hora_fin:    g.hora_fin,
        });
    }
    // Ordenar: actual primero, luego por fecha desc
    return Object.entries(map)
        .map(([periodo, data]) => [periodo, { grupos: Array.from(data.gruposMap.values()), esActual: data.esActual, inicio: data.inicio }])
        .sort(([, a], [, b]) => {
            if (a.esActual && !b.esActual) return -1;
            if (!a.esActual && b.esActual) return 1;
            return b.inicio.localeCompare(a.inicio);
        });
});

const estadoLabel = (estado) => {
    const m = {
        activo:               { label: 'Activo',      color: '#22c55e' },
        pendiente_pago:       { label: 'Pend. pago',  color: '#f59e0b' },
        pendiente_matricula:  { label: 'Pend. matr.', color: '#f59e0b' },
        completado:           { label: 'Completado',  color: '#3b82f6' },
        abandonado:           { label: 'Abandonado',  color: '#ef4444' },
        aprobado:             { label: 'Aprobado',    color: '#22c55e' },
        reprobado:            { label: 'Reprobado',   color: '#ef4444' },
    };
    return m[estado] ?? { label: estado, color: '#6b7280' };
};

const tipoLabel = (tipo) => ({
    tecnico_superior: 'Técnico Superior',
    tecnico_medio:    'Técnico Medio',
}[tipo] ?? tipo);

const planLabel = (fp) => ({
    contado: 'Pago Total',
    credito: 'Enganche + Materias',
    materia: 'Por Materia',
}[fp] ?? fp);

const fmtHora = (t) => (t ?? '').substring(0, 5);
const cap     = (s)  => s ? s[0].toUpperCase() + s.slice(1) : '';

// Oferta general agrupada por nivel → materia → codigo_grupo (con días agregados)
const ofertaAgrupada = computed(() => {
    if (!props.ofertaGeneral.length) return [];
    const niveles = new Map();
    for (const row of props.ofertaGeneral) {
        const nivelKey  = row.numero_nivel ?? '__libre';
        const nivelLabel = row.nivel_nombre ?? (row.numero_nivel ? `Nivel ${row.numero_nivel}` : 'Módulos del Curso');
        if (!niveles.has(nivelKey)) {
            niveles.set(nivelKey, { numero_nivel: row.numero_nivel ?? 0, nivel_nombre: nivelLabel, materias: new Map() });
        }
        const nivel = niveles.get(nivelKey);
        if (!nivel.materias.has(row.id_materia)) {
            nivel.materias.set(row.id_materia, {
                id_materia:      row.id_materia,
                materia_nombre:  row.materia_nombre,
                materia_codigo:  row.materia_codigo,
                es_proxima:      props.proximaMateria?.id_materia === row.id_materia,
                es_en_curso:     props.materiaEnCurso?.id_materia === row.id_materia,
                grupos:          new Map(),
            });
        }
        const materia  = nivel.materias.get(row.id_materia);
        const grupoKey = row.codigo_grupo ?? ('_' + row.id_oferta);
        if (!materia.grupos.has(grupoKey)) {
            materia.grupos.set(grupoKey, {
                id_oferta:        row.id_oferta,
                codigo_grupo:     row.codigo_grupo,
                vacantes_max:     row.vacantes_max,
                vacantes_ocupadas: row.vacantes_ocupadas,
                aula_nombre:      row.aula_nombre,
                profesor_nombre:  row.profesor_nombre,
                profesor_cv:      row.profesor_cv,
                horarios:         [],
            });
        }
        materia.grupos.get(grupoKey).horarios.push({
            dia_semana:  row.dia_semana,
            hora_inicio: row.hora_inicio,
            hora_fin:    row.hora_fin,
        });
    }
    return Array.from(niveles.values())
        .sort((a, b) => a.numero_nivel - b.numero_nivel)
        .map(nv => ({
            ...nv,
            materias: Array.from(nv.materias.values()).map(mt => ({
                ...mt,
                grupos: Array.from(mt.grupos.values()),
            })),
        }));
});

// Inscripción directa (plan completo — sin QR)
const inscribiendoId = ref(null);
function inscribirse(idOferta) {
    inscribiendoId.value = idOferta;
    const form = useForm({});
    form.post(route('estudiante.inscribir', idOferta), {
        onFinish: () => { inscribiendoId.value = null; },
    });
}

// Elegir plan (para materia → sin QR, para completo/porcentaje → redirige a pago)
const eligiendoPlan = ref(null);
function elegirPlan(tipo) {
    eligiendoPlan.value = tipo;
    const form = useForm({});
    form.post(route('estudiante.plan', tipo), {
        onFinish: () => { eligiendoPlan.value = null; },
    });
}
</script>

<template>
    <Head title="Mi Panel" />
    <EstudianteLayout>
        <template #header>
            <span class="font-semibold text-lg" style="color: var(--text-color);">Mi Panel</span>
        </template>

        <div class="py-8 px-4 sm:px-6 lg:px-8 mx-auto max-w-6xl space-y-5">

            <!-- Alertas globales -->
            <div v-if="errs.general || errs.plan"
                 class="rounded-lg px-4 py-3 text-sm font-medium"
                 style="background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;">
                {{ errs.general || errs.plan }}
            </div>
            <div v-if="flash.success"
                 class="rounded-lg px-4 py-3 text-sm font-medium"
                 style="background-color: #dcfce7; color: #15803d; border: 1px solid #86efac;">
                {{ flash.success }}
            </div>

            <!-- Sin perfil -->
            <div v-if="!estudiante" class="rounded-xl p-8 text-center"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <p class="font-semibold" style="color: var(--text-color);">Tu cuenta no tiene perfil de estudiante.</p>
                <p class="text-sm mt-1" style="color: var(--text-secondary);">Contacta a la secretaría para completar tu registro.</p>
            </div>

            <template v-else>

                <!-- ── HEADER ESTUDIANTE ── -->
                <div class="rounded-xl p-5" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <!-- Avatar -->
                        <img v-if="user?.foto_perfil" :src="($page.props.asset_url || '') + '/imagenes/' + user.foto_perfil" class="w-12 h-12 rounded-full object-cover shrink-0">
                        <div v-else class="flex items-center justify-center w-12 h-12 rounded-full text-lg font-bold shrink-0"
                             style="background-color: var(--primary-color); color: var(--primary-text);">
                            {{ (user?.name ?? 'E')[0].toUpperCase() }}
                        </div>
                        <!-- Info -->
                        <div class="flex-1">
                            <h1 class="text-lg font-bold" style="color: var(--text-color);">{{ user?.name }}</h1>
                            <p class="text-xs" style="color: var(--text-secondary);">{{ user?.email }}</p>
                            <div class="flex flex-wrap gap-2 mt-1.5">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                      style="background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);">
                                    {{ estudiante.legajo }}
                                </span>
                                <span v-if="estudiante.carrera" class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                      style="background-color: color-mix(in srgb, #3b82f6 15%, transparent); color: #3b82f6;">
                                    {{ estudiante.carrera.nombre }}
                                </span>
                                <span v-if="estudiante.carrera" class="px-2.5 py-0.5 rounded-full text-xs font-medium"
                                      style="background-color: color-mix(in srgb, var(--text-color) 8%, transparent); color: var(--text-secondary);">
                                    {{ tipoLabel(estudiante.carrera.tipo) }}
                                </span>
                            </div>
                        </div>
                        <!-- Estado matrícula -->
                        <div class="rounded-lg px-3 py-2 text-center shrink-0 text-xs font-semibold"
                             :style="estudiante.tiene_matricula
                                ? 'background-color:#dcfce7; border:1px solid #86efac; color:#15803d'
                                : 'background-color:#fee2e2; border:1px solid #fca5a5; color:#b91c1c'">
                            <p class="uppercase tracking-wide">Matrícula</p>
                            <p class="text-sm font-bold mt-0.5">{{ estudiante.tiene_matricula ? '✓ Pagada' : '✗ Pendiente' }}</p>
                            <p v-if="estudiante.matricula">{{ estudiante.matricula.fecha_pago }}</p>
                        </div>
                    </div>
                </div>

                <!-- ── TABS ── -->
                <div class="flex gap-1 rounded-lg p-1" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <!-- Plan -->
                    <button @click="tabActiva = 'plan'"
                        class="flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all relative"
                        :style="tabActiva === 'plan'
                            ? 'background-color: var(--primary-color); color: var(--primary-text);'
                            : 'color: var(--text-secondary);'">
                        Plan de Carrera
                        <span v-if="!afiliacion" class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-red-500"></span>
                    </button>
                    <!-- Oferta del semestre — solo cuando inscripciones abiertas -->
                    <button v-if="ofertaGeneral.length > 0" @click="tabActiva = 'oferta'"
                        class="flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all"
                        :style="tabActiva === 'oferta'
                            ? 'background-color: var(--primary-color); color: var(--primary-text);'
                            : 'color: var(--text-secondary);'">
                        Oferta del Semestre
                        <span class="ml-1 text-xs opacity-75">({{ ofertaAgrupada.reduce((s,n)=>s+n.materias.length,0) }} mat.)</span>
                    </button>
                    <!-- Grupos disponibles para mí -->
                    <button @click="tabActiva = 'disponibles'"
                        class="flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all"
                        :style="tabActiva === 'disponibles'
                            ? 'background-color: var(--primary-color); color: var(--primary-text);'
                            : 'color: var(--text-secondary);'">
                        Mis Grupos
                        <span v-if="gruposDisponibles.length" class="ml-1 text-xs opacity-75">({{ gruposDisponibles.length }})</span>
                    </button>
                    <!-- Mis inscripciones -->
                    <button @click="tabActiva = 'inscripciones'"
                        class="flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all"
                        :style="tabActiva === 'inscripciones'
                            ? 'background-color: var(--primary-color); color: var(--primary-text);'
                            : 'color: var(--text-secondary);'">
                        Mis Inscripciones
                        <span v-if="inscripciones.length" class="ml-1 text-xs opacity-75">({{ inscripciones.length }})</span>
                    </button>
                </div>

                <!-- ══ TAB: PLAN DE CARRERA ══ -->
                <div v-if="tabActiva === 'plan'">

                    <!-- Plan ya activo -->
                    <div v-if="afiliacion" class="rounded-xl p-5"
                         style="background-color: var(--card-bg); border: 1px solid #86efac;">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">✅</span>
                            <div>
                                <p class="font-bold" style="color: #15803d;">Plan activo desde {{ afiliacion.fecha_inicio }}</p>
                                <p class="text-sm mt-0.5" style="color: var(--text-secondary);">
                                    {{ planLabel(pagoCarrera?.forma_pago) }} —
                                    <template v-if="pagoCarrera?.forma_pago === 'contado'">
                                        Todas las materias cubiertas.
                                    </template>
                                    <template v-else-if="pagoCarrera?.forma_pago === 'credito'">
                                        Enganche pagado. Pagas cada materia al inscribirte.
                                    </template>
                                    <template v-else>
                                        Pagas cada materia al inscribirte.
                                    </template>
                                </p>
                                <p v-if="pagoCarrera && pagoCarrera.forma_pago !== 'contado'" class="text-xs mt-1" style="color: var(--text-secondary);">
                                    Monto total: Bs. {{ pagoCarrera.monto_total?.toFixed(2) }} |
                                    Pagado: Bs. {{ pagoCarrera.monto_pagado?.toFixed(2) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Sin plan → elegir -->
                    <template v-else>
                        <div class="rounded-xl px-5 py-4 mb-4"
                             style="background-color: #fffbeb; border: 1px solid #fcd34d;">
                            <p class="text-sm font-semibold" style="color: #92400e;">⚠ Debes elegir un plan de pago para poder inscribirte en materias</p>
                        </div>

                        <!-- 3 opciones de plan -->
                        <div v-if="!estudiante.carrera" class="text-center p-6" style="color: var(--text-secondary);">
                            Sin carrera asignada. Contacta a la secretaría.
                        </div>
                        <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">

                            <!-- COMPLETO -->
                            <div class="rounded-xl overflow-hidden flex flex-col"
                                 style="background-color: var(--card-bg); border: 2px solid var(--primary-color);">
                                <div class="px-5 py-3 text-center"
                                     style="background-color: var(--primary-color);">
                                    <p class="font-bold text-sm" style="color: var(--primary-text);">Pago Total</p>
                                    <p class="text-xs mt-0.5" style="color: var(--primary-text); opacity: 0.85;">20% de descuento</p>
                                </div>
                                <div class="p-5 flex-1 flex flex-col gap-3">
                                    <div class="text-center">
                                        <p class="text-3xl font-bold" style="color: var(--primary-color);">
                                            Bs. {{ planOpciones.contado?.monto_inicial?.toFixed(2) }}
                                        </p>
                                        <p class="text-xs line-through mt-0.5" style="color: var(--text-secondary);">
                                            Bs. {{ planOpciones.contado?.monto_original?.toFixed(2) }}
                                        </p>
                                        <p class="text-xs font-semibold mt-1" style="color: #22c55e;">
                                            Ahorro: Bs. {{ planOpciones.contado?.ahorro?.toFixed(2) }}
                                        </p>
                                    </div>
                                    <p class="text-xs text-center" style="color: var(--text-secondary);">
                                        Cubre todas las materias de la carrera. Sin costo adicional por materia.
                                    </p>
                                    <div class="flex-1"></div>
                                    <button @click="elegirPlan('contado')" :disabled="eligiendoPlan === 'contado'"
                                            class="w-full py-2.5 rounded-lg font-semibold text-sm transition-all disabled:opacity-50"
                                            style="background-color: var(--primary-color); color: var(--primary-text);">
                                        {{ eligiendoPlan === 'contado' ? 'Procesando...' : 'Pagar con QR' }}
                                    </button>
                                </div>
                            </div>

                            <!-- PORCENTAJE -->
                            <div class="rounded-xl overflow-hidden flex flex-col"
                                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                                <div class="px-5 py-3 text-center border-b" style="border-color: var(--border-color);">
                                    <p class="font-bold text-sm" style="color: var(--text-color);">Enganche + Materias</p>
                                    <p class="text-xs mt-0.5" style="color: var(--text-secondary);">30% ahora, resto por materia</p>
                                </div>
                                <div class="p-5 flex-1 flex flex-col gap-3">
                                    <div class="text-center">
                                        <p class="text-3xl font-bold" style="color: var(--text-color);">
                                            Bs. {{ planOpciones.credito?.monto_inicial?.toFixed(2) }}
                                        </p>
                                        <p class="text-xs mt-0.5" style="color: var(--text-secondary);">enganche inicial</p>
                                        <p class="text-xs font-semibold mt-1" style="color: var(--primary-color);">
                                            + Bs. {{ planOpciones.credito?.por_materia?.toFixed(2) }} por materia
                                        </p>
                                    </div>
                                    <p class="text-xs text-center" style="color: var(--text-secondary);">
                                        Paga el 30% ahora y el resto se distribuye al inscribirte en cada materia.
                                    </p>
                                    <div class="flex-1"></div>
                                    <button @click="elegirPlan('credito')" :disabled="eligiendoPlan === 'credito'"
                                            class="w-full py-2.5 rounded-lg font-semibold text-sm transition-all border disabled:opacity-50"
                                            style="border-color: var(--primary-color); color: var(--primary-color); background: transparent;">
                                        {{ eligiendoPlan === 'credito' ? 'Procesando...' : 'Pagar con QR' }}
                                    </button>
                                </div>
                            </div>

                            <!-- MATERIA -->
                            <div class="rounded-xl overflow-hidden flex flex-col"
                                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                                <div class="px-5 py-3 text-center border-b" style="border-color: var(--border-color);">
                                    <p class="font-bold text-sm" style="color: var(--text-color);">Por Materia</p>
                                    <p class="text-xs mt-0.5" style="color: var(--text-secondary);">Sin enganche</p>
                                </div>
                                <div class="p-5 flex-1 flex flex-col gap-3">
                                    <div class="text-center">
                                        <p class="text-3xl font-bold" style="color: var(--text-color);">Bs. 0</p>
                                        <p class="text-xs mt-0.5" style="color: var(--text-secondary);">costo inicial</p>
                                        <p class="text-xs font-semibold mt-1" style="color: var(--primary-color);">
                                            Bs. {{ planOpciones.materia?.por_materia?.toFixed(2) }} por materia
                                        </p>
                                    </div>
                                    <p class="text-xs text-center" style="color: var(--text-secondary);">
                                        Sin pago inicial. Pagas cada materia cuando te inscribas.
                                    </p>
                                    <div class="flex-1"></div>
                                    <button @click="elegirPlan('materia')" :disabled="eligiendoPlan === 'materia'"
                                            class="w-full py-2.5 rounded-lg font-semibold text-sm transition-all border disabled:opacity-50"
                                            style="border-color: var(--border-color); color: var(--text-color); background: transparent;">
                                        {{ eligiendoPlan === 'materia' ? 'Procesando...' : 'Elegir este plan' }}
                                    </button>
                                </div>
                            </div>

                        </div>
                    </template>
                </div>

                <!-- ══ TAB: OFERTA DEL SEMESTRE ══ -->
                <div v-if="tabActiva === 'oferta'">

                    <!-- Sin período activo -->
                    <div v-if="!ofertaGeneral.length" class="rounded-xl p-8 text-center"
                         style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <p class="text-4xl mb-3">📅</p>
                        <p class="font-medium" style="color: var(--text-color);">No hay grupos publicados en el período activo.</p>
                        <p class="text-sm mt-1" style="color: var(--text-secondary);">El director aún no ha publicado el maestro de oferta para este semestre.</p>
                    </div>

                    <template v-else>
                        <!-- Info banner -->
                        <div class="rounded-xl px-4 py-3 mb-4 text-sm flex items-start gap-2"
                             style="background-color: color-mix(in srgb,#6366f1 8%,transparent); border: 1px solid color-mix(in srgb,#6366f1 25%,transparent); color:#4338ca;">
                            <span class="shrink-0 mt-0.5">📋</span>
                            <span>
                                Vista general de todos los grupos ofertados este semestre para tu carrera.
                                Tu próxima materia está <strong>resaltada en verde</strong>.
                                Para inscribirte ve a <button class="underline font-semibold" @click="tabActiva='disponibles'">Mis Grupos →</button>
                            </span>
                        </div>

                        <!-- Niveles -->
                        <div class="space-y-5">
                            <div v-for="nivel in ofertaAgrupada" :key="nivel.numero_nivel">

                                <!-- Cabecera nivel -->
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold shrink-0"
                                         style="background-color: var(--primary-color); color: var(--primary-text);">
                                        {{ nivel.numero_nivel || '★' }}
                                    </div>
                                    <h3 class="font-semibold text-sm" style="color: var(--text-color);">{{ nivel.nivel_nombre }}</h3>
                                    <div class="flex-1 h-px" style="background-color: var(--border-color);"></div>
                                    <span class="text-xs" style="color: var(--text-secondary);">{{ nivel.materias.length }} materia(s)</span>
                                </div>

                                <!-- Materias del nivel -->
                                <div class="space-y-3">
                                    <div v-for="materia in nivel.materias" :key="materia.id_materia"
                                         class="rounded-xl overflow-hidden"
                                         :style="(materia.es_proxima || materia.es_en_curso)
                                            ? `border: 2px solid ${materia.es_en_curso ? '#f59e0b' : '#22c55e'}; background-color: color-mix(in srgb,${materia.es_en_curso ? '#f59e0b' : '#22c55e'} 5%,var(--card-bg));`
                                            : 'border: 1px solid var(--border-color); background-color: var(--card-bg);'">

                                        <!-- Header materia -->
                                        <div class="flex items-center gap-3 px-4 py-2.5 border-b"
                                             :style="(materia.es_proxima || materia.es_en_curso)
                                                ? `border-color: ${materia.es_en_curso ? '#fcd34d' : '#86efac'}; background-color: color-mix(in srgb,${materia.es_en_curso ? '#f59e0b' : '#22c55e'} 10%,transparent);`
                                                : 'border-color: var(--border-color);'">
                                            <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded"
                                                  style="background-color: color-mix(in srgb,var(--text-color) 8%,transparent); color: var(--text-secondary);">
                                                {{ materia.materia_codigo }}
                                            </span>
                                            <span class="font-semibold text-sm flex-1" :style="materia.es_en_curso ? 'color:#92400e' : (materia.es_proxima ? 'color:#15803d' : 'color:var(--text-color)')">
                                                {{ materia.materia_nombre }}
                                            </span>
                                            <span v-if="materia.es_en_curso"
                                                  class="px-2.5 py-0.5 rounded-full text-xs font-bold"
                                                  style="background-color:#f59e0b; color:#fff;">
                                                ⏳ En curso
                                            </span>
                                            <span v-else-if="materia.es_proxima"
                                                  class="px-2.5 py-0.5 rounded-full text-xs font-bold"
                                                  style="background-color:#22c55e; color:#fff;">
                                                ← Tu próxima
                                            </span>
                                            <span class="text-xs" style="color: var(--text-secondary);">
                                                {{ materia.grupos.length }} grupo(s)
                                            </span>
                                        </div>

                                        <!-- Grupos de la materia -->
                                        <div class="divide-y" style="border-color: var(--border-color);">
                                            <div v-for="grupo in materia.grupos" :key="grupo.codigo_grupo"
                                                 class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 py-3">
                                                <!-- Código grupo -->
                                                <span class="font-mono text-xs font-bold shrink-0"
                                                      style="color: var(--primary-color); min-width:5rem;">
                                                    {{ grupo.codigo_grupo ?? '—' }}
                                                </span>
                                                <!-- Horarios (multi-día) -->
                                                <div class="flex-1">
                                                    <div class="flex flex-wrap gap-1.5">
                                                        <span v-for="h in grupo.horarios" :key="h.dia_semana"
                                                              class="text-xs px-2 py-0.5 rounded-full font-medium"
                                                              style="background-color: color-mix(in srgb, var(--primary-color) 10%, transparent); color: var(--primary-color);">
                                                            {{ cap(h.dia_semana) }} {{ fmtHora(h.hora_inicio) }}–{{ fmtHora(h.hora_fin) }}
                                                        </span>
                                                    </div>
                                                    <div class="flex flex-wrap gap-x-3 mt-1 text-xs" style="color: var(--text-secondary);">
                                                        <span>{{ grupo.aula_nombre }}</span>
                                                        <span class="flex items-center gap-1">
                                                            {{ grupo.profesor_nombre }}
                                                            <a v-if="grupo.profesor_cv" :href="assetUrl + '/cvs/' + grupo.profesor_cv" target="_blank" title="Ver CV" class="text-indigo-500 hover:text-indigo-700">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                                            </a>
                                                        </span>
                                                    </div>
                                                </div>
                                                <!-- Vacantes -->
                                                <div class="text-center shrink-0">
                                                    <p class="text-xs" style="color: var(--text-secondary);">Vacantes</p>
                                                    <p class="font-bold text-sm" :style="(grupo.vacantes_max - (grupo.vacantes_ocupadas??0)) > 0 ? 'color: var(--primary-color)' : 'color:#ef4444'">
                                                        {{ grupo.vacantes_max - (grupo.vacantes_ocupadas ?? 0) }}
                                                        <span class="text-xs font-normal" style="color: var(--text-secondary);">/ {{ grupo.vacantes_max }}</span>
                                                    </p>
                                                </div>
                                                <!-- Badge informativo -->
                                                <span v-if="materia.es_en_curso"
                                                      class="shrink-0 text-xs px-2.5 py-1 rounded-lg font-semibold"
                                                      style="background-color: color-mix(in srgb,#f59e0b 15%,transparent); color:#92400e; border:1px solid #fcd34d;">
                                                    En curso
                                                </span>
                                                <span v-else-if="materia.es_proxima"
                                                      class="shrink-0 text-xs px-2.5 py-1 rounded-lg font-semibold"
                                                      style="background-color: color-mix(in srgb,#22c55e 15%,transparent); color:#15803d; border:1px solid #86efac;">
                                                    Tu próxima
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- ══ TAB: GRUPOS DISPONIBLES ══ -->
                <div v-if="tabActiva === 'disponibles'">

                    <!-- Banner cronograma: inscripciones abiertas o cerradas -->
                    <div v-if="cronogramaInscripcion" class="rounded-xl px-4 py-3 mb-3 flex items-center gap-2 text-sm"
                         style="background-color: color-mix(in srgb,#10b981 10%,transparent); border: 1px solid color-mix(in srgb,#10b981 30%,transparent); color:#15803d;">
                        <span>✅</span>
                        <span>
                            Inscripciones abiertas hasta
                            <strong>{{ new Date(cronogramaInscripcion.fecha_fin + 'T12:00:00').toLocaleDateString('es-BO', { day:'2-digit', month:'short', year:'numeric' }) }}</strong>
                            — {{ cronogramaInscripcion.nombre }}
                        </span>
                    </div>
                    <div v-else class="rounded-xl px-4 py-3 mb-3 flex items-center gap-2 text-sm"
                         style="background-color: color-mix(in srgb,#ef4444 10%,transparent); border: 1px solid color-mix(in srgb,#ef4444 30%,transparent); color:#b91c1c;">
                        <span>🔒</span>
                        <span>Período de inscripciones cerrado. Consulta el cronograma académico.</span>
                    </div>

                    <!-- Banner: materia en curso (mensual, bloquea nuevas inscripciones) -->
                    <div v-if="materiaEnCurso"
                         class="rounded-xl px-4 py-3 mb-3 flex items-center gap-2 text-sm"
                         style="background-color: color-mix(in srgb,#f59e0b 10%,transparent); border: 1px solid color-mix(in srgb,#f59e0b 30%,transparent); color:#92400e;">
                        <span>⏳</span>
                        <span>
                            Tienes <strong>{{ materiaEnCurso.nombre }}</strong>
                            <span class="opacity-70">({{ materiaEnCurso.codigo }})</span>
                            en curso. Las materias son mensuales: debes completarla antes de poder inscribirte y pagar la siguiente.
                        </span>
                    </div>

                    <!-- Banner materia que le corresponde -->
                    <div v-else-if="cronogramaInscripcion && proximaMateria"
                         class="rounded-xl px-4 py-3 mb-3 flex items-center gap-2 text-sm"
                         style="background-color: color-mix(in srgb,#6366f1 10%,transparent); border: 1px solid color-mix(in srgb,#6366f1 30%,transparent); color:#4338ca;">
                        <span>📚</span>
                        <span>
                            Tu próxima materia:
                            <strong>{{ proximaMateria.nombre }}</strong>
                            <span class="ml-1 opacity-70">({{ proximaMateria.codigo }})</span>
                            — se muestran solo los grupos disponibles para esta materia.
                        </span>
                    </div>

                    <!-- Sin afiliación → bloquear con aviso -->
                    <div v-if="!afiliacion" class="rounded-xl p-6"
                         style="background-color: #fffbeb; border: 1px solid #fcd34d;">
                        <p class="font-semibold text-sm" style="color: #92400e;">⚠ Necesitas un plan de carrera activo</p>
                        <p class="text-sm mt-1" style="color: #92400e;">
                            Ve a la pestaña <strong>Plan de Carrera</strong> y elige tu modalidad de pago para poder inscribirte en materias.
                        </p>
                    </div>

                    <div v-else-if="!estudiante.carrera" class="rounded-xl p-8 text-center"
                         style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <p class="font-medium" style="color: var(--text-color);">No tienes carrera asignada.</p>
                    </div>

                    <div v-else-if="gruposPorPeriodo.length === 0" class="rounded-xl p-8 text-center"
                         style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <p class="text-4xl mb-3">📅</p>
                        <p class="font-medium" style="color: var(--text-color);">
                            <template v-if="materiaEnCurso">Ya tienes <strong>{{ materiaEnCurso.nombre }}</strong> en curso este mes.</template>
                            <template v-else-if="!cronogramaInscripcion">Las inscripciones están cerradas.</template>
                            <template v-else-if="!proximaMateria">Ya completaste todas las materias de la malla.</template>
                            <template v-else>No hay grupos disponibles para <strong>{{ proximaMateria.nombre }}</strong> en este período.</template>
                        </p>
                        <p class="text-sm mt-1" style="color: var(--text-secondary);">
                            <template v-if="materiaEnCurso">No se ofrecen nuevas materias hasta que la completes.</template>
                            <template v-else-if="!cronogramaInscripcion">Consulta el cronograma académico para las próximas fechas.</template>
                            <template v-else-if="!proximaMateria">Consulta con la dirección para tu siguiente paso.</template>
                            <template v-else>El director aún no ha publicado grupos para esta materia.</template>
                        </p>
                    </div>

                    <!-- Grupos por período -->
                    <div v-else class="space-y-4">
                        <div v-for="[periodo, data] in gruposPorPeriodo" :key="periodo"
                             class="rounded-xl overflow-hidden"
                             style="background-color: var(--card-bg); border: 1px solid var(--border-color);">

                            <!-- Header período -->
                            <div class="px-5 py-3 border-b flex items-center gap-3"
                                 :style="data.esActual
                                    ? 'border-color: var(--primary-color); background-color: color-mix(in srgb, var(--primary-color) 12%, transparent);'
                                    : 'border-color: var(--border-color); background-color: color-mix(in srgb, var(--text-color) 4%, transparent);'">
                                <p class="font-semibold text-sm flex-1"
                                   :style="data.esActual ? 'color: var(--primary-color)' : 'color: var(--text-color)'">
                                    {{ periodo }}
                                </p>
                                <span v-if="data.esActual"
                                      class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide"
                                      style="background-color: var(--primary-color); color: var(--primary-text);">
                                    Semestre actual
                                </span>
                                <!-- Precio por materia si aplica -->
                                <span v-if="pagoCarrera?.forma_pago !== 'contado'" class="text-xs"
                                      style="color: var(--text-secondary);">
                                    Bs. {{ (pagoCarrera?.forma_pago === 'credito'
                                        ? planOpciones.credito?.por_materia
                                        : planOpciones.materia?.por_materia)?.toFixed(2) }} / materia
                                </span>
                            </div>

                            <!-- Grupos -->
                            <div class="divide-y" style="border-color: var(--border-color);">
                                <div v-for="g in data.grupos" :key="g.id_oferta"
                                     class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs font-mono px-2 py-0.5 rounded"
                                                  style="background-color: color-mix(in srgb, var(--text-color) 8%, transparent); color: var(--text-secondary);">
                                                {{ g.materia_codigo }}
                                            </span>
                                            <span class="font-semibold text-sm" style="color: var(--text-color);">{{ g.materia_nombre }}</span>
                                            <span v-if="g.codigo_grupo" class="font-mono text-xs font-bold" style="color: var(--primary-color);">
                                                {{ g.codigo_grupo }}
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap gap-1.5 mt-1.5">
                                            <span v-for="h in g.horarios" :key="h.dia_semana"
                                                  class="text-xs px-2 py-0.5 rounded-full font-medium"
                                                  style="background-color: color-mix(in srgb, var(--primary-color) 10%, transparent); color: var(--primary-color);">
                                                {{ cap(h.dia_semana) }} {{ fmtHora(h.hora_inicio) }}–{{ fmtHora(h.hora_fin) }}
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap gap-x-4 gap-y-0.5 mt-1 text-xs" style="color: var(--text-secondary);">
                                            <span>{{ g.aula_nombre }}</span>
                                        <span class="flex items-center gap-1">
                                            {{ g.profesor_nombre }}
                                            <a v-if="g.profesor_cv" :href="assetUrl + '/cvs/' + g.profesor_cv" target="_blank" title="Ver CV del Docente" class="text-indigo-600 hover:text-indigo-800 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                </svg>
                                            </a>
                                        </span>
                                        </div>
                                    </div>
                                    <div class="text-center shrink-0">
                                        <p class="text-xs font-medium" style="color: var(--text-secondary);">Vacantes</p>
                                        <p class="text-lg font-bold" style="color: var(--primary-color);">
                                            {{ (g.vacantes_max - (g.vacantes_ocupadas ?? 0)) }}
                                            <span class="text-xs font-normal" style="color: var(--text-secondary);">/ {{ g.vacantes_max }}</span>
                                        </p>
                                    </div>
                                    <button @click="inscribirse(g.id_oferta)"
                                            :disabled="inscribiendoId === g.id_oferta"
                                            class="shrink-0 px-5 py-2 rounded-lg text-sm font-semibold transition-all disabled:opacity-50"
                                            style="background-color: var(--primary-color); color: var(--primary-text);">
                                        {{ inscribiendoId === g.id_oferta ? 'Procesando...' : (pagoCarrera?.forma_pago === 'contado' ? 'Inscribirme' : 'Inscribir y pagar') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══ TAB: MIS INSCRIPCIONES ══ -->
                <div v-if="tabActiva === 'inscripciones'">
                    <div v-if="inscripciones.length === 0" class="rounded-xl p-8 text-center"
                         style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <p class="text-4xl mb-3">📚</p>
                        <p class="font-medium" style="color: var(--text-color);">Todavía no tienes inscripciones activas.</p>
                        <p class="text-sm mt-1" style="color: var(--text-secondary);">Inscríbete en un grupo desde "Grupos Disponibles".</p>
                    </div>
                    <div v-else class="rounded-xl overflow-hidden"
                         style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="divide-y" style="border-color: var(--border-color);">
                            <div v-for="ins in inscripciones" :key="ins.id_inscripcion"
                                 class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-mono px-2 py-0.5 rounded"
                                              style="background-color: color-mix(in srgb, var(--text-color) 8%, transparent); color: var(--text-secondary);">
                                            {{ ins.materia_codigo }}
                                        </span>
                                        <span class="font-semibold text-sm" style="color: var(--text-color);">{{ ins.materia_nombre }}</span>
                                        <!-- Badge semestre actual -->
                                        <span v-if="ins.periodo_inicio <= hoy && hoy <= ins.periodo_fin"
                                              class="px-2 py-0.5 rounded-full text-xs font-bold"
                                              style="background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);">
                                            En curso
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap gap-x-4 gap-y-0.5 mt-1 text-xs" style="color: var(--text-secondary);">
                                        <span>{{ ins.periodo_nombre }}</span>
                                        <span>{{ cap(ins.dia_semana) }} {{ fmtHora(ins.hora_inicio) }}–{{ fmtHora(ins.hora_fin) }}</span>
                                        <span>{{ ins.aula_nombre }}</span>
                                        <span class="flex items-center gap-1">
                                            {{ ins.profesor_nombre }}
                                            <a v-if="ins.profesor_cv" :href="assetUrl + '/cvs/' + ins.profesor_cv" target="_blank" title="Ver CV del Docente" class="text-indigo-600 hover:text-indigo-800 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                </svg>
                                            </a>
                                        </span>
                                    </div>
                                </div>
                                <div v-if="ins.calificacion_final !== null" class="text-center shrink-0">
                                    <p class="text-xs font-medium" style="color: var(--text-secondary);">Nota</p>
                                    <p class="text-lg font-bold" :style="ins.aprobado ? 'color:#22c55e' : 'color:#ef4444'">
                                        {{ ins.calificacion_final }}
                                    </p>
                                </div>
                                <span class="shrink-0 px-3 py-1 rounded-full text-xs font-semibold"
                                      :style="`background-color: color-mix(in srgb, ${estadoLabel(ins.estado).color} 15%, transparent); color: ${estadoLabel(ins.estado).color}`">
                                    {{ estadoLabel(ins.estado).label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </template>
        </div>
    </EstudianteLayout>
</template>
