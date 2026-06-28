<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    estudiante:        { type: Object, default: null },
    afiliacion:        { type: Object, default: null },
    pagoCarrera:       { type: Object, default: null },
    planOpciones:      { type: Object, default: () => ({}) },
    inscripciones:     { type: Array,  default: () => [] },
    gruposDisponibles: { type: Array,  default: () => [] },
});

const page  = usePage();
const user  = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash ?? {});
const errs  = computed(() => page.props.errors ?? {});

// Tab activa — si no tiene afiliación, mostrar plan primero
const tabActiva = ref(props.afiliacion ? 'disponibles' : 'plan');

// Detectar semestre actual
const hoy = new Date().toISOString().split('T')[0];
const esPeriodoActual = (g) => g.periodo_inicio <= hoy && hoy <= g.periodo_fin;

// Agrupar grupos disponibles por período, con flag de "actual"
const gruposPorPeriodo = computed(() => {
    const map = {};
    for (const g of props.gruposDisponibles) {
        if (!map[g.periodo_nombre]) {
            map[g.periodo_nombre] = { grupos: [], esActual: esPeriodoActual(g), inicio: g.periodo_inicio };
        }
        map[g.periodo_nombre].grupos.push(g);
    }
    // Ordenar: actual primero, luego por fecha desc
    return Object.entries(map).sort(([, a], [, b]) => {
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
    <AuthenticatedLayout>
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
                        <div class="flex items-center justify-center w-12 h-12 rounded-full text-lg font-bold shrink-0"
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
                    <!-- Grupos -->
                    <button @click="tabActiva = 'disponibles'"
                        class="flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all"
                        :style="tabActiva === 'disponibles'
                            ? 'background-color: var(--primary-color); color: var(--primary-text);'
                            : 'color: var(--text-secondary);'">
                        Grupos Disponibles
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

                <!-- ══ TAB: GRUPOS DISPONIBLES ══ -->
                <div v-if="tabActiva === 'disponibles'">

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
                        <p class="font-medium" style="color: var(--text-color);">No hay grupos disponibles en este momento.</p>
                        <p class="text-sm mt-1" style="color: var(--text-secondary);">Los períodos activos con vacantes aparecerán aquí.</p>
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
                                        </div>
                                        <div class="flex flex-wrap gap-x-4 gap-y-0.5 mt-1.5 text-xs" style="color: var(--text-secondary);">
                                            <span>{{ cap(g.dia_semana) }} {{ fmtHora(g.hora_inicio) }}–{{ fmtHora(g.hora_fin) }}</span>
                                            <span>{{ g.aula_nombre }}</span>
                                            <span>{{ g.profesor_nombre }}</span>
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
                                        <span>{{ ins.profesor_nombre }}</span>
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
    </AuthenticatedLayout>
</template>
