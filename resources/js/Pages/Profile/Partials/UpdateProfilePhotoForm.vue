<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
const assetUrl = computed(() => page.props.asset_url || '');

const form = useForm({
    foto: null,
});

const fileInput = ref(null);

const selectNewPhoto = () => {
    fileInput.value.click();
};

const emit = defineEmits(['photo-updated']);

const updatePhoto = () => {
    const photo = fileInput.value.files[0];
    if (!photo) return;

    form.foto = photo;
    form.post(route('profile.photo.update'), {
        preserveScroll: true,
        onSuccess: () => {
            clearPhotoFileInput();
            emit('photo-updated');
        },
    });
};

const clearPhotoFileInput = () => {
    if (fileInput.value?.value) {
        fileInput.value.value = null;
    }
};
</script>

<template>
    <section class="flex flex-col items-center justify-center py-6 text-center">
        <!-- Photo Container -->
        <div class="relative group">
            <div class="h-32 w-32 rounded-full overflow-hidden border-4 border-white shadow-lg bg-gray-200 flex items-center justify-center text-4xl font-bold text-gray-400 uppercase">
                <img 
                    v-if="user.foto_perfil" 
                    :src="assetUrl + '/imagenes/' + user.foto_perfil" 
                    :alt="user.nombre" 
                    class="h-full w-full object-cover"
                >
                <span v-else>{{ user.nombre.charAt(0) }}{{ user.apellido.charAt(0) }}</span>
                
                <!-- Loading overlay -->
                <div v-if="form.processing" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                    <svg class="animate-spin h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>

            <!-- Edit Button (Pencil) -->
            <button 
                @click.prevent="selectNewPhoto"
                :disabled="form.processing"
                class="absolute bottom-0 right-0 bg-indigo-600 rounded-full p-2.5 text-white shadow hover:bg-indigo-700 transition cursor-pointer disabled:opacity-50"
                title="Cambiar Foto"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                </svg>
            </button>
        </div>

        <!-- Hidden File Input -->
        <input
            ref="fileInput"
            type="file"
            class="hidden"
            accept="image/jpeg, image/png, image/jpg"
            @change="updatePhoto"
        >

        <!-- User Information -->
        <div class="mt-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ user.nombre }} {{ user.apellido }}</h2>
            <p class="text-gray-600 font-medium mt-1">{{ user.email }}</p>
            <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-indigo-100 text-indigo-800">
                <span class="capitalize">{{ user.role === 'profesor' ? 'Docente' : (user.role === 'estudiante' ? 'Estudiante' : user.role) }}</span>
                <span class="ml-2 pl-2 border-l border-indigo-300">DNI: {{ user.dni }}</span>
            </div>
        </div>
        
        <div class="mt-2 text-sm text-red-600" v-if="form.errors.foto">
            {{ form.errors.foto }}
        </div>
    </section>
</template>
