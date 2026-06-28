<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    transId:  { type: Number, required: true },
    qrImage:  { type: String, default: null },
    estado:   { type: String, default: 'pendiente' },
    concepto: { type: String, default: '' },
    monto:    { type: Number, default: 200 },
});

const estadoActual  = ref(props.estado);
const tiempoRestante = ref(15 * 60); // 15 minutos en segundos
let pollInterval   = null;
let countdownTimer = null;

function detenerTodo() {
    clearInterval(pollInterval);
    clearInterval(countdownTimer);
}

async function verificar() {
    try {
        const resp = await fetch(route('estudiante.pago.estado', props.transId));
        if (!resp.ok) return;
        const data = await resp.json();
        estadoActual.value = data.estado;
        if (data.estado === 'pagado' || data.estado === 'expirado') {
            detenerTodo();
        }
    } catch { /* red — reintentar en próximo ciclo */ }
}

onMounted(() => {
    if (estadoActual.value !== 'pendiente') return;

    // Verificar de inmediato
    verificar();

    // Polling cada 15 segundos
    pollInterval = setInterval(verificar, 15_000);

    // Cuenta regresiva
    countdownTimer = setInterval(() => {
        tiempoRestante.value--;
        if (tiempoRestante.value <= 0) {
            estadoActual.value = 'expirado';
            detenerTodo();
        }
    }, 1_000);
});

onUnmounted(detenerTodo);

const minutos  = (s) => String(Math.floor(s / 60)).padStart(2, '0');
const segundos = (s) => String(s % 60).padStart(2, '0');
</script>

<template>
    <Head title="Pago de Inscripción" />
    <AuthenticatedLayout>
        <template #header>
            <span class="font-semibold text-lg" style="color: var(--text-color);">Pago de Inscripción</span>
        </template>

        <div class="py-8 px-4 sm:px-6 lg:px-8 mx-auto max-w-2xl">

            <!-- ── PAGADO ── -->
            <div v-if="estadoActual === 'pagado'"
                 class="rounded-2xl p-8 text-center"
                 style="background-color: var(--card-bg); border: 1px solid #86efac;">
                <div class="flex items-center justify-center w-16 h-16 rounded-full mx-auto mb-4"
                     style="background-color: #dcfce7;">
                    <span class="text-3xl">✓</span>
                </div>
                <h2 class="text-2xl font-bold mb-2" style="color: #15803d;">¡Inscripción confirmada!</h2>
                <p v-if="concepto" class="text-sm mb-6" style="color: var(--text-secondary);">{{ concepto }}</p>
                <p class="text-sm mb-6" style="color: var(--text-secondary);">
                    Tu inscripción ha sido registrada y ya está activa. Puedes verla en "Mis Inscripciones".
                </p>
                <Link :href="route('estudiante.panel')"
                      class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold text-sm transition-all"
                      style="background-color: var(--primary-color); color: var(--primary-text);">
                    Ir a mi panel
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
                <p class="text-sm mb-6" style="color: var(--text-secondary);">
                    El código QR venció sin registrar pago. Vuelve a iniciar el proceso desde tu panel.
                </p>
                <Link :href="route('estudiante.panel')"
                      class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold text-sm transition-all"
                      style="background-color: var(--primary-color); color: var(--primary-text);">
                    Volver al panel
                </Link>
            </div>

            <!-- ── PENDIENTE ── -->
            <div v-else class="space-y-5">

                <!-- Concepto + monto -->
                <div class="rounded-xl px-5 py-4"
                     style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Concepto</p>
                    <p class="font-semibold mt-0.5" style="color: var(--text-color);">{{ concepto || 'Inscripción de materia' }}</p>
                    <p class="text-2xl font-bold mt-2" style="color: var(--primary-color);">
                        Bs. {{ monto.toFixed(2) }}
                    </p>
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

                    <!-- Cuenta regresiva -->
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-mono font-bold"
                         :style="tiempoRestante < 120
                            ? 'background-color: #fee2e2; color: #b91c1c;'
                            : 'background-color: color-mix(in srgb, var(--primary-color) 10%, transparent); color: var(--primary-color);'">
                        <span>⏱</span>
                        {{ minutos(tiempoRestante) }}:{{ segundos(tiempoRestante) }}
                    </div>

                    <p class="text-xs mt-3" style="color: var(--text-secondary);">
                        Verificando el pago automáticamente cada 15 segundos...
                    </p>
                </div>

                <!-- Instrucciones -->
                <div class="rounded-xl px-5 py-4"
                     style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <p class="text-sm font-semibold mb-3" style="color: var(--text-color);">¿Cómo pagar?</p>
                    <ol class="space-y-1.5 text-sm" style="color: var(--text-secondary);">
                        <li class="flex gap-2"><span class="font-bold shrink-0" style="color: var(--primary-color);">1.</span> Abre la app de tu banco (BNB, Banco Unión, Banco Sol, etc.)</li>
                        <li class="flex gap-2"><span class="font-bold shrink-0" style="color: var(--primary-color);">2.</span> Busca la opción "Pago QR" o "Escanear QR"</li>
                        <li class="flex gap-2"><span class="font-bold shrink-0" style="color: var(--primary-color);">3.</span> Escanea el código y confirma el monto de Bs. {{ monto.toFixed(2) }}</li>
                        <li class="flex gap-2"><span class="font-bold shrink-0" style="color: var(--primary-color);">4.</span> Esta página se actualizará sola al confirmar el pago</li>
                    </ol>
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
