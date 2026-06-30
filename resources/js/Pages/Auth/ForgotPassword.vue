<script setup>
import AuthShell from '@/Components/Auth/AuthShell.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: { type: String },
});

const form = useForm({ email: '' });

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Recuperar contraseña" />

    <AuthShell
        title="¿Olvidaste tu contraseña?"
        subtitle="Ingresá tu correo y te enviaremos un enlace para restablecer tu acceso."
    >
        <div class="auth-card">
            <div class="auth-card-head">
                <h2>Recuperar contraseña</h2>
                <p>Recibirás un código por correo válido por 15 minutos.</p>
            </div>

            <div v-if="status" class="auth-status">{{ status }}</div>

            <form class="auth-form" @submit.prevent="submit">
                <div class="auth-field">
                    <InputLabel for="email" value="Correo electrónico" />
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

                <button
                    type="submit"
                    class="auth-submit"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Enviando...' : 'Enviar enlace de recuperación' }}
                </button>

                <p style="margin: 0; text-align: center; font-size: 0.9rem; color: var(--text-secondary);">
                    <Link :href="route('login')" class="auth-link">Volver al inicio de sesión</Link>
                </p>
            </form>
        </div>
    </AuthShell>
</template>
