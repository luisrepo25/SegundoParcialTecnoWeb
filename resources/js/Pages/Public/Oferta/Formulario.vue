<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({ carrera: Object });

const form = useForm({
    nombre:   '',
    apellido: '',
    dni:      '',
    email:    '',
    telefono: '',
});

const submit = () => {
    form.post(route('oferta.registrar', props.carrera.id_carrera));
};

const formatBs = (n) => new Intl.NumberFormat('es-BO', { minimumFractionDigits: 0 }).format(n ?? 0);

const TIPO_LABEL = {
    tecnico:          'Técnico',
    tecnico_superior: 'Técnico Superior',
    curso_libre:      'Curso Libre',
};
</script>

<template>
    <Head :title="'Inscripción — ' + carrera.nombre" />
    <PublicLayout>

        <div style="max-width:56rem;margin:0 auto;padding:3rem 1.5rem 5rem;">
            <!-- Volver -->
            <Link :href="route('oferta.show', carrera.id_carrera)"
                  style="font-size:0.8rem;color:#64748b;text-decoration:none;display:inline-flex;align-items:center;gap:0.3rem;margin-bottom:2rem;transition:color 0.15s;"
                  onmouseover="this.style.color='#94a3b8'" onmouseout="this.style.color='#64748b'">
                ← Volver a la carrera
            </Link>

            <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:2rem;align-items:start;" class="form-grid">

                <!-- Resumen de carrera -->
                <div style="border-radius:1rem;border:1px solid rgba(255,255,255,0.08);background:rgba(15,23,42,0.7);padding:1.5rem;position:sticky;top:5rem;">
                    <p style="font-size:0.68rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#475569;margin-bottom:1rem;">Tu elección</p>
                    <span style="font-size:0.7rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#818cf8;padding:0.2rem 0.6rem;background:rgba(129,140,248,0.12);border-radius:999px;">
                        {{ TIPO_LABEL[carrera.tipo] ?? carrera.tipo }}
                    </span>
                    <h2 style="font-size:1.2rem;font-weight:700;color:#f1f5f9;margin-top:0.75rem;line-height:1.3;">{{ carrera.nombre }}</h2>
                    <p style="font-size:0.82rem;color:#64748b;margin-top:0.4rem;">{{ carrera.duracion_niveles }} niveles</p>

                    <div style="margin-top:1.25rem;border-top:1px solid rgba(255,255,255,0.07);padding-top:1.25rem;display:flex;flex-direction:column;gap:0.75rem;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:0.82rem;color:#64748b;">Matrícula</span>
                            <span style="font-size:0.95rem;font-weight:700;color:#38bdf8;">Bs. 500</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:0.82rem;color:#64748b;">Carrera (total)</span>
                            <span style="font-size:0.95rem;font-weight:700;color:#f1f5f9;">Bs. {{ formatBs(carrera.costo_carrera_completa) }}</span>
                        </div>
                        <div style="border-top:1px solid rgba(255,255,255,0.07);padding-top:0.75rem;">
                            <p style="font-size:0.72rem;color:#475569;line-height:1.5;">
                                El pago de matrícula (Bs. 500) se realiza ahora con QR. El plan de carrera se gestiona con la secretaría.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Formulario -->
                <div style="border-radius:1rem;border:1px solid rgba(255,255,255,0.08);background:rgba(15,23,42,0.7);padding:2rem;">
                    <h1 style="font-size:1.3rem;font-weight:700;color:#f1f5f9;margin-bottom:0.4rem;">Formulario de inscripción</h1>
                    <p style="font-size:0.85rem;color:#64748b;margin-bottom:1.75rem;line-height:1.6;">
                        Completá tus datos para pagar la matrícula y crear tu cuenta de acceso al sistema.
                    </p>

                    <!-- Error general -->
                    <div v-if="form.errors.general"
                         style="border-radius:0.6rem;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);padding:0.85rem 1rem;margin-bottom:1.25rem;font-size:0.85rem;color:#f87171;">
                        {{ form.errors.general }}
                    </div>

                    <form @submit.prevent="submit" style="display:flex;flex-direction:column;gap:1.1rem;">
                        <!-- Nombre y Apellido -->
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div>
                                <label style="display:block;font-size:0.78rem;font-weight:600;color:#94a3b8;margin-bottom:0.4rem;">Nombre *</label>
                                <input v-model="form.nombre" type="text" placeholder="Juan"
                                       style="width:100%;box-sizing:border-box;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:0.5rem;padding:0.65rem 0.85rem;font-size:0.9rem;color:#f1f5f9;outline:none;transition:border-color 0.15s;"
                                       onfocus="this.style.borderColor='rgba(56,189,248,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'" />
                                <p v-if="form.errors.nombre" style="font-size:0.75rem;color:#f87171;margin-top:0.25rem;">{{ form.errors.nombre }}</p>
                            </div>
                            <div>
                                <label style="display:block;font-size:0.78rem;font-weight:600;color:#94a3b8;margin-bottom:0.4rem;">Apellido *</label>
                                <input v-model="form.apellido" type="text" placeholder="Pérez"
                                       style="width:100%;box-sizing:border-box;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:0.5rem;padding:0.65rem 0.85rem;font-size:0.9rem;color:#f1f5f9;outline:none;transition:border-color 0.15s;"
                                       onfocus="this.style.borderColor='rgba(56,189,248,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'" />
                                <p v-if="form.errors.apellido" style="font-size:0.75rem;color:#f87171;margin-top:0.25rem;">{{ form.errors.apellido }}</p>
                            </div>
                        </div>

                        <!-- DNI y Teléfono -->
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div>
                                <label style="display:block;font-size:0.78rem;font-weight:600;color:#94a3b8;margin-bottom:0.4rem;">DNI / CI *</label>
                                <input v-model="form.dni" type="text" placeholder="12345678"
                                       style="width:100%;box-sizing:border-box;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:0.5rem;padding:0.65rem 0.85rem;font-size:0.9rem;color:#f1f5f9;outline:none;transition:border-color 0.15s;"
                                       onfocus="this.style.borderColor='rgba(56,189,248,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'" />
                                <p v-if="form.errors.dni" style="font-size:0.75rem;color:#f87171;margin-top:0.25rem;">{{ form.errors.dni }}</p>
                            </div>
                            <div>
                                <label style="display:block;font-size:0.78rem;font-weight:600;color:#94a3b8;margin-bottom:0.4rem;">Teléfono</label>
                                <input v-model="form.telefono" type="tel" placeholder="70000000"
                                       style="width:100%;box-sizing:border-box;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:0.5rem;padding:0.65rem 0.85rem;font-size:0.9rem;color:#f1f5f9;outline:none;transition:border-color 0.15s;"
                                       onfocus="this.style.borderColor='rgba(56,189,248,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'" />
                            </div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label style="display:block;font-size:0.78rem;font-weight:600;color:#94a3b8;margin-bottom:0.4rem;">Correo electrónico *</label>
                            <input v-model="form.email" type="email" placeholder="juan@correo.com"
                                   style="width:100%;box-sizing:border-box;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:0.5rem;padding:0.65rem 0.85rem;font-size:0.9rem;color:#f1f5f9;outline:none;transition:border-color 0.15s;"
                                   onfocus="this.style.borderColor='rgba(56,189,248,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'" />
                            <p v-if="form.errors.email" style="font-size:0.75rem;color:#f87171;margin-top:0.25rem;">{{ form.errors.email }}</p>
                            <p style="font-size:0.72rem;color:#475569;margin-top:0.25rem;">Este correo será tu usuario de acceso al sistema.</p>
                        </div>

                        <!-- Aviso -->
                        <div style="border-radius:0.6rem;background:rgba(56,189,248,0.06);border:1px solid rgba(56,189,248,0.15);padding:0.85rem 1rem;">
                            <p style="font-size:0.8rem;color:#7dd3fc;line-height:1.55;">
                                Al confirmar, se generará un <strong>código QR de pago</strong> para la matrícula (Bs. 500).
                                Después de escanear y pagar, recibirás tus credenciales de acceso.
                            </p>
                        </div>

                        <button type="submit"
                                :disabled="form.processing"
                                style="margin-top:0.5rem;padding:0.8rem;border:none;border-radius:0.65rem;font-size:0.95rem;font-weight:700;color:#0f172a;background:linear-gradient(135deg,#38bdf8,#818cf8);cursor:pointer;transition:opacity 0.15s;"
                                :style="form.processing ? 'opacity:0.5;cursor:not-allowed;' : ''">
                            {{ form.processing ? 'Generando QR...' : 'Continuar al pago →' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
@media (max-width: 640px) {
    .form-grid { grid-template-columns: 1fr !important; }
}
</style>
