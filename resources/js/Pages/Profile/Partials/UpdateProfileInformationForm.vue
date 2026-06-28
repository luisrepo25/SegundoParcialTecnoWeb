<script setup>
import { ref, computed } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const isEditing = ref(false);

const form = useForm({
    email: user.value.email,
    telefono: user.value.telefono || '',
    direccion: user.value.direccion || '',
});

const toggleEdit = () => {
    if (isEditing.value) {
        form.reset();
        form.clearErrors();
    }
    isEditing.value = !isEditing.value;
};

const emit = defineEmits(['profile-updated']);

const updateProfileInformation = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Update the form's default values to the newly saved ones
            form.defaults({
                email: user.value.email,
                telefono: user.value.telefono || '',
                direccion: user.value.direccion || '',
            });
            isEditing.value = false;
            emit('profile-updated');
        },
    });
};
</script>

<template>
    <section>
        <header class="flex justify-between items-center">
            <div>
                <h2 class="text-lg font-medium text-gray-900">
                    Información Personal
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Consulta y actualiza tus datos de contacto y perfil.
                </p>
            </div>
            <button 
                @click="toggleEdit"
                type="button"
                class="inline-flex items-center justify-center p-2 rounded-full transition-colors"
                :class="isEditing ? 'bg-gray-200 text-gray-600 hover:bg-gray-300' : 'bg-indigo-100 text-indigo-600 hover:bg-indigo-200'"
                :title="isEditing ? 'Cancelar edición' : 'Editar información'"
            >
                <svg v-if="!isEditing" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </header>

        <!-- Read-Only View -->
        <div v-if="!isEditing" class="mt-6 border-t border-gray-100">
            <dl class="divide-y divide-gray-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Nombre Completo</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ user.nombre }} {{ user.apellido }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Documento (DNI)</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ user.dni }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Correo Electrónico</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ user.email }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Teléfono</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ user.telefono || 'No registrado' }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Dirección</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ user.direccion || 'No registrada' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Edit Form -->
        <form
            v-else
            @submit.prevent="updateProfileInformation"
            class="mt-6 space-y-6"
        >
            <div class="bg-gray-50 px-4 py-3 rounded-lg text-sm text-gray-600 mb-6">
                <strong>Nota:</strong> Tu nombre completo y DNI no pueden ser modificados. Si necesitas corregirlos, por favor contacta a Secretaría.
            </div>

            <div>
                <InputLabel for="email" value="Correo Electrónico" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="telefono" value="Teléfono / Celular" />
                <TextInput
                    id="telefono"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.telefono"
                    autocomplete="tel"
                />
                <InputError class="mt-2" :message="form.errors.telefono" />
            </div>

            <div>
                <InputLabel for="direccion" value="Dirección Completa" />
                <TextInput
                    id="direccion"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.direccion"
                    autocomplete="street-address"
                />
                <InputError class="mt-2" :message="form.errors.direccion" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Guardar Cambios</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Guardado.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
