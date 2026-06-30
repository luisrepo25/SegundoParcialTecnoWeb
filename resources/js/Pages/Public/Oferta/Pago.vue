<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    transId: Number,
    qrImage: String,
    estado:  String,
    monto:   Number,
    email:   String,
});

// ── Estado reactivo ───────────────────────────────────────────────────────────
const estadoActual  = ref(props.estado === 'pagado' ? 'pagado' : (props.estado ?? 'pendiente'));
const credenciales  = ref(null);          // { email, password, legajo }
const segundos      = ref(15 * 60);       // countdown 15 minutos
const copiado       = ref(null);          // campo copiado recientemente
let   timerInterval = null;
let   pollInterval  = null;

const esExpirado = computed(() => estadoActual.value === 'expirado');
const esPagado   = computed(() => estadoActual.value === 'pagado');

// ── Countdown ─────────────────────────────────────────────────────────────────
const minutos = computed(() => String(Math.floor(segundos.value / 60)).padStart(2, '0'));
const segsStr = computed(() => String(segundos.value % 60).padStart(2, '0'));

function formatBs(n) {
    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 0 }).format(n ?? 0);
}

// ── Polling ───────────────────────────────────────────────────────────────────
async function verificar() {
    try {
        const resp = await fetch(route('oferta.estado', props.transId));
        const data = await resp.json();
        estadoActual.value = data.estado;

        if (data.estado === 'pagado') {
            credenciales.value = { email: data.email, password: data.password, legajo: data.legajo };
            detenerTodo();
        } else if (data.estado === 'expirado') {
            detenerTodo();
        }
    } catch {
        // Silenciar errores de red — seguir intentando
    }
}

function detenerTodo() {
    clearInterval(timerInterval);
    clearInterval(pollInterval);
}

async function copiar(texto, campo) {
    try {
        await navigator.clipboard.writeText(texto);
        copiado.value = campo;
        setTimeout(() => { copiado.value = null; }, 2000);
    } catch { /* clipboard no disponible */ }
}

onMounted(async () => {
    if (esPagado.value) {
        await verificar();
        return;
    }

    // Countdown timer (decrementa cada segundo)
    timerInterval = setInterval(() => {
        if (segundos.value > 0) {
            segundos.value--;
        } else {
            estadoActual.value = 'expirado';
            detenerTodo();
        }
    }, 1000);

    // Polling cada 15 segundos
    pollInterval = setInterval(verificar, 15_000);

    // Verificar inmediatamente al montar
    await verificar();
});

onUnmounted(detenerTodo);
</script>

<template>
    <Head title="Pago de Matrícula — Instituto San Pablo" />
    <PublicLayout>
        <div class="pago-page">

            <!-- ─── PAGADO ─── -->
            <template v-if="esPagado">
                <div class="pago-center">
                    <div class="pago-icon icon-ok">✓</div>
                    <h1 class="pago-title title-ok">¡Pago confirmado!</h1>
                    <p class="pago-sub">Tu matrícula fue procesada exitosamente. Guardá tus credenciales para acceder al sistema.</p>
                </div>

                <!-- Card de credenciales -->
                <div v-if="credenciales" class="cred-card">
                    <p class="cred-label">Tus credenciales de acceso</p>

                    <div class="cred-list">
                        <div class="cred-row">
                            <div>
                                <p class="cred-key">Correo (usuario)</p>
                                <p class="cred-val">{{ credenciales.email }}</p>
                            </div>
                            <button @click="copiar(credenciales.email, 'email')" type="button" class="btn-copy">
                                {{ copiado === 'email' ? '✓ Copiado' : 'Copiar' }}
                            </button>
                        </div>

                        <div class="cred-row">
                            <div>
                                <p class="cred-key">Contraseña temporal</p>
                                <p class="cred-val cred-pw">{{ credenciales.password }}</p>
                            </div>
                            <button @click="copiar(credenciales.password, 'pw')" type="button" class="btn-copy">
                                {{ copiado === 'pw' ? '✓ Copiado' : 'Copiar' }}
                            </button>
                        </div>

                        <div v-if="credenciales.legajo" class="cred-row">
                            <div>
                                <p class="cred-key">Legajo</p>
                                <p class="cred-val">{{ credenciales.legajo }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="cred-warn">
                        <p>Anotá tu contraseña antes de cerrar esta página. Podés cambiarla desde tu perfil después de ingresar.</p>
                    </div>
                </div>

                <Link :href="route('login')" class="btn-big">
                    Ingresar al sistema →
                </Link>
            </template>

            <!-- ─── EXPIRADO ─── -->
            <template v-else-if="esExpirado">
                <div class="pago-center">
                    <div class="pago-icon icon-err">✕</div>
                    <h1 class="pago-title title-err">QR expirado</h1>
                    <p class="pago-sub" style="margin-bottom:2rem;">
                        El código QR venció después de 15 minutos sin confirmación. Podés iniciar el proceso nuevamente.
                    </p>
                    <Link :href="route('oferta.index')" class="btn-outline">
                        ← Ver oferta académica
                    </Link>
                </div>
            </template>

            <!-- ─── PENDIENTE ─── -->
            <template v-else>
                <div class="pago-center">
                    <h1 class="pago-title">Escanea el código QR</h1>
                    <p class="pago-sub">Usá tu banca móvil para pagar la matrícula</p>
                </div>

                <!-- QR Image -->
                <div class="qr-box">
                    <img v-if="qrImage" :src="qrImage" alt="Código QR de pago" class="qr-img" />
                    <div v-else class="qr-loading">
                        <p>Generando QR...</p>
                    </div>
                </div>

                <!-- Monto -->
                <div class="pago-center">
                    <p class="monto-label">Monto a pagar</p>
                    <p class="monto-val">Bs. {{ formatBs(monto) }}</p>
                    <p class="monto-sub">Matrícula — Instituto San Pablo</p>
                </div>

                <!-- Countdown + estado -->
                <div class="estado-row">
                    <div class="countdown">
                        <span>QR válido por</span>
                        <span class="countdown-val">{{ minutos }}:{{ segsStr }}</span>
                    </div>
                    <div class="waiting">
                        <span class="waiting-dot"></span>
                        <span>Esperando pago...</span>
                    </div>
                </div>

                <!-- Instrucciones -->
                <div class="instr-box">
                    <p class="instr-label">Instrucciones</p>
                    <ol class="instr-list">
                        <li>Abrí tu aplicación de banca móvil (ej: BancoSol, Banco Unión, etc.)</li>
                        <li>Seleccioná la opción de pago con QR</li>
                        <li>Apuntá la cámara al código QR de esta pantalla</li>
                        <li>Confirmá el pago de Bs. {{ formatBs(monto) }}</li>
                        <li>Esta página detectará el pago automáticamente</li>
                    </ol>
                </div>

                <p class="poll-note">
                    Verificando automáticamente cada 15 segundos · Matrícula: {{ email }}
                </p>
            </template>
        </div>
    </PublicLayout>
</template>

<style scoped>
.pago-page {
    max-width: 44rem; margin: 0 auto; padding: 3rem 1.5rem 5rem;
    display: flex; flex-direction: column; align-items: center; gap: 2rem;
}
.pago-center { text-align: center; width: 100%; }
.pago-icon {
    width: 4rem; height: 4rem; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1rem; font-size: 1.8rem;
}
.icon-ok { background: color-mix(in srgb, #059669 15%, transparent); }
.icon-err { background: color-mix(in srgb, #dc2626 12%, transparent); }
.pago-title { font-size: 1.6rem; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 0.5rem; color: var(--text-color); }
.title-ok { color: #059669; }
html[data-theme="youth"] .title-ok { color: #34d399; }
.title-err { color: #dc2626; }
.pago-sub { font-size: 0.92rem; color: var(--text-secondary); line-height: 1.7; }

/* ── Credenciales ── */
.cred-card {
    width: 100%; border-radius: var(--border-radius);
    border: 1px solid color-mix(in srgb, #059669 25%, transparent);
    background: color-mix(in srgb, #059669 5%, var(--card-bg));
    padding: 1.75rem;
}
.cred-label {
    font-size: 0.68rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
    color: #059669; margin-bottom: 1.25rem;
}
html[data-theme="youth"] .cred-label { color: #34d399; }
.cred-list { display: flex; flex-direction: column; gap: 1rem; }
.cred-row {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    background: var(--bg-color); border: 1px solid var(--border-color);
    border-radius: 0.6rem; padding: 0.85rem 1rem;
}
.cred-key { font-size: 0.68rem; color: var(--text-secondary); margin-bottom: 0.2rem; }
.cred-val { font-size: 0.95rem; font-weight: 600; color: var(--text-color); font-family: monospace; }
.cred-pw { font-size: 1.1rem; font-weight: 700; color: var(--primary-color); letter-spacing: 0.08em; }
.btn-copy {
    font-size: 0.75rem; color: var(--text-secondary); background: none;
    border: 1px solid var(--border-color); border-radius: 0.4rem;
    padding: 0.3rem 0.65rem; cursor: pointer; flex-shrink: 0; transition: color 0.15s;
    font-family: var(--font-family);
}
.btn-copy:hover { color: var(--primary-color); border-color: var(--primary-color); }
.cred-warn {
    margin-top: 1.25rem; border-radius: 0.6rem;
    background: color-mix(in srgb, #f59e0b 8%, transparent);
    border: 1px solid color-mix(in srgb, #f59e0b 20%, transparent);
    padding: 0.85rem 1rem;
}
.cred-warn p { font-size: 0.8rem; color: #b45309; line-height: 1.5; }
html[data-theme="youth"] .cred-warn p { color: #fbbf24; }

.btn-big {
    display: inline-flex; align-items: center; gap: 0.5rem;
    font-size: 0.95rem; font-weight: 700; color: var(--primary-text);
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    padding: 0.8rem 2rem; border-radius: 0.75rem;
    text-decoration: none; transition: opacity 0.15s;
}
.btn-big:hover { opacity: 0.88; }
.btn-outline {
    display: inline-flex; align-items: center; gap: 0.5rem;
    font-size: 0.9rem; font-weight: 600; color: var(--text-color);
    background: var(--card-bg); border: 1px solid var(--border-color);
    padding: 0.7rem 1.5rem; border-radius: 0.65rem;
    text-decoration: none; transition: border-color 0.15s;
}
.btn-outline:hover { border-color: var(--primary-color); color: var(--primary-color); }

/* ── QR ── */
.qr-box {
    border-radius: 1.25rem; border: 2px solid color-mix(in srgb, var(--primary-color) 25%, transparent);
    background: #fff; padding: 1.25rem;
    display: flex; justify-content: center; align-items: center;
    min-height: 16rem; min-width: 16rem;
}
.qr-img { max-width: 220px; max-height: 220px; display: block; }
.qr-loading { text-align: center; padding: 2rem; color: var(--text-secondary); }
.qr-loading p { font-size: 0.85rem; }

/* ── Monto ── */
.monto-label {
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--text-secondary); opacity: 0.75; margin-bottom: 0.3rem;
}
.monto-val { font-size: 2rem; font-weight: 800; color: var(--primary-color); letter-spacing: -0.03em; }
.monto-sub { font-size: 0.78rem; color: var(--text-secondary); margin-top: 0.2rem; }

/* ── Countdown / estado ── */
.estado-row { display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; justify-content: center; }
.countdown { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-secondary); }
.countdown-val { font-size: 1rem; font-weight: 700; color: var(--text-color); font-family: monospace; }
.waiting { display: flex; align-items: center; gap: 0.4rem; }
.waiting span:last-child { font-size: 0.82rem; color: #f59e0b; font-weight: 600; }
.waiting-dot {
    width: 0.55rem; height: 0.55rem; border-radius: 50%;
    background: #f59e0b; animation: pulse 1.5s infinite; display: inline-block;
}

/* ── Instrucciones ── */
.instr-box {
    width: 100%; border-radius: 0.9rem; border: 1px solid var(--border-color);
    background: var(--card-bg); padding: 1.25rem 1.5rem;
}
.instr-label {
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--text-secondary); opacity: 0.7; margin-bottom: 0.85rem;
}
.instr-list { margin: 0; padding-left: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem; }
.instr-list li { font-size: 0.85rem; color: var(--text-secondary); }

.poll-note { font-size: 0.78rem; color: var(--text-secondary); opacity: 0.65; text-align: center; }

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.35; }
}
</style>
