<script setup>
import AuthShell from '@/Components/Auth/AuthShell.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: { type: String, default: '' },
});

const form = useForm({
    email: props.email,
    code:  '',
});

const submit = () => {
    form.post(route('password.verify'));
};
</script>

<template>
    <Head title="Verificar código" />

    <AuthShell
        title="Revisá tu correo"
        subtitle="Ingresá el código de 6 dígitos que enviamos a tu casilla."
    >
        <div class="auth-card">
            <div class="auth-card-head">
                <h2>Ingresar código</h2>
                <p>
                    Enviamos un código a
                    <strong>{{ form.email || 'tu correo' }}</strong>.
                    Válido por 15 minutos.
                </p>
            </div>

            <form class="auth-form" @submit.prevent="submit">
                <input type="hidden" v-model="form.email" />

                <div class="auth-field">
                    <InputLabel for="code" value="Código de verificación" />
                    <TextInput
                        id="code"
                        v-model="form.code"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        required
                        autofocus
                        autocomplete="one-time-code"
                        placeholder="000000"
                        style="font-size:1.6rem;letter-spacing:.5rem;text-align:center;font-weight:800;"
                    />
                    <InputError :message="form.errors.code" />
                </div>

                <button
                    type="submit"
                    class="auth-submit"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Verificando...' : 'Verificar código' }}
                </button>

                <p style="margin:0;text-align:center;font-size:0.9rem;color:#475569;">
                    ¿No llegó?
                    <Link :href="route('password.request')" class="auth-link">Solicitá otro</Link>
                </p>
            </form>
        </div>
    </AuthShell>
</template>
