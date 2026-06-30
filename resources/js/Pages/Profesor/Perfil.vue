<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage, Link, router } from '@inertiajs/vue3';
import DocenteLayout from '@/Layouts/DocenteLayout.vue';

const props = defineProps({
    perfil: { type: Object, required: true },
});

const page     = usePage();
const flash    = computed(() => page.props.flash ?? {});
const errors   = computed(() => page.props.errors ?? {});
const seccion  = ref('datos');
const user     = computed(() => page.props.auth.user);
const assetUrl = computed(() => page.props.asset_url || '');

// ── Foto de perfil ───────────────────────────────────────────────────────────
const fileInput = ref(null);
const formFoto  = useForm({ foto: null });

function selectNewPhoto() {
    fileInput.value.click();
}

function updatePhoto() {
    const photo = fileInput.value.files[0];
    if (!photo) return;
    formFoto.foto = photo;
    formFoto.post(route('profile.photo.update'), {
        preserveScroll: true,
        onSuccess: () => { if (fileInput.value) fileInput.value.value = null; },
    });
}

// ── CV ───────────────────────────────────────────────────────────────────────
const cvInput    = ref(null);
const cvLoading  = ref(false);
const cvDeleting = ref(false);

function selectCv() { cvInput.value?.click(); }

function uploadCv() {
    const file = cvInput.value?.files?.[0];
    if (!file) return;
    cvLoading.value = true;
    const data = new FormData();
    data.append('cv', file);
    router.post(route('profile.cv.update'), data, {
        preserveScroll: true,
        onFinish: () => {
            cvLoading.value = false;
            if (cvInput.value) cvInput.value.value = null;
        },
    });
}

function eliminarCv() {
    if (!confirm('¿Eliminar el CV cargado?')) return;
    cvDeleting.value = true;
    router.delete(route('profile.cv.delete'), {
        preserveScroll: true,
        onFinish: () => { cvDeleting.value = false; },
    });
}

// ── Formulario datos personales ──────────────────────────────────────────────
const isEditing = ref(false);

const formDatos = useForm({
    nombre:    props.perfil.nombre    ?? '',
    apellido:  props.perfil.apellido  ?? '',
    email:     props.perfil.email     ?? '',
    telefono:  props.perfil.telefono  ?? '',
    direccion: props.perfil.direccion ?? '',
});

function toggleEditing() {
    if (isEditing.value) {
        formDatos.reset();
        formDatos.clearErrors();
    }
    isEditing.value = !isEditing.value;
}

function guardarDatos() {
    formDatos.put(route('profesor.perfil.update'), {
        preserveScroll: true,
        onSuccess: () => { isEditing.value = false; },
    });
}

// ── Formulario cambio de contraseña ─────────────────────────────────────────
const formPwd = useForm({
    password_actual:             '',
    password_nuevo:              '',
    password_nuevo_confirmation: '',
});

function cambiarPassword() {
    formPwd.put(route('profesor.perfil.password'), {
        preserveScroll: true,
        onSuccess: () => formPwd.reset(),
    });
}
</script>

<template>
    <Head title="Mi Perfil" />
    <DocenteLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('dashboard.profesor')"
                      class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors"
                      style="color: var(--text-secondary); background-color: color-mix(in srgb, var(--text-color) 8%, transparent);"
                      title="Volver al panel">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <span class="font-semibold text-lg" style="color: var(--text-color);">Mi Perfil</span>
            </div>
        </template>

        <div class="py-8 px-4 sm:px-6 lg:px-8 mx-auto max-w-3xl space-y-5">

            <!-- Flash -->
            <div v-if="flash.success"
                 class="rounded-lg px-4 py-3 text-sm font-medium"
                 style="background-color: #dcfce7; color: #15803d; border: 1px solid #86efac;">
                {{ flash.success }}
            </div>

            <!-- ── Encabezado con foto ── -->
            <div class="rounded-xl p-5 flex items-center gap-4"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">

                <div class="relative shrink-0">
                    <div class="w-16 h-16 rounded-full overflow-hidden flex items-center justify-center text-2xl font-bold"
                         style="background-color: var(--primary-color); color: var(--primary-text);">
                        <img v-if="user.foto_perfil"
                             :src="assetUrl + '/imagenes/' + user.foto_perfil"
                             alt="Foto de perfil"
                             class="w-full h-full object-cover" />
                        <span v-else>{{ (perfil.nombre ?? 'D')[0].toUpperCase() }}</span>
                        <div v-if="formFoto.processing"
                             class="absolute inset-0 bg-black/50 flex items-center justify-center rounded-full">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                        </div>
                    </div>
                    <button @click.prevent="selectNewPhoto" :disabled="formFoto.processing"
                            class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full flex items-center justify-center shadow transition-opacity disabled:opacity-50"
                            style="background-color: var(--primary-color); color: var(--primary-text);"
                            title="Cambiar foto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                        </svg>
                    </button>
                    <input ref="fileInput" type="file" class="hidden"
                           accept="image/jpeg,image/png,image/jpg"
                           @change="updatePhoto" />
                </div>

                <div class="flex-1 min-w-0">
                    <h1 class="text-xl font-bold truncate" style="color: var(--text-color);">
                        {{ perfil.nombre }} {{ perfil.apellido }}
                    </h1>
                    <p class="text-sm" style="color: var(--text-secondary);">{{ perfil.email }}</p>
                    <span class="inline-block mt-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                          style="background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);">
                        Docente
                    </span>
                </div>
            </div>

            <!-- ── Datos fijos (solo lectura) ── -->
            <div class="rounded-xl p-5" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <p class="text-xs font-semibold uppercase tracking-wide mb-3" style="color: var(--text-secondary);">Información de cuenta</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div>
                        <p style="color: var(--text-secondary);">DNI / CI</p>
                        <p class="font-medium" style="color: var(--text-color);">{{ perfil.dni ?? '—' }}</p>
                    </div>
                    <div>
                        <p style="color: var(--text-secondary);">Rol</p>
                        <p class="font-medium" style="color: var(--text-color);">Docente</p>
                    </div>
                    <div v-if="perfil.especialidad">
                        <p style="color: var(--text-secondary);">Especialidad</p>
                        <p class="font-medium" style="color: var(--text-color);">{{ perfil.especialidad }}</p>
                    </div>
                </div>
            </div>

            <!-- ── Currículum Vitae ── -->
            <div class="rounded-xl p-5" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Currículum Vitae</p>
                    <div class="flex items-center gap-2">
                        <button v-if="perfil.archivo_cv" @click="eliminarCv" :disabled="cvDeleting"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all disabled:opacity-50"
                                style="background-color: color-mix(in srgb, #ef4444 10%, transparent); color: #ef4444;">
                            <svg v-if="!cvDeleting" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <svg v-else class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Eliminar
                        </button>
                        <button @click="selectCv" :disabled="cvLoading"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all disabled:opacity-50"
                                style="background-color: color-mix(in srgb, var(--primary-color) 12%, transparent); color: var(--primary-color);">
                            <svg v-if="!cvLoading" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <svg v-else class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ perfil.archivo_cv ? 'Reemplazar' : 'Subir CV' }}
                        </button>
                        <input ref="cvInput" type="file" class="hidden" accept="application/pdf" @change="uploadCv" />
                    </div>
                </div>

                <!-- CV existente -->
                <div v-if="perfil.archivo_cv"
                     class="flex items-center gap-3 p-3 rounded-lg"
                     style="background-color: color-mix(in srgb, var(--text-color) 4%, transparent); border: 1px solid var(--border-color);">
                    <div class="w-8 h-8 rounded-md flex items-center justify-center shrink-0"
                         style="background-color: color-mix(in srgb, #ef4444 12%, transparent);">
                        <svg class="w-4 h-4" style="color: #ef4444;" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate" style="color: var(--text-color);">{{ perfil.archivo_cv }}</p>
                        <p class="text-xs" style="color: var(--text-secondary);">PDF · Currículum Vitae</p>
                    </div>
                    <a :href="(assetUrl || '') + '/cvs/' + perfil.archivo_cv" target="_blank"
                       class="flex items-center gap-1 text-xs font-medium shrink-0 px-2.5 py-1.5 rounded-md transition-all"
                       style="color: var(--primary-color); background-color: color-mix(in srgb, var(--primary-color) 8%, transparent);">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"/>
                            <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"/>
                        </svg>
                        Ver
                    </a>
                </div>

                <!-- Sin CV -->
                <div v-else class="flex items-center gap-3 p-3 rounded-lg text-sm"
                     style="background-color: color-mix(in srgb, var(--text-color) 4%, transparent); color: var(--text-secondary); border: 1px dashed var(--border-color);">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                    </svg>
                    No hay CV cargado · solo se aceptan archivos PDF
                </div>
            </div>

            <!-- ── Pestañas ── -->
            <div class="flex gap-1 rounded-lg p-1" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <button @click="seccion = 'datos'"
                    class="flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all"
                    :style="seccion === 'datos'
                        ? 'background-color: var(--primary-color); color: var(--primary-text);'
                        : 'color: var(--text-secondary);'">
                    Datos personales
                </button>
                <button @click="seccion = 'password'"
                    class="flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all"
                    :style="seccion === 'password'
                        ? 'background-color: var(--primary-color); color: var(--primary-text);'
                        : 'color: var(--text-secondary);'">
                    Cambiar contraseña
                </button>
            </div>

            <!-- ══ Sección: Datos personales ══ -->
            <div v-if="seccion === 'datos'"
                 class="rounded-xl p-5 space-y-4"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">

                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Información personal</p>
                    <button @click="toggleEditing" type="button"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                            :style="isEditing
                                ? 'background-color: color-mix(in srgb, var(--text-color) 10%, transparent); color: var(--text-secondary);'
                                : 'background-color: color-mix(in srgb, var(--primary-color) 12%, transparent); color: var(--primary-color);'">
                        <svg v-if="!isEditing" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        {{ isEditing ? 'Cancelar' : 'Editar' }}
                    </button>
                </div>

                <form @submit.prevent="guardarDatos" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Nombre</label>
                            <p v-if="!isEditing" class="text-sm py-2 font-medium" style="color: var(--text-color);">{{ formDatos.nombre || '—' }}</p>
                            <input v-else v-model="formDatos.nombre" type="text" required
                                   class="w-full rounded-lg px-3 py-2 text-sm"
                                   style="background-color: color-mix(in srgb, var(--text-color) 5%, transparent); border: 1px solid var(--border-color); color: var(--text-color);" />
                            <p v-if="isEditing && errors.nombre" class="text-xs mt-1" style="color: #ef4444;">{{ errors.nombre }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Apellido</label>
                            <p v-if="!isEditing" class="text-sm py-2 font-medium" style="color: var(--text-color);">{{ formDatos.apellido || '—' }}</p>
                            <input v-else v-model="formDatos.apellido" type="text" required
                                   class="w-full rounded-lg px-3 py-2 text-sm"
                                   style="background-color: color-mix(in srgb, var(--text-color) 5%, transparent); border: 1px solid var(--border-color); color: var(--text-color);" />
                            <p v-if="isEditing && errors.apellido" class="text-xs mt-1" style="color: #ef4444;">{{ errors.apellido }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Correo electrónico</label>
                        <p v-if="!isEditing" class="text-sm py-2 font-medium" style="color: var(--text-color);">{{ formDatos.email || '—' }}</p>
                        <input v-else v-model="formDatos.email" type="email" required
                               class="w-full rounded-lg px-3 py-2 text-sm"
                               style="background-color: color-mix(in srgb, var(--text-color) 5%, transparent); border: 1px solid var(--border-color); color: var(--text-color);" />
                        <p v-if="isEditing && errors.email" class="text-xs mt-1" style="color: #ef4444;">{{ errors.email }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Teléfono</label>
                            <p v-if="!isEditing" class="text-sm py-2 font-medium" style="color: var(--text-color);">{{ formDatos.telefono || '—' }}</p>
                            <input v-else v-model="formDatos.telefono" type="text"
                                   class="w-full rounded-lg px-3 py-2 text-sm"
                                   style="background-color: color-mix(in srgb, var(--text-color) 5%, transparent); border: 1px solid var(--border-color); color: var(--text-color);" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Dirección</label>
                            <p v-if="!isEditing" class="text-sm py-2 font-medium" style="color: var(--text-color);">{{ formDatos.direccion || '—' }}</p>
                            <input v-else v-model="formDatos.direccion" type="text"
                                   class="w-full rounded-lg px-3 py-2 text-sm"
                                   style="background-color: color-mix(in srgb, var(--text-color) 5%, transparent); border: 1px solid var(--border-color); color: var(--text-color);" />
                        </div>
                    </div>

                    <div v-if="isEditing" class="flex justify-end pt-1">
                        <button type="submit" :disabled="formDatos.processing"
                                class="px-6 py-2.5 rounded-lg font-semibold text-sm transition-all disabled:opacity-50"
                                style="background-color: var(--primary-color); color: var(--primary-text);">
                            {{ formDatos.processing ? 'Guardando...' : 'Guardar cambios' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ══ Sección: Cambiar contraseña ══ -->
            <form v-if="seccion === 'password'" @submit.prevent="cambiarPassword"
                  class="rounded-xl p-5 space-y-4"
                  style="background-color: var(--card-bg); border: 1px solid var(--border-color);">

                <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary);">Cambiar contraseña</p>

                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Contraseña actual</label>
                    <input v-model="formPwd.password_actual" type="password" required autocomplete="current-password"
                           class="w-full rounded-lg px-3 py-2 text-sm"
                           style="background-color: color-mix(in srgb, var(--text-color) 5%, transparent); border: 1px solid var(--border-color); color: var(--text-color);" />
                    <p v-if="errors.password_actual" class="text-xs mt-1" style="color: #ef4444;">{{ errors.password_actual }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Nueva contraseña</label>
                    <input v-model="formPwd.password_nuevo" type="password" required autocomplete="new-password"
                           class="w-full rounded-lg px-3 py-2 text-sm"
                           style="background-color: color-mix(in srgb, var(--text-color) 5%, transparent); border: 1px solid var(--border-color); color: var(--text-color);" />
                    <p v-if="errors.password_nuevo" class="text-xs mt-1" style="color: #ef4444;">{{ errors.password_nuevo }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Confirmar nueva contraseña</label>
                    <input v-model="formPwd.password_nuevo_confirmation" type="password" required autocomplete="new-password"
                           class="w-full rounded-lg px-3 py-2 text-sm"
                           style="background-color: color-mix(in srgb, var(--text-color) 5%, transparent); border: 1px solid var(--border-color); color: var(--text-color);" />
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit" :disabled="formPwd.processing"
                            class="px-6 py-2.5 rounded-lg font-semibold text-sm transition-all disabled:opacity-50"
                            style="background-color: var(--primary-color); color: var(--primary-text);">
                        {{ formPwd.processing ? 'Actualizando...' : 'Cambiar contraseña' }}
                    </button>
                </div>
            </form>

        </div>
    </DocenteLayout>
</template>
