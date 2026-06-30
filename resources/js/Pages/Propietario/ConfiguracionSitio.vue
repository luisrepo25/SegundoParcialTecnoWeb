<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ config: Object });

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

const form = useForm({
    nombre_institucion: props.config.nombre_institucion ?? '',
    descripcion:        props.config.descripcion ?? '',
    email:              props.config.email ?? '',
    telefono_1:         props.config.telefono_1 ?? '',
    telefono_2:         props.config.telefono_2 ?? '',
    direccion:          props.config.direccion ?? '',
    facebook_url:       props.config.facebook_url ?? '',
    instagram_url:      props.config.instagram_url ?? '',
    youtube_url:        props.config.youtube_url ?? '',
    logo:               null,
});

const logoPreview = ref(props.config.logo_url ?? '/images/logo.png');
const fileInput   = ref(null);

function onLogoChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.logo = file;
    logoPreview.value = URL.createObjectURL(file);
}

function submit() {
    form.post(route('propietario.configuracion.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => { form.logo = null; },
    });
}
</script>

<template>
    <Head title="Configuración del Sitio" />
    <AdminLayout>
        <div class="page">
            <div class="page-head">
                <h1 class="page-title">Configuración del Sitio Público</h1>
                <p class="page-sub">Logo, datos de contacto y redes sociales del portal de inscripciones.</p>
            </div>

            <div v-if="flash.success" class="alert-success">{{ flash.success }}</div>

            <form @submit.prevent="submit" class="form-grid">

                <!-- ── Logo ── -->
                <section class="card">
                    <h2 class="card-title">Logo / Escudo</h2>
                    <div class="logo-row">
                        <img :src="logoPreview" alt="Logo" class="logo-preview" />
                        <div>
                            <button type="button" class="btn-outline" @click="fileInput.click()">Cambiar logo</button>
                            <input ref="fileInput" type="file" accept="image/png,image/jpeg,image/webp" class="hidden-input" @change="onLogoChange" />
                            <p class="hint">PNG, JPG o WEBP. Máx. 2 MB. Se mostrará en el portal público y en el footer.</p>
                            <p v-if="form.errors.logo" class="error">{{ form.errors.logo }}</p>
                        </div>
                    </div>
                </section>

                <!-- ── Datos institucionales ── -->
                <section class="card">
                    <h2 class="card-title">Datos de la institución</h2>
                    <div class="field">
                        <label>Nombre de la institución</label>
                        <input v-model="form.nombre_institucion" type="text" class="input" />
                        <p v-if="form.errors.nombre_institucion" class="error">{{ form.errors.nombre_institucion }}</p>
                    </div>
                    <div class="field">
                        <label>Descripción (footer del portal)</label>
                        <textarea v-model="form.descripcion" rows="3" class="input"></textarea>
                        <p v-if="form.errors.descripcion" class="error">{{ form.errors.descripcion }}</p>
                    </div>
                </section>

                <!-- ── Contacto ── -->
                <section class="card">
                    <h2 class="card-title">Contacto</h2>
                    <div class="field-row">
                        <div class="field">
                            <label>Correo electrónico</label>
                            <input v-model="form.email" type="email" class="input" placeholder="info@institucion.edu.bo" />
                            <p v-if="form.errors.email" class="error">{{ form.errors.email }}</p>
                        </div>
                        <div class="field">
                            <label>Dirección</label>
                            <input v-model="form.direccion" type="text" class="input" placeholder="Santa Cruz, Bolivia" />
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label>Teléfono 1</label>
                            <input v-model="form.telefono_1" type="text" class="input" placeholder="+591 3 300-0000" />
                        </div>
                        <div class="field">
                            <label>Teléfono 2</label>
                            <input v-model="form.telefono_2" type="text" class="input" placeholder="+591 70 000-000" />
                        </div>
                    </div>
                </section>

                <!-- ── Redes sociales ── -->
                <section class="card">
                    <h2 class="card-title">Redes sociales</h2>
                    <p class="hint" style="margin-bottom:0.85rem;">Deja vacío el campo si no aplica — el ícono no aparecerá en el portal.</p>
                    <div class="field">
                        <label>📘 Facebook (URL completa)</label>
                        <input v-model="form.facebook_url" type="url" class="input" placeholder="https://facebook.com/institutosanpablo" />
                        <p v-if="form.errors.facebook_url" class="error">{{ form.errors.facebook_url }}</p>
                    </div>
                    <div class="field">
                        <label>📷 Instagram (URL completa)</label>
                        <input v-model="form.instagram_url" type="url" class="input" placeholder="https://instagram.com/institutosanpablo" />
                        <p v-if="form.errors.instagram_url" class="error">{{ form.errors.instagram_url }}</p>
                    </div>
                    <div class="field">
                        <label>▶️ YouTube (URL completa)</label>
                        <input v-model="form.youtube_url" type="url" class="input" placeholder="https://youtube.com/@institutosanpablo" />
                        <p v-if="form.errors.youtube_url" class="error">{{ form.errors.youtube_url }}</p>
                    </div>
                </section>

                <div class="actions">
                    <button type="submit" class="btn-primary" :disabled="form.processing">
                        {{ form.processing ? 'Guardando...' : 'Guardar configuración' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
.page { max-width: 48rem; margin: 0 auto; padding: 1.5rem; }
.page-head { margin-bottom: 1.5rem; }
.page-title { font-size: 1.3rem; font-weight: 800; color: var(--text-color); }
.page-sub { font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.25rem; }

.alert-success {
    background: color-mix(in srgb, var(--primary-color) 12%, transparent);
    border: 1px solid color-mix(in srgb, var(--primary-color) 30%, transparent);
    color: var(--primary-color);
    padding: 0.75rem 1rem; border-radius: var(--border-radius, 0.5rem);
    font-size: 0.85rem; margin-bottom: 1.25rem;
}

.form-grid { display: flex; flex-direction: column; gap: 1.25rem; }
.card {
    background: var(--card-bg); border: 1px solid var(--border-color);
    border-radius: var(--border-radius, 0.5rem); padding: 1.5rem;
}
.card-title { font-size: 0.95rem; font-weight: 700; color: var(--text-color); margin-bottom: 1rem; }

.logo-row { display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; }
.logo-preview {
    width: 5rem; height: 5rem; object-fit: contain; border-radius: 0.6rem;
    border: 1px solid var(--border-color); background: var(--bg-color); padding: 0.4rem;
}
.hidden-input { display: none; }
.hint { font-size: 0.72rem; color: var(--text-secondary); margin-top: 0.4rem; }

.field { margin-bottom: 1rem; }
.field:last-child { margin-bottom: 0; }
.field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
.field label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-color); margin-bottom: 0.35rem; }
.input {
    width: 100%; box-sizing: border-box;
    border: 1px solid var(--border-color); border-radius: 0.4rem;
    padding: 0.55rem 0.75rem; font-size: 0.88rem;
    background: var(--bg-color); color: var(--text-color);
    font-family: inherit; outline: none; transition: border-color 0.15s;
}
.input:focus { border-color: var(--primary-color); }
.error { font-size: 0.72rem; color: #ef4444; margin-top: 0.3rem; }

.btn-outline {
    font-size: 0.8rem; font-weight: 600; padding: 0.5rem 1rem;
    border-radius: 0.4rem; border: 1.5px solid var(--primary-color);
    background: transparent; color: var(--primary-color); cursor: pointer;
    transition: all 0.15s;
}
.btn-outline:hover { background: var(--primary-color); color: var(--primary-text); }

.actions { display: flex; justify-content: flex-end; }
.btn-primary {
    font-size: 0.88rem; font-weight: 700; padding: 0.7rem 1.75rem;
    border-radius: 0.5rem; border: none;
    background: var(--primary-color); color: var(--primary-text);
    cursor: pointer; transition: background 0.15s;
}
.btn-primary:hover { background: var(--primary-hover); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

@media (max-width: 560px) {
    .field-row { grid-template-columns: 1fr; }
}
</style>
