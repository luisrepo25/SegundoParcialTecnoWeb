<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import AuthShell from '@/Components/Auth/AuthShell.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const stats = [
    { valor: '9+',   etiqueta: 'Carreras técnicas' },
    { valor: '500+', etiqueta: 'Estudiantes activos' },
    { valor: '15+',  etiqueta: 'Años de experiencia' },
];

const pilares = [
    { icono: '🎓', titulo: 'Formación Técnica', desc: 'Carreras orientadas al mercado laboral de la región.' },
    { icono: '📋', titulo: 'Gestión Académica', desc: 'Seguimiento completo de inscripciones, notas y pagos.' },
    { icono: '🤝', titulo: 'Compromiso Social', desc: 'Educación accesible para jóvenes y adultos del oriente.' },
];
</script>

<template>
    <Head title="Bienvenido — Instituto San Pablo" />

    <AuthShell
        title="Instituto San Pablo del Oriente"
        subtitle="Sistema de gestión académica institucional."
    >
        <template #hero>
            <p class="auth-eyebrow">Sistema de Gestión Académica</p>
            <h1 class="login-title">Instituto<br><span class="login-title-highlight">San Pablo</span><br>del Oriente</h1>
            <p class="login-subtitle">
                Formación técnica de calidad en el corazón del oriente boliviano.
                Gestioná carreras, inscripciones, docentes y más desde un solo sistema.
            </p>

            <!-- Estadísticas del instituto -->
            <div class="login-stats">
                <div v-for="s in stats" :key="s.etiqueta" class="login-stat">
                    <strong>{{ s.valor }}</strong>
                    <span>{{ s.etiqueta }}</span>
                </div>
            </div>

            <!-- Pilares -->
            <div class="login-pilares">
                <div v-for="p in pilares" :key="p.titulo" class="login-pilar">
                    <span class="login-pilar-icon">{{ p.icono }}</span>
                    <div>
                        <strong>{{ p.titulo }}</strong>
                        <span>{{ p.desc }}</span>
                    </div>
                </div>
            </div>

            <!-- CTA oferta académica -->
            <div class="login-oferta-cta">
                <p class="login-oferta-label">¿Querés estudiar con nosotros?</p>
                <Link :href="route('oferta.index')" class="login-oferta-btn">
                    Ver oferta académica →
                </Link>
            </div>
        </template>

        <!-- Formulario de ingreso -->
        <div class="auth-card">
            <div class="auth-card-head">
                <h2>Iniciar sesión</h2>
                <p>Ingresá con tu cuenta institucional. El sistema te redirigirá al panel de tu rol.</p>
            </div>

            <div v-if="status" class="auth-status">{{ status }}</div>

            <form class="auth-form" @submit.prevent="submit">
                <div class="auth-field">
                    <InputLabel for="email" value="Correo institucional" />
                    <TextInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="usuario@sanpablo.edu.bo"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="auth-field">
                    <InputLabel for="password" value="Contraseña" />
                    <TextInput
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="auth-helper-row">
                    <label class="auth-checkbox">
                        <Checkbox name="remember" v-model:checked="form.remember" />
                        Recordarme
                    </label>
                    <Link v-if="canResetPassword" :href="route('password.request')" class="auth-link">
                        Olvidé mi contraseña
                    </Link>
                </div>

                <button
                    type="submit"
                    class="auth-submit"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Ingresando...' : 'Ingresar al sistema' }}
                </button>
            </form>
        </div>
    </AuthShell>
</template>

<style scoped>
.login-title {
    margin: 0;
    font-size: clamp(2.4rem, 3.8vw, 4rem);
    line-height: 1;
    letter-spacing: -0.04em;
    color: #f8fafc;
}
.login-title-highlight {
    background: linear-gradient(90deg, #38bdf8, #818cf8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.login-subtitle {
    margin: 0;
    max-width: 34rem;
    font-size: 1rem;
    line-height: 1.7;
    color: #94a3b8;
}

/* Stats */
.login-stats {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}
.login-stat {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}
.login-stat strong {
    font-size: 1.6rem;
    font-weight: 800;
    color: #f8fafc;
    letter-spacing: -0.03em;
}
.login-stat span {
    font-size: 0.78rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

/* Pilares */
.login-pilares {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding-top: 0.5rem;
}
.login-pilar {
    display: flex;
    align-items: flex-start;
    gap: 0.9rem;
    padding: 0.9rem 1rem;
    border-radius: 0.9rem;
    border: 1px solid rgba(148, 163, 184, 0.12);
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(10px);
}
.login-pilar-icon {
    font-size: 1.35rem;
    line-height: 1;
    margin-top: 1px;
    flex-shrink: 0;
}
.login-pilar strong {
    display: block;
    font-size: 0.9rem;
    font-weight: 600;
    color: #e2e8f0;
    margin-bottom: 0.15rem;
}
.login-pilar span {
    font-size: 0.82rem;
    color: #64748b;
    line-height: 1.5;
}

/* Oferta académica CTA */
.login-oferta-cta {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    padding-top: 0.5rem;
}
.login-oferta-label {
    font-size: 0.82rem;
    color: #475569;
    margin: 0;
    flex-shrink: 0;
}
.login-oferta-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.85rem;
    font-weight: 700;
    color: #0f172a;
    background: linear-gradient(135deg, #38bdf8, #818cf8);
    padding: 0.45rem 1.1rem;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: opacity 0.15s;
}
.login-oferta-btn:hover {
    opacity: 0.85;
}
</style>
