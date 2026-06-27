<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    estudiante:           Object,
    carreraActual:        Object,
    matricula:            Object,
    planCarrera:          Object,
    cuotas:               Array,
    materiasSueltas:      Array,
    carrerasDisponibles:  Array,
    pendiente:            Object,
});

// ── Modal matrícula ───────────────────────────────────────────────────────────
const showMatricula = ref(false);
const formMatricula = useForm({ monto: 500, comprobante: '' });

function guardarMatricula() {
    formMatricula.post(route('secretaria.pagos.matricula', props.estudiante.id_usuario), {
        onSuccess: () => { showMatricula.value = false; formMatricula.reset(); formMatricula.monto = 500; },
    });
}

// ── Modal plan carrera ────────────────────────────────────────────────────────
const showCarrera   = ref(false);
const formCarrera   = useForm({ id_carrera: '', monto: '' });

const carreraSeleccionada = computed(() =>
    props.carrerasDisponibles.find(c => c.id_carrera == formCarrera.id_carrera) ?? null
);

watch(() => formCarrera.id_carrera, () => { formCarrera.monto = ''; });

const montoNum = computed(() => parseFloat(formCarrera.monto) || 0);

const analisisPago = computed(() => {
    const c = carreraSeleccionada.value;
    if (!c || montoNum.value <= 0) return null;
    const contado = montoNum.value >= c.precio_contado;
    const valido  = montoNum.value >= c.minimo_30;
    return {
        valido,
        tipo:    contado ? 'Contado' : 'Crédito',
        color:   contado ? '#34d399'  : '#818cf8',
        mensaje: contado
            ? `Pago de contado con 20% de descuento (precio normal: Bs. ${c.costo_carrera_completa})`
            : valido
                ? `Pago a crédito — mínimo Bs. ${c.minimo_30} (30%)`
                : `Monto insuficiente — mínimo Bs. ${c.minimo_30}`,
    };
});

function guardarCarrera() {
    formCarrera.post(route('secretaria.pagos.carrera', props.estudiante.id_usuario), {
        onSuccess: () => { showCarrera.value = false; formCarrera.reset(); },
    });
}

// ── Pagar cuota ───────────────────────────────────────────────────────────────
const cuotaConfirm = ref(null); // { id_pago_carrera, numero_cuota, monto }

function confirmarCuota(c) {
    if (!confirm(`¿Registrar pago de la Cuota #${c.numero_cuota}?`)) return;
    useForm({}).post(
        route('secretaria.pagos.cuota', { idPago: c.id_pago_carrera, numCuota: c.numero_cuota }),
        { preserveScroll: true }
    );
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const nombreCompleto = computed(() => `${props.estudiante.apellido}, ${props.estudiante.nombre}`);

function formatFecha(f) {
    if (!f) return '—';
    return new Date(f).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
}
function formatBs(n) {
    if (n == null) return '—';
    return 'Bs. ' + Number(n).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

const cuotasPagadas   = computed(() => props.cuotas.filter(c => c.estado === 'pagado').length);
const cuotasPendientes = computed(() => props.cuotas.filter(c => c.estado === 'pendiente').length);
</script>

<template>
    <Head :title="`Pagos — ${estudiante.apellido} ${estudiante.nombre}`" />

    <AdminLayout>
        <template #header>
            <h2 class="text-base font-semibold" style="color:var(--text-color);">Caja y Pagos</h2>
        </template>

        <!-- Volver -->
        <div class="mb-6">
            <Link :href="route('secretaria.pagos.index')"
                class="inline-flex items-center gap-1.5 text-sm font-medium transition-opacity hover:opacity-70"
                style="color:var(--text-secondary);">
                ← Volver al listado
            </Link>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success" class="mb-5 rounded-lg p-4 text-sm font-medium"
             style="background-color:rgba(52,211,153,.15);color:#34d399;border:1px solid rgba(52,211,153,.3);">
            {{ $page.props.flash.success }}
        </div>

        <!-- Header estudiante -->
        <div class="rounded-xl p-5 mb-6 flex items-center gap-4"
             style="background-color:var(--card-bg);border:1px solid var(--border-color);">
            <div class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold shrink-0"
                 style="background-color:var(--primary-color);color:var(--primary-text);">
                {{ (estudiante.nombre[0] ?? '') + (estudiante.apellido[0] ?? '') }}
            </div>
            <div class="flex-1 min-w-0">
                <h1 class="text-lg font-bold truncate" style="color:var(--text-color);">{{ nombreCompleto }}</h1>
                <p class="text-xs" style="color:var(--text-secondary);">{{ estudiante.email }}
                    <span v-if="estudiante.dni"> · DNI: {{ estudiante.dni }}</span>
                    <span v-if="estudiante.legajo"> · Legajo: {{ estudiante.legajo }}</span>
                </p>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full font-medium shrink-0"
                  :style="estudiante.activo
                    ? 'background-color:rgba(52,211,153,.15);color:#34d399;'
                    : 'background-color:rgba(248,113,113,.15);color:#f87171;'">
                {{ estudiante.activo ? 'Activo' : 'Inactivo' }}
            </span>
        </div>

        <div class="space-y-6">

            <!-- ══ 1. MATRÍCULA ══════════════════════════════════════════ -->
            <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border-color);">
                <div class="px-6 py-4 flex items-center justify-between"
                     style="background-color:color-mix(in srgb,var(--card-bg) 80%,var(--border-color));">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest" style="color:var(--text-secondary);">Matrícula</p>
                        <p class="text-xs mt-0.5" style="color:var(--text-secondary);">Costo fijo: Bs. 500</p>
                    </div>
                    <button v-if="!pendiente.matricula && !matricula"
                        @click="showMatricula = true"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-opacity hover:opacity-80"
                        style="background-color:var(--primary-color);color:var(--primary-text);">
                        + Registrar matrícula
                    </button>
                </div>

                <div class="px-6 py-5" style="background-color:var(--card-bg);">
                    <!-- Pendiente de tabla -->
                    <div v-if="pendiente.matricula" class="text-center py-4 opacity-50">
                        <p class="text-sm" style="color:var(--text-color);">Módulo de matrículas pendiente de implementación en BD</p>
                    </div>
                    <!-- Sin matrícula -->
                    <div v-else-if="!matricula" class="text-center py-4">
                        <p class="text-sm" style="color:var(--text-secondary);">Este estudiante no tiene matrícula registrada.</p>
                    </div>
                    <!-- Con matrícula -->
                    <div v-else class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <p class="text-[11px] uppercase tracking-wide mb-1" style="color:var(--text-secondary);">Estado</p>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                  style="background-color:rgba(52,211,153,.15);color:#34d399;">
                                {{ matricula.estado }}
                            </span>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide mb-1" style="color:var(--text-secondary);">Monto</p>
                            <p class="text-sm font-semibold" style="color:var(--text-color);">{{ formatBs(matricula.monto_pagado) }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide mb-1" style="color:var(--text-secondary);">Comprobante</p>
                            <p class="text-xs font-mono" style="color:var(--text-color);">{{ matricula.comprobante ?? '—' }}</p>
                        </div>
                        <div v-if="matricula.fecha_pago">
                            <p class="text-[11px] uppercase tracking-wide mb-1" style="color:var(--text-secondary);">Fecha</p>
                            <p class="text-sm" style="color:var(--text-color);">{{ formatFecha(matricula.fecha_pago) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══ 2. PLAN DE CARRERA ════════════════════════════════════ -->
            <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border-color);">
                <div class="px-6 py-4 flex items-center justify-between"
                     style="background-color:color-mix(in srgb,var(--card-bg) 80%,var(--border-color));">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest" style="color:var(--text-secondary);">Plan de Carrera</p>
                        <p class="text-xs mt-0.5" style="color:var(--text-secondary);">Mín. 30% · 20% descuento pagando al contado</p>
                    </div>
                    <button v-if="!pendiente.carrera && !planCarrera"
                        @click="showCarrera = true"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-opacity hover:opacity-80"
                        style="background-color:var(--primary-color);color:var(--primary-text);">
                        + Registrar plan
                    </button>
                </div>

                <div class="px-6 py-5" style="background-color:var(--card-bg);">
                    <div v-if="pendiente.carrera" class="text-center py-4 opacity-50">
                        <p class="text-sm" style="color:var(--text-color);">Módulo de planes pendiente de implementación en BD</p>
                    </div>
                    <div v-else-if="!planCarrera" class="text-center py-4">
                        <p class="text-sm" style="color:var(--text-secondary);">Sin plan de carrera registrado.</p>
                    </div>

                    <!-- Plan existente -->
                    <div v-else>
                        <!-- Resumen del plan -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                            <div>
                                <p class="text-[11px] uppercase tracking-wide mb-1" style="color:var(--text-secondary);">Carrera</p>
                                <p class="text-sm font-semibold" style="color:var(--text-color);">
                                    {{ planCarrera.carrera_nombre ?? '—' }}
                                    <span class="font-mono text-[11px]">({{ planCarrera.carrera_codigo }})</span>
                                </p>
                            </div>
                            <div>
                                <p class="text-[11px] uppercase tracking-wide mb-1" style="color:var(--text-secondary);">Forma de pago</p>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium capitalize"
                                      :style="planCarrera.forma_pago === 'contado'
                                        ? 'background-color:rgba(52,211,153,.15);color:#34d399;'
                                        : 'background-color:rgba(99,102,241,.15);color:#818cf8;'">
                                    {{ planCarrera.forma_pago }}
                                </span>
                            </div>
                            <div>
                                <p class="text-[11px] uppercase tracking-wide mb-1" style="color:var(--text-secondary);">Pagado / Total</p>
                                <p class="text-sm font-semibold" style="color:var(--text-color);">
                                    {{ formatBs(planCarrera.monto_pagado) }}
                                    <span style="color:var(--text-secondary);"> / {{ formatBs(planCarrera.monto_total) }}</span>
                                </p>
                            </div>
                            <div>
                                <p class="text-[11px] uppercase tracking-wide mb-1" style="color:var(--text-secondary);">Estado</p>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium capitalize"
                                      :style="planCarrera.estado === 'pagado'
                                        ? 'background-color:rgba(52,211,153,.15);color:#34d399;'
                                        : 'background-color:rgba(245,158,11,.15);color:#f59e0b;'">
                                    {{ planCarrera.estado }}
                                </span>
                            </div>
                        </div>

                        <!-- Cuotas (solo si es crédito) -->
                        <div v-if="planCarrera.forma_pago === 'credito' && cuotas.length">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-[11px] font-semibold uppercase tracking-widest" style="color:var(--text-secondary);">
                                    Cuotas — {{ cuotasPagadas }}/{{ cuotas.length }} pagadas
                                </p>
                                <span v-if="cuotasPendientes > 0"
                                      class="text-[11px] px-2 py-0.5 rounded-full"
                                      style="background-color:rgba(245,158,11,.15);color:#f59e0b;">
                                    {{ cuotasPendientes }} pendiente{{ cuotasPendientes !== 1 ? 's' : '' }}
                                </span>
                            </div>
                            <div class="rounded-lg overflow-hidden" style="border:1px solid var(--border-color);">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr style="background-color:color-mix(in srgb,var(--card-bg) 60%,var(--border-color));">
                                            <th class="text-left px-4 py-2.5 text-[11px] font-semibold uppercase tracking-widest" style="color:var(--text-secondary);">Cuota</th>
                                            <th class="text-left px-4 py-2.5 text-[11px] font-semibold uppercase tracking-widest" style="color:var(--text-secondary);">Monto</th>
                                            <th class="text-left px-4 py-2.5 text-[11px] font-semibold uppercase tracking-widest" style="color:var(--text-secondary);">Fecha pago</th>
                                            <th class="text-center px-4 py-2.5 text-[11px] font-semibold uppercase tracking-widest" style="color:var(--text-secondary);">Estado</th>
                                            <th class="text-right px-4 py-2.5 text-[11px] font-semibold uppercase tracking-widest" style="color:var(--text-secondary);">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="c in cuotas" :key="c.numero_cuota"
                                            class="border-t" style="border-color:var(--border-color);">
                                            <td class="px-4 py-3 font-mono" style="color:var(--text-color);">#{{ c.numero_cuota }}</td>
                                            <td class="px-4 py-3" style="color:var(--text-color);">{{ formatBs(c.monto) }}</td>
                                            <td class="px-4 py-3 text-xs" style="color:var(--text-secondary);">{{ formatFecha(c.fecha_pago) }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="text-xs px-2 py-0.5 rounded-full font-medium capitalize"
                                                      :style="c.estado === 'pagado'
                                                        ? 'background-color:rgba(52,211,153,.15);color:#34d399;'
                                                        : 'background-color:rgba(245,158,11,.15);color:#f59e0b;'">
                                                    {{ c.estado }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <button v-if="c.estado === 'pendiente'"
                                                    @click="confirmarCuota(c)"
                                                    class="text-xs font-medium transition-opacity hover:opacity-70"
                                                    style="color:var(--primary-color);">
                                                    Registrar pago
                                                </button>
                                                <span v-else class="text-xs" style="color:var(--text-secondary);">—</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══ 3. MATERIAS SUELTAS ═══════════════════════════════════ -->
            <div class="rounded-xl overflow-hidden opacity-60" style="border:1px dashed var(--border-color);">
                <div class="px-6 py-4" style="background-color:color-mix(in srgb,var(--card-bg) 80%,var(--border-color));">
                    <p class="text-[11px] font-semibold uppercase tracking-widest" style="color:var(--text-secondary);">Materias sueltas</p>
                </div>
                <div class="px-6 py-8 text-center" style="background-color:var(--card-bg);">
                    <p class="text-sm font-medium mb-1" style="color:var(--text-color);">Disponible próximamente</p>
                    <p class="text-xs" style="color:var(--text-secondary);">Requiere CU6 (Inscripciones) para vincular pagos a materias específicas.</p>
                </div>
            </div>

        </div>

        <!-- ══ MODAL: Registrar Matrícula ════════════════════════════════ -->
        <div v-if="showMatricula"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
             @click.self="showMatricula = false">
            <div class="rounded-xl p-6 w-full max-w-sm mx-4"
                 style="background-color:var(--card-bg);border:1px solid var(--border-color);">
                <h3 class="text-base font-semibold mb-4" style="color:var(--text-color);">Registrar matrícula</h3>
                <p class="text-xs mb-5" style="color:var(--text-secondary);">{{ nombreCompleto }}</p>

                <label class="block text-xs font-medium mb-1" style="color:var(--text-secondary);">Monto (Bs.)</label>
                <input v-model.number="formMatricula.monto" type="number" min="1" step="0.01"
                    class="w-full rounded-lg px-3 py-2 text-sm mb-1 focus:outline-none"
                    style="background-color:color-mix(in srgb,var(--card-bg) 60%,var(--border-color));color:var(--text-color);border:1px solid var(--border-color);" />
                <p v-if="formMatricula.errors.monto" class="text-xs mb-3" style="color:#f87171;">{{ formMatricula.errors.monto }}</p>

                <label class="block text-xs font-medium mb-1 mt-3" style="color:var(--text-secondary);">Comprobante (opcional)</label>
                <input v-model="formMatricula.comprobante" type="text" placeholder="N° recibo o referencia…"
                    class="w-full rounded-lg px-3 py-2 text-sm mb-5 focus:outline-none"
                    style="background-color:color-mix(in srgb,var(--card-bg) 60%,var(--border-color));color:var(--text-color);border:1px solid var(--border-color);" />

                <div class="flex justify-end gap-3">
                    <button @click="showMatricula = false"
                        class="px-4 py-2 rounded-lg text-sm font-medium"
                        style="color:var(--text-secondary);border:1px solid var(--border-color);">
                        Cancelar
                    </button>
                    <button @click="guardarMatricula"
                        :disabled="formMatricula.processing"
                        class="px-4 py-2 rounded-lg text-sm font-medium disabled:opacity-50"
                        style="background-color:var(--primary-color);color:var(--primary-text);">
                        Registrar
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ MODAL: Registrar Plan Carrera ═════════════════════════════ -->
        <div v-if="showCarrera"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
             @click.self="showCarrera = false">
            <div class="rounded-xl p-6 w-full max-w-md mx-4"
                 style="background-color:var(--card-bg);border:1px solid var(--border-color);">
                <h3 class="text-base font-semibold mb-4" style="color:var(--text-color);">Registrar plan de carrera</h3>
                <p class="text-xs mb-5" style="color:var(--text-secondary);">{{ nombreCompleto }}</p>

                <!-- Select carrera -->
                <label class="block text-xs font-medium mb-1" style="color:var(--text-secondary);">Carrera</label>
                <select v-model="formCarrera.id_carrera"
                    class="w-full rounded-lg px-3 py-2 text-sm mb-1 focus:outline-none"
                    style="background-color:color-mix(in srgb,var(--card-bg) 60%,var(--border-color));color:var(--text-color);border:1px solid var(--border-color);">
                    <option value="">Seleccionar carrera…</option>
                    <option v-for="c in carrerasDisponibles" :key="c.id_carrera" :value="c.id_carrera">
                        {{ c.nombre }} ({{ c.codigo }}) — {{ formatBs(c.costo_carrera_completa) }}
                    </option>
                </select>
                <p v-if="formCarrera.errors.id_carrera" class="text-xs mb-3" style="color:#f87171;">{{ formCarrera.errors.id_carrera }}</p>

                <!-- Info de precios -->
                <div v-if="carreraSeleccionada" class="my-3 rounded-lg p-3 text-xs space-y-1"
                     style="background-color:color-mix(in srgb,var(--card-bg) 60%,var(--border-color));">
                    <div class="flex justify-between">
                        <span style="color:var(--text-secondary);">Precio total:</span>
                        <span style="color:var(--text-color);font-weight:600;">{{ formatBs(carreraSeleccionada.costo_carrera_completa) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color:var(--text-secondary);">Mínimo 30% (crédito):</span>
                        <span style="color:#818cf8;font-weight:600;">{{ formatBs(carreraSeleccionada.minimo_30) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color:var(--text-secondary);">Precio contado (20% desc.):</span>
                        <span style="color:#34d399;font-weight:600;">{{ formatBs(carreraSeleccionada.precio_contado) }}</span>
                    </div>
                </div>

                <!-- Monto a pagar -->
                <label class="block text-xs font-medium mb-1 mt-3" style="color:var(--text-secondary);">Monto a pagar (Bs.)</label>
                <input v-model="formCarrera.monto" type="number" min="0" step="0.01"
                    :placeholder="carreraSeleccionada ? `Mín. ${carreraSeleccionada.minimo_30}` : '—'"
                    class="w-full rounded-lg px-3 py-2 text-sm mb-1 focus:outline-none"
                    style="background-color:color-mix(in srgb,var(--card-bg) 60%,var(--border-color));color:var(--text-color);border:1px solid var(--border-color);" />
                <p v-if="formCarrera.errors.monto" class="text-xs" style="color:#f87171;">{{ formCarrera.errors.monto }}</p>

                <!-- Análisis del monto -->
                <div v-if="analisisPago" class="mt-2 rounded-lg px-3 py-2 text-xs font-medium"
                     :style="`background-color: color-mix(in srgb, ${analisisPago.color} 10%, transparent); color: ${analisisPago.color};`">
                    {{ analisisPago.tipo }}: {{ analisisPago.mensaje }}
                </div>

                <div class="flex justify-end gap-3 mt-5">
                    <button @click="showCarrera = false"
                        class="px-4 py-2 rounded-lg text-sm font-medium"
                        style="color:var(--text-secondary);border:1px solid var(--border-color);">
                        Cancelar
                    </button>
                    <button @click="guardarCarrera"
                        :disabled="formCarrera.processing || !formCarrera.id_carrera || !formCarrera.monto || (analisisPago && !analisisPago.valido)"
                        class="px-4 py-2 rounded-lg text-sm font-medium disabled:opacity-50"
                        style="background-color:var(--primary-color);color:var(--primary-text);">
                        Registrar plan
                    </button>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>
