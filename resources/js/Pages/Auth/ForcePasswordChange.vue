<script setup>
import AuthShell from '@/Components/Auth/AuthShell.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.change.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Cambiar Contraseña - Instituto San Pablo" />

    <AuthShell
        title="Instituto San Pablo del Oriente"
        subtitle="Actualización de seguridad requerida."
    >
        <template #hero>
            <p class="auth-eyebrow"><span class="auth-eyebrow-dot"></span>Seguridad de la Cuenta</p>
            <h1 class="auth-head-title">Actualización <em>requerida</em></h1>
            <p class="auth-head-sub">
                Por motivos de seguridad, detectamos que aún utilizas tu Cédula de Identidad (DNI) como contraseña.
            </p>

            <div class="login-pilares">
                <div class="login-pilar">
                    <span class="login-pilar-icon">🔒</span>
                    <div>
                        <strong>Contraseña Segura</strong>
                        <span>Debe tener al menos 6 caracteres e incluir un símbolo especial (@, $, !, %, *, #, ?, &, .).</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="auth-card">
            <div class="auth-card-head">
                <h2>Cambiar Contraseña</h2>
                <p>Establece una nueva contraseña para continuar navegando en el sistema.</p>
            </div>

            <form class="auth-form" @submit.prevent="submit">
                <div class="auth-field">
                    <InputLabel for="password" value="Nueva Contraseña" />
                    <TextInput
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autofocus
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="auth-field">
                    <InputLabel for="password_confirmation" value="Confirmar Contraseña" />
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <button
                    type="submit"
                    class="auth-submit"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Guardando...' : 'Guardar y Continuar' }}
                </button>
            </form>
        </div>
    </AuthShell>
</template>

<style scoped>
/* Pilares */
.login-pilares {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    width: 100%;
}
.login-pilar {
    display: flex;
    align-items: flex-start;
    gap: 0.9rem;
    padding: 0.9rem 1rem;
    border-radius: 0.9rem;
    border: 1px solid var(--border-color);
    background: var(--card-bg);
    text-align: left;
}
.login-pilar-icon {
    font-size: 1.25rem;
    margin-top: 0.1rem;
}
.login-pilar div {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}
.login-pilar strong {
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--text-color);
}
.login-pilar span {
    font-size: 0.8rem;
    color: var(--text-secondary);
    line-height: 1.4;
}
</style>
