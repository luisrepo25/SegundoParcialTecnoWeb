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

        <div class="form-page">
            <Link :href="route('oferta.show', carrera.id_carrera)" class="back-link">
                ← Volver a la carrera
            </Link>

            <div class="form-grid">

                <!-- Resumen de carrera -->
                <div class="summary-card">
                    <p class="summary-label">Tu elección</p>
                    <span class="badge" :class="carrera.tipo === 'curso_libre' ? 'badge-libre' : 'badge-ts'">
                        {{ TIPO_LABEL[carrera.tipo] ?? carrera.tipo }}
                    </span>
                    <h2 class="summary-name">{{ carrera.nombre }}</h2>
                    <p class="summary-dur">{{ carrera.duracion_niveles }} {{ carrera.duracion_unidad === 'meses' ? 'mes(es)' : 'nivel(es)' }}</p>

                    <div class="summary-prices">
                        <div class="price-row">
                            <span class="price-sub">Matrícula</span>
                            <span class="price-val">Bs. 500</span>
                        </div>
                        <div class="price-row">
                            <span class="price-sub">Carrera (total)</span>
                            <span class="price-big">Bs. {{ formatBs(carrera.costo_carrera_completa) }}</span>
                        </div>
                        <div class="summary-note">
                            <p>El pago de matrícula (Bs. 500) se realiza ahora con QR. El plan de carrera se gestiona con la secretaría.</p>
                        </div>
                    </div>
                </div>

                <!-- Formulario -->
                <div class="form-card">
                    <h1 class="form-title">Formulario de inscripción</h1>
                    <p class="form-sub">
                        Completá tus datos para pagar la matrícula y crear tu cuenta de acceso al sistema.
                    </p>

                    <div v-if="form.errors.general" class="form-error-box">
                        {{ form.errors.general }}
                    </div>

                    <form @submit.prevent="submit" class="form-fields">
                        <div class="field-pair">
                            <div class="field">
                                <label class="field-label">Nombre *</label>
                                <input v-model="form.nombre" type="text" placeholder="Juan" class="field-input" />
                                <p v-if="form.errors.nombre" class="field-error">{{ form.errors.nombre }}</p>
                            </div>
                            <div class="field">
                                <label class="field-label">Apellido *</label>
                                <input v-model="form.apellido" type="text" placeholder="Pérez" class="field-input" />
                                <p v-if="form.errors.apellido" class="field-error">{{ form.errors.apellido }}</p>
                            </div>
                        </div>

                        <div class="field-pair">
                            <div class="field">
                                <label class="field-label">DNI / CI *</label>
                                <input v-model="form.dni" type="text" placeholder="12345678" class="field-input" />
                                <p v-if="form.errors.dni" class="field-error">{{ form.errors.dni }}</p>
                            </div>
                            <div class="field">
                                <label class="field-label">Teléfono</label>
                                <input v-model="form.telefono" type="tel" placeholder="70000000" class="field-input" />
                            </div>
                        </div>

                        <div class="field">
                            <label class="field-label">Correo electrónico *</label>
                            <input v-model="form.email" type="email" placeholder="juan@correo.com" class="field-input" />
                            <p v-if="form.errors.email" class="field-error">{{ form.errors.email }}</p>
                            <p class="field-hint">Este correo será tu usuario de acceso al sistema.</p>
                        </div>

                        <div class="form-notice">
                            <p>
                                Al confirmar, se generará un <strong>código QR de pago</strong> para la matrícula (Bs. 500).
                                Después de escanear y pagar, recibirás tus credenciales de acceso.
                            </p>
                        </div>

                        <button type="submit" class="btn-submit" :disabled="form.processing">
                            {{ form.processing ? 'Generando QR...' : 'Continuar al pago →' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
.form-page { max-width: 56rem; margin: 0 auto; padding: 3rem 1.5rem 5rem; }
.back-link {
    font-size: 0.8rem; color: var(--text-secondary); text-decoration: none;
    display: inline-flex; align-items: center; gap: 0.3rem;
    margin-bottom: 2rem; transition: color 0.15s;
}
.back-link:hover { color: var(--primary-color); }

.form-grid { display: grid; grid-template-columns: 1fr 1.6fr; gap: 2rem; align-items: start; }

/* ── Resumen ── */
.summary-card {
    border-radius: var(--border-radius); border: 1px solid var(--border-color);
    background: var(--card-bg); padding: 1.5rem; position: sticky; top: 5rem;
}
.summary-label {
    font-size: 0.68rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--text-secondary); opacity: 0.7; margin-bottom: 1rem;
}
.badge {
    display: inline-block; font-size: 0.7rem; font-weight: 700;
    letter-spacing: 0.06em; text-transform: uppercase;
    padding: 0.2rem 0.6rem; border-radius: 999px;
}
.badge-ts { background: color-mix(in srgb, var(--primary-color) 12%, transparent); color: var(--primary-color); }
.badge-libre { background: color-mix(in srgb, #059669 12%, transparent); color: #059669; }
html[data-theme="youth"] .badge-libre { color: #34d399; background: color-mix(in srgb, #34d399 12%, transparent); }
.summary-name { font-size: 1.2rem; font-weight: 800; color: var(--text-color); margin-top: 0.75rem; line-height: 1.3; }
.summary-dur { font-size: 0.82rem; color: var(--text-secondary); margin-top: 0.4rem; }
.summary-prices {
    margin-top: 1.25rem; border-top: 1px solid var(--border-color); padding-top: 1.25rem;
    display: flex; flex-direction: column; gap: 0.75rem;
}
.price-row { display: flex; justify-content: space-between; align-items: center; }
.price-sub { font-size: 0.82rem; color: var(--text-secondary); }
.price-val { font-size: 0.95rem; font-weight: 700; color: var(--primary-color); }
.price-big { font-size: 0.95rem; font-weight: 700; color: var(--text-color); }
.summary-note { border-top: 1px solid var(--border-color); padding-top: 0.75rem; }
.summary-note p { font-size: 0.72rem; color: var(--text-secondary); line-height: 1.5; }

/* ── Formulario ── */
.form-card { border-radius: var(--border-radius); border: 1px solid var(--border-color); background: var(--card-bg); padding: 2rem; }
.form-title { font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin-bottom: 0.4rem; }
.form-sub { font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.75rem; line-height: 1.6; }
.form-error-box {
    border-radius: 0.6rem; background: color-mix(in srgb, #dc2626 10%, transparent);
    border: 1px solid color-mix(in srgb, #dc2626 25%, transparent);
    padding: 0.85rem 1rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: #dc2626;
}
.form-fields { display: flex; flex-direction: column; gap: 1.1rem; }
.field-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.field-label { display: block; font-size: 0.78rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 0.4rem; }
.field-input {
    width: 100%; box-sizing: border-box;
    background: var(--bg-color); border: 1px solid var(--border-color);
    border-radius: 0.5rem; padding: 0.65rem 0.85rem; font-size: 0.9rem;
    color: var(--text-color); outline: none; transition: border-color 0.15s;
    font-family: var(--font-family);
}
.field-input:focus { border-color: var(--primary-color); }
.field-input::placeholder { color: var(--text-secondary); opacity: 0.5; }
.field-error { font-size: 0.75rem; color: #dc2626; margin-top: 0.25rem; }
.field-hint { font-size: 0.72rem; color: var(--text-secondary); opacity: 0.8; margin-top: 0.25rem; }
.form-notice {
    border-radius: 0.6rem; background: color-mix(in srgb, var(--primary-color) 8%, transparent);
    border: 1px solid color-mix(in srgb, var(--primary-color) 20%, transparent);
    padding: 0.85rem 1rem;
}
.form-notice p { font-size: 0.8rem; color: var(--primary-color); line-height: 1.55; }
.btn-submit {
    margin-top: 0.5rem; padding: 0.8rem; border: none; border-radius: 0.65rem;
    font-size: 0.95rem; font-weight: 700; color: var(--primary-text);
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    cursor: pointer; transition: opacity 0.15s;
}
.btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }

@media (max-width: 640px) {
    .form-grid { grid-template-columns: 1fr; }
}
</style>
