<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import AuthShell from '@/Components/Auth/AuthShell.vue';
import RoleProfiles from '@/Components/Auth/RoleProfiles.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const metrics = [
    { value: '5', label: 'perfiles visuales' },
    { value: 'JWT', label: 'ingreso seguro' },
    { value: 'RBAC', label: 'rutas por rol' },
];

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Ingreso al sistema" />

    <AuthShell
        title="Ingreso seguro para cada rol"
        subtitle="Accede con JWT a un panel diseñado para tu perfil: administración, dirección, secretaría, docencia o estudiante."
    >
        <template #hero>
            <p class="auth-eyebrow">Acceso institucional</p>
            <h1>Un portal claro para cada actor del instituto.</h1>
            <p>
                La interfaz mantiene separado el comportamiento, los estilos y
                los componentes reutilizables para que el sistema escale sin
                perder orden.
            </p>

            <div class="auth-metrics">
                <div v-for="metric in metrics" :key="metric.label" class="auth-metric">
                    <strong>{{ metric.value }}</strong>
                    <span>{{ metric.label }}</span>
                </div>
            </div>

            <RoleProfiles />
        </template>

        <div class="auth-card">
            <div class="auth-card-head">
                <h2>Iniciar sesión</h2>
                <p>Ingresa con tu cuenta institucional y el sistema te llevará al panel de tu rol.</p>
            </div>

            <div v-if="status" class="auth-status">
                {{ status }}
            </div>

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
                        placeholder="usuario@instituto.com"
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

                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="auth-link"
                    >
                        Olvidé mi contraseña
                    </Link>
                </div>

                <PrimaryButton
                    class="auth-submit"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Entrar al sistema
                </PrimaryButton>
            </form>
        </div>
    </AuthShell>
</template>
