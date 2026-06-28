<script setup>
import AuthShell from '@/Components/Auth/AuthShell.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    email: { type: String, required: true },
});

const form = useForm({
    password:              '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Nueva contraseña" />

    <AuthShell
        title="Nueva contraseña"
        subtitle="Elegí una contraseña segura para tu cuenta."
    >
        <div class="auth-card">
            <div class="auth-card-head">
                <h2>Establecer contraseña</h2>
                <p>Ingresá y confirmá tu nueva contraseña para <strong>{{ email }}</strong>.</p>
            </div>

            <form class="auth-form" @submit.prevent="submit">
                <div class="auth-field">
                    <InputLabel for="password" value="Nueva contraseña" />
                    <TextInput
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autofocus
                        autocomplete="new-password"
                        placeholder="Mínimo 8 caracteres"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="auth-field">
                    <InputLabel for="password_confirmation" value="Confirmar contraseña" />
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="Repetí la contraseña"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <button
                    type="submit"
                    class="auth-submit"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Guardando...' : 'Guardar nueva contraseña' }}
                </button>
            </form>
        </div>
    </AuthShell>
</template>
