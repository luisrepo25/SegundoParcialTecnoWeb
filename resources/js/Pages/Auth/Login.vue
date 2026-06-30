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
</script>

<template>
    <Head title="Bienvenido — Instituto San Pablo" />

    <AuthShell
        title="Bienvenido de nuevo"
        subtitle="Accedé a tu panel según tu rol institucional: dirección, secretaría, docencia o estudiante."
    >
        <template #hero>
            <p class="auth-eyebrow"><span class="auth-eyebrow-dot"></span>Sistema de Gestión Académica</p>
            <h1 class="auth-head-title">Bienvenido <em>de nuevo</em></h1>
            <p class="auth-head-sub">Accedé a tu panel según tu rol institucional: dirección, secretaría, docencia o estudiante.</p>

            <div class="login-stats">
                <div v-for="s in stats" :key="s.etiqueta" class="login-stat">
                    <strong>{{ s.valor }}</strong>
                    <span>{{ s.etiqueta }}</span>
                </div>
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

        <!-- CTA oferta académica -->
        <div class="login-oferta-cta">
            <p class="login-oferta-label">¿Querés estudiar con nosotros?</p>
            <Link :href="route('oferta.index')" class="login-oferta-btn">
                Ver oferta académica →
            </Link>
        </div>
    </AuthShell>
</template>

<style scoped>
/* Stats */
.login-stats {
    display: flex;
    justify-content: center;
    gap: 2rem;
    flex-wrap: wrap;
}
.login-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.15rem;
}
.login-stat strong {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--text-color);
    letter-spacing: -0.03em;
}
.login-stat span {
    font-size: 0.7rem;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

/* Oferta académica CTA */
.login-oferta-cta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}
.login-oferta-label {
    font-size: 0.82rem;
    color: var(--text-secondary);
    margin: 0;
}
.login-oferta-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--primary-text);
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    padding: 0.45rem 1.1rem;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: opacity 0.15s;
}
.login-oferta-btn:hover {
    opacity: 0.85;
}
</style>
