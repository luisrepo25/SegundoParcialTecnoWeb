<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const assetUrl = computed(() => page.props.asset_url || '');

const form = useForm({
    cv: null,
});

const fileInput = ref(null);

const selectNewCv = () => {
    fileInput.value.click();
};

const emit = defineEmits(['cv-updated']);

const updateCv = () => {
    const cv = fileInput.value.files[0];
    if (!cv) return;

    form.cv = cv;
    form.post(route('profile.cv.update'), {
        preserveScroll: true,
        onSuccess: () => {
            clearCvFileInput();
            emit('cv-updated');
        },
    });
};

const deleteCv = () => {
    if (confirm('¿Estás seguro que deseas eliminar tu Curriculum Vitae?')) {
        form.delete(route('profile.cv.delete'), {
            preserveScroll: true,
            onSuccess: () => emit('cv-updated'),
        });
    }
};

const clearCvFileInput = () => {
    if (fileInput.value?.value) {
        fileInput.value.value = null;
    }
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium" style="color: var(--text-color);">
                Curriculum Vitae (CV)
            </h2>
            <p class="mt-1 text-sm" style="color: var(--text-secondary);">
                Sube tu Curriculum Vitae en formato PDF para que los estudiantes puedan visualizarlo. Tamaño máximo: 2MB.
            </p>
        </header>

        <div class="mt-6 flex flex-col sm:flex-row items-start sm:items-center gap-4">
            
            <!-- File Input (Hidden) -->
            <input
                ref="fileInput"
                type="file"
                class="hidden"
                accept="application/pdf"
                @change="updateCv"
            >

            <!-- Current CV State -->
            <div v-if="user.archivo_cv" class="flex items-center gap-4 p-4 rounded-lg border w-full sm:w-auto" style="border-color: var(--border-color); background-color: color-mix(in srgb, var(--primary-color) 5%, transparent);">
                <div class="p-3 rounded-full" style="background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold" style="color: var(--text-color);">CV Actual Cargado</p>
                    <a :href="assetUrl + '/cvs/' + user.archivo_cv" target="_blank" class="text-xs hover:underline" style="color: var(--primary-color);">
                        Ver documento PDF &rarr;
                    </a>
                </div>
            </div>
            
            <div v-else class="flex items-center gap-4 p-4 rounded-lg border w-full sm:w-auto border-dashed" style="border-color: var(--border-color); background-color: color-mix(in srgb, var(--text-secondary) 5%, transparent);">
                <div class="p-3 rounded-full bg-gray-100 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium" style="color: var(--text-secondary);">Ningún CV cargado</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2">
                <PrimaryButton @click.prevent="selectNewCv" :disabled="form.processing" class="h-10">
                    {{ user.archivo_cv ? 'Reemplazar PDF' : 'Subir PDF' }}
                </PrimaryButton>

                <DangerButton v-if="user.archivo_cv" @click="deleteCv" :disabled="form.processing" class="h-10 px-3" title="Eliminar CV">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </DangerButton>
            </div>
            
            <div v-if="form.processing" class="flex items-center text-sm" style="color: var(--primary-color);">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Procesando...
            </div>
        </div>

        <div class="mt-2 text-sm text-red-600" v-if="form.errors.cv">
            {{ form.errors.cv }}
        </div>
    </section>
</template>
