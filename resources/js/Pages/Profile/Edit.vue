<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdateProfilePhotoForm from './Partials/UpdateProfilePhotoForm.vue';
import UpdateProfileCvForm from './Partials/UpdateProfileCvForm.vue';
import { Head, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const user = page.props.auth.user;

const successMessage = ref('');
let timeoutId = null;

const setSuccessMessage = (message) => {
    successMessage.value = message;
    if (timeoutId) clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        successMessage.value = '';
    }, 4000);
};

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <Head title="Mi Perfil" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--text-color);">
                Mi Perfil
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                
                <!-- Link para volver atrás -->
                <div class="mb-2 px-4 sm:px-0">
                    <button @click="goBack" class="inline-flex items-center gap-2 text-sm font-medium transition-colors" style="color: var(--text-secondary);" onmouseover="this.style.color='var(--text-color)'" onmouseout="this.style.color='var(--text-secondary)'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver
                    </button>
                </div>
                
                <!-- Mensajes de éxito o error -->
                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-[-10px]"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-[-10px]"
                >
                    <div v-if="successMessage" class="px-4 py-3 rounded-lg flex items-center gap-3 mb-6 shadow-sm mx-4 sm:mx-0"
                         style="background-color: color-mix(in srgb, #22c55e 15%, transparent); border: 1px solid #86efac;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm font-medium text-green-800">{{ successMessage }}</p>
                    </div>
                </Transition>

                <div class="p-4 sm:p-8 shadow sm:rounded-lg" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <UpdateProfilePhotoForm class="max-w-xl mx-auto" @photo-updated="setSuccessMessage('¡Foto de perfil actualizada correctamente!')" />
                </div>
                
                <!-- Subida de CV (Solo para Docentes) -->
                <div v-if="user.role === 'profesor'" class="p-4 sm:p-8 shadow sm:rounded-lg" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <UpdateProfileCvForm class="max-w-xl" @cv-updated="setSuccessMessage('¡Curriculum Vitae actualizado exitosamente!')" />
                </div>

                <div class="p-4 shadow sm:rounded-lg sm:p-8" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                        @profile-updated="setSuccessMessage('¡Tus datos personales se han modificado correctamente!')"
                    />
                </div>

                <div class="p-4 sm:p-8 shadow sm:rounded-lg" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <UpdatePasswordForm class="max-w-xl" @password-updated="setSuccessMessage('¡Contraseña actualizada de forma segura!')" />
                </div>

                <div class="p-4 shadow sm:rounded-lg sm:p-8" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
