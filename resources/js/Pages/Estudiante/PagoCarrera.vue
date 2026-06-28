<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    transId:    { type: Number, required: true },
    qrImage:    { type: String, default: null },
    estado:     { type: String, default: 'pendiente' },
    planNombre: { type: String, default: 'Plan de carrera' },
    monto:      { type: Number, default: 0 },
});

const estadoActual   = ref(props.estado);
const tiempoRestante = ref(15 * 60);
let pollInterval   = null;
let countdownTimer = null;

function detenerTodo() {
    clearInterval(pollInterval);
    clearInterval(countdownTimer);
}

async function verificar() {
    try {
        const resp = await fetch(route('estudiante.pago.carrera.estado', props.transId));
        if (!resp.ok) return;
        const data = await resp.json();
        estadoActual.value = data.estado;
        if (data.estado === 'pagado' || data.estado === 'expirado') detenerTodo();
    } catch { /* reintentar */ }
}

onMounted(() => {
    if (estadoActual.value !== 'pendiente') return;
    verificar();
    pollInterval   = setInterval(verificar, 15_000);
    countdownTimer = setInterval(() => {
        tiempoRestante.value--;
        if (tiempoRestante.value <= 0) {
            estadoActual.value = 'expirado';
            detenerTodo();
        }
    }, 1_000);
});

onUnmounted(detenerTodo);

const mm = (s) => String(Math.floor(s / 60)).padStart(2, '0');
const ss = (s) => String(s % 60).padStart(2, '0');
</script>

<template>
    <Head title="Pago de Plan de Carrera" />
    <AuthenticatedLayout>
        <template #header>
            <span class="font-semibold text-lg" style="color: var(--text-color);">Pago de Plan de Carrera</span>
        </template>

        <div class="py-8 px-4 sm:px-6 lg:px-8 mx-auto max-w-2xl">

            <!-- ── PAGADO ── -->
            <div v-if="estadoActual === 'pagado'"
                 class="rounded-2xl p-8 text-center"
                 style="background-color: var(--card-bg); border: 2px solid #86efac;">
                <div class="flex items-center justify-center w-16 h-16 rounded-full mx-auto mb-4"
                     style="background-color: #dcfce7;">
                    <span class="text-3xl">✓</span>
                </div>
                <h2 class="text-2xl font-bold mb-2" style="color: #15803d;">¡Plan activado!</h2>
                <p class="text-sm mb-2" style="color: var(--text-secondary);">{{ planNombre }}</p>
                <p class="text-sm mb-6" style="color: var(--text-secondary);">
                    Tu plan de carrera está activo. Ya puedes inscribirte en las materias disponibles.
                </p>
                <Link :href="route('estudiante.panel')"
                      class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold text-sm"
                      style="background-color: var(--primary-color); color: var(--primary-text);">
                    Ir a inscribir materias
                </Link>
            </div>

            <!-- ── EXPIRADO ── -->
            <div v-else-if="estadoActual === 'expirado'"
                 class="rounded-2xl p-8 text-center"
                 style="background-color: var(--card-bg); border: 1px solid #fca5a5;">
                <div class="flex items-center justify-center w-16 h-16 rounded-full mx-auto mb-4"
                     style="background-color: #fee2e2;">
                    <span class="text-3xl">✗</span>
                </div>
                <h2 class="text-xl font-bold mb-2" style="color: #b91c1c;">QR expirado</h2>
                <p class="text-sm mb-6" style="color: var(--text-secondary);">El código QR venció. Vuelve al panel para generar uno nuevo.</p>
                <Link :href="route('estudiante.panel')"
                      class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold text-sm"
                      style="background-color: var(--primary-color); color: var(--primary-text);">
                    Volver al panel
                </Link>
            </div>

            <!-- ── PENDIENTE ── -->
            <div v-else class="space-y-5">

                <!-- Plan + monto -->
                <div class="rounded-xl px-5 py-4"
                     style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Plan seleccionado</p>
                    <p class="font-semibold mt-0.5" style="color: var(--text-color);">{{ planNombre }}</p>
                    <p class="text-2xl font-bold mt-2" style="color: var(--primary-color);">Bs. {{ monto.toFixed(2) }}</p>
                </div>

                <!-- QR -->
                <div class="rounded-xl p-6 text-center"
                     style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <p class="text-sm font-semibold mb-4" style="color: var(--text-color);">Escanea el QR con tu app bancaria</p>

                    <div v-if="qrImage" class="inline-block rounded-xl overflow-hidden p-3 mb-4"
                         style="background: white; border: 1px solid var(--border-color);">
                        <img :src="qrImage" alt="QR PagoFácil" class="w-52 h-52 object-contain" />
                    </div>
                    <div v-else class="w-52 h-52 mx-auto rounded-xl flex items-center justify-center mb-4"
                         style="background-color: color-mix(in srgb, var(--text-color) 5%, transparent); border: 1px dashed var(--border-color);">
                        <p class="text-sm" style="color: var(--text-secondary);">Generando QR...</p>
                    </div>

                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-mono font-bold"
                         :style="tiempoRestante < 120
                            ? 'background-color: #fee2e2; color: #b91c1c;'
                            : 'background-color: color-mix(in srgb, var(--primary-color) 10%, transparent); color: var(--primary-color);'">
                        <span>⏱</span> {{ mm(tiempoRestante) }}:{{ ss(tiempoRestante) }}
                    </div>
                    <p class="text-xs mt-3" style="color: var(--text-secondary);">Verificando automáticamente cada 15 segundos...</p>
                </div>

                <div class="text-center">
                    <Link :href="route('estudiante.panel')" class="text-sm" style="color: var(--text-secondary);">
                        ← Cancelar y volver al panel
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
