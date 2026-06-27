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
        <div style="max-width:44rem;margin:0 auto;padding:3rem 1.5rem 5rem;display:flex;flex-direction:column;align-items:center;gap:2rem;">

            <!-- ─── PAGADO ─── -->
            <template v-if="esPagado">
                <div style="text-align:center;width:100%;">
                    <div style="width:4rem;height:4rem;border-radius:50%;background:rgba(52,211,153,0.15);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.8rem;">✓</div>
                    <h1 style="font-size:1.8rem;font-weight:800;color:#34d399;letter-spacing:-0.03em;margin-bottom:0.5rem;">¡Pago confirmado!</h1>
                    <p style="font-size:0.95rem;color:#64748b;line-height:1.7;">Tu matrícula fue procesada exitosamente. Guardá tus credenciales para acceder al sistema.</p>
                </div>

                <!-- Card de credenciales -->
                <div v-if="credenciales" style="width:100%;border-radius:1rem;border:1px solid rgba(52,211,153,0.25);background:rgba(52,211,153,0.05);padding:1.75rem;">
                    <p style="font-size:0.68rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#34d399;margin-bottom:1.25rem;">Tus credenciales de acceso</p>

                    <div style="display:flex;flex-direction:column;gap:1rem;">
                        <!-- Email -->
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;background:rgba(0,0,0,0.3);border-radius:0.6rem;padding:0.85rem 1rem;">
                            <div>
                                <p style="font-size:0.68rem;color:#475569;margin-bottom:0.2rem;">Correo (usuario)</p>
                                <p style="font-size:0.95rem;font-weight:600;color:#f1f5f9;font-family:monospace;">{{ credenciales.email }}</p>
                            </div>
                            <button @click="copiar(credenciales.email, 'email')" type="button"
                                    style="font-size:0.75rem;color:#64748b;background:none;border:1px solid rgba(255,255,255,0.1);border-radius:0.4rem;padding:0.3rem 0.65rem;cursor:pointer;flex-shrink:0;transition:color 0.15s;"
                                    onmouseover="this.style.color='#94a3b8'" onmouseout="this.style.color='#64748b'">
                                {{ copiado === 'email' ? '✓ Copiado' : 'Copiar' }}
                            </button>
                        </div>

                        <!-- Password -->
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;background:rgba(0,0,0,0.3);border-radius:0.6rem;padding:0.85rem 1rem;">
                            <div>
                                <p style="font-size:0.68rem;color:#475569;margin-bottom:0.2rem;">Contraseña temporal</p>
                                <p style="font-size:1.1rem;font-weight:700;color:#38bdf8;font-family:monospace;letter-spacing:0.08em;">{{ credenciales.password }}</p>
                            </div>
                            <button @click="copiar(credenciales.password, 'pw')" type="button"
                                    style="font-size:0.75rem;color:#64748b;background:none;border:1px solid rgba(255,255,255,0.1);border-radius:0.4rem;padding:0.3rem 0.65rem;cursor:pointer;flex-shrink:0;transition:color 0.15s;"
                                    onmouseover="this.style.color='#94a3b8'" onmouseout="this.style.color='#64748b'">
                                {{ copiado === 'pw' ? '✓ Copiado' : 'Copiar' }}
                            </button>
                        </div>

                        <!-- Legajo -->
                        <div v-if="credenciales.legajo" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;background:rgba(0,0,0,0.3);border-radius:0.6rem;padding:0.85rem 1rem;">
                            <div>
                                <p style="font-size:0.68rem;color:#475569;margin-bottom:0.2rem;">Legajo</p>
                                <p style="font-size:0.95rem;font-weight:600;color:#f1f5f9;font-family:monospace;">{{ credenciales.legajo }}</p>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:1.25rem;border-radius:0.6rem;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.15);padding:0.85rem 1rem;">
                        <p style="font-size:0.8rem;color:#fbbf24;line-height:1.5;">
                            Anotá tu contraseña antes de cerrar esta página. Podés cambiarla desde tu perfil después de ingresar.
                        </p>
                    </div>
                </div>

                <Link :href="route('login')"
                      style="display:inline-flex;align-items:center;gap:0.5rem;font-size:0.95rem;font-weight:700;color:#0f172a;background:linear-gradient(135deg,#38bdf8,#818cf8);padding:0.8rem 2rem;border-radius:0.75rem;text-decoration:none;transition:opacity 0.15s;"
                      onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                    Ingresar al sistema →
                </Link>
            </template>

            <!-- ─── EXPIRADO ─── -->
            <template v-else-if="esExpirado">
                <div style="text-align:center;width:100%;">
                    <div style="width:4rem;height:4rem;border-radius:50%;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.8rem;">✕</div>
                    <h1 style="font-size:1.8rem;font-weight:800;color:#f87171;letter-spacing:-0.03em;margin-bottom:0.5rem;">QR expirado</h1>
                    <p style="font-size:0.95rem;color:#64748b;line-height:1.7;margin-bottom:2rem;">
                        El código QR venció después de 15 minutos sin confirmación. Podés iniciar el proceso nuevamente.
                    </p>
                    <Link :href="route('oferta.index')"
                          style="display:inline-flex;align-items:center;gap:0.5rem;font-size:0.9rem;font-weight:600;color:#f1f5f9;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);padding:0.7rem 1.5rem;border-radius:0.65rem;text-decoration:none;transition:background 0.15s;"
                          onmouseover="this.style.background='rgba(255,255,255,0.12)'" onmouseout="this.style.background='rgba(255,255,255,0.08)'">
                        ← Ver oferta académica
                    </Link>
                </div>
            </template>

            <!-- ─── PENDIENTE ─── -->
            <template v-else>
                <div style="text-align:center;">
                    <h1 style="font-size:1.6rem;font-weight:800;color:#f1f5f9;letter-spacing:-0.03em;margin-bottom:0.4rem;">Escanea el código QR</h1>
                    <p style="font-size:0.9rem;color:#64748b;">Usá tu banca móvil para pagar la matrícula</p>
                </div>

                <!-- QR Image -->
                <div style="border-radius:1.25rem;border:2px solid rgba(56,189,248,0.2);background:#fff;padding:1.25rem;display:flex;justify-content:center;align-items:center;min-height:16rem;min-width:16rem;">
                    <img v-if="qrImage" :src="qrImage" alt="Código QR de pago"
                         style="max-width:220px;max-height:220px;display:block;" />
                    <div v-else style="text-align:center;padding:2rem;color:#94a3b8;">
                        <p style="font-size:0.85rem;">Generando QR...</p>
                    </div>
                </div>

                <!-- Monto -->
                <div style="text-align:center;">
                    <p style="font-size:0.72rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#475569;margin-bottom:0.3rem;">Monto a pagar</p>
                    <p style="font-size:2rem;font-weight:800;color:#38bdf8;letter-spacing:-0.03em;">Bs. {{ formatBs(monto) }}</p>
                    <p style="font-size:0.78rem;color:#475569;margin-top:0.2rem;">Matrícula — Instituto San Pablo</p>
                </div>

                <!-- Countdown + estado -->
                <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;justify-content:center;">
                    <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.85rem;">
                        <span style="color:#475569;">QR válido por</span>
                        <span style="font-size:1rem;font-weight:700;color:#f1f5f9;font-family:monospace;">{{ minutos }}:{{ segsStr }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.4rem;">
                        <span style="width:0.55rem;height:0.55rem;border-radius:50%;background:#f59e0b;animation:pulse 1.5s infinite;display:inline-block;"></span>
                        <span style="font-size:0.82rem;color:#f59e0b;font-weight:600;">Esperando pago...</span>
                    </div>
                </div>

                <!-- Instrucciones -->
                <div style="width:100%;border-radius:0.9rem;border:1px solid rgba(255,255,255,0.07);background:rgba(15,23,42,0.6);padding:1.25rem 1.5rem;">
                    <p style="font-size:0.72rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#475569;margin-bottom:0.85rem;">Instrucciones</p>
                    <ol style="margin:0;padding-left:1.25rem;display:flex;flex-direction:column;gap:0.5rem;">
                        <li style="font-size:0.85rem;color:#94a3b8;">Abrí tu aplicación de banca móvil (ej: BancoSol, Banco Unión, etc.)</li>
                        <li style="font-size:0.85rem;color:#94a3b8;">Seleccioná la opción de pago con QR</li>
                        <li style="font-size:0.85rem;color:#94a3b8;">Apuntá la cámara al código QR de esta pantalla</li>
                        <li style="font-size:0.85rem;color:#94a3b8;">Confirmá el pago de Bs. {{ formatBs(monto) }}</li>
                        <li style="font-size:0.85rem;color:#94a3b8;">Esta página detectará el pago automáticamente</li>
                    </ol>
                </div>

                <p style="font-size:0.78rem;color:#334155;text-align:center;">
                    Verificando automáticamente cada 15 segundos · Matrícula: {{ email }}
                </p>
            </template>
        </div>
    </PublicLayout>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.35; }
}
</style>
