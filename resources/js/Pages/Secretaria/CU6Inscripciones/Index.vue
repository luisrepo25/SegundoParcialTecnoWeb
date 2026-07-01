<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { errNombre, errDni, errEmail, errTelefono } from '@/utils/validacion.js';

const props = defineProps({
    carreras: Array,
});

const page = usePage();
const credenciales = computed(() => page.props.flash?.credenciales);
const canEdit = computed(() => ['propietario', 'director', 'secretaria'].includes(page.props.auth?.user?.role));

const form = useForm({
    nombre: '',
    apellido: '',
    dni: '',
    email: '',
    telefono: '',
    id_carrera: '',
});

const fe = ref({});

function validarCampos() {
    const e = {};
    const n = errNombre(form.nombre);                    if (n) e.nombre   = n;
    const a = errNombre(form.apellido, 'El apellido');   if (a) e.apellido = a;
    const d = errDni(form.dni);                         if (d) e.dni      = d;
    const em = errEmail(form.email);                    if (em) e.email   = em;
    const t = errTelefono(form.telefono);               if (t) e.telefono = t;
    if (!form.id_carrera) e.id_carrera = 'Seleccione una carrera.';
    fe.value = e;
    return Object.keys(e).length === 0;
}

watch(() => [form.nombre, form.apellido, form.dni, form.email, form.telefono, form.id_carrera], () => {
    if (Object.keys(fe.value).length) validarCampos();
});

function submitForm() {
    if (!validarCampos()) return;
    form.post(route('secretaria.inscripciones.manual'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            fe.value = {};
        }
    });
}
</script>

<template>
    <Head title="Inscripción Manual" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight tracking-tight" style="color: var(--text-color);">
                Inscripciones
            </h2>
        </template>

        <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

            <div v-if="!canEdit" class="text-center py-16" style="color:var(--text-secondary);">
                <p class="text-sm">Solo la secretaría puede registrar nuevas inscripciones.</p>
            </div>

            <template v-else>
            <div class="mb-8">
                <h3 class="text-3xl font-light text-[var(--text-color)] tracking-tight">Nueva Inscripción Presencial</h3>
                <p class="text-[var(--text-muted)] mt-1 font-light">Registra a un estudiante y cóbrale en efectivo instantáneamente.</p>
            </div>

            <!-- Alerta de Éxito (Muestra credenciales generadas) -->
            <div v-if="credenciales" class="mb-8 p-6 rounded-sm border border-emerald-500/30 bg-emerald-500/10 flex flex-col items-start gap-4">
                <div>
                    <h4 class="text-lg font-medium text-emerald-600 dark:text-emerald-400">¡Inscripción Exitosa y Pago Registrado!</h4>
                    <p class="text-sm font-light text-[var(--text-color)] mt-1">El estudiante <strong style="color: var(--text-color);">{{ credenciales.nombre }}</strong> ha sido registrado. Por favor, indícale las siguientes credenciales para acceder a su portal web. <br><br> <span class="font-medium text-emerald-700 dark:text-emerald-300">Nota importante:</span> Por seguridad, su contraseña inicial es su Cédula de Identidad (DNI) y el sistema le pedirá que la cambie la primera vez que ingrese.</p>
                </div>
                
                <div class="bg-[var(--card-bg)] border border-[var(--border-color)] p-4 rounded-sm w-full font-mono text-sm grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="block text-[10px] uppercase tracking-widest text-[var(--text-muted)] mb-1">Legajo</span>
                        <span class="text-[var(--text-color)] font-medium">{{ credenciales.legajo }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] uppercase tracking-widest text-[var(--text-muted)] mb-1">Email / Usuario</span>
                        <span class="text-[var(--text-color)] font-medium">{{ credenciales.email }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-[var(--card-bg)] rounded-sm border border-[var(--border-color)] p-6 shadow-sm">
                <form @submit.prevent="submitForm" class="space-y-6">
                    
                    <div class="border-b border-[var(--border-color)] pb-4 mb-4">
                        <h4 class="text-[11px] font-semibold uppercase tracking-widest opacity-70" style="color: var(--text-secondary);">Datos Personales</h4>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-light text-[var(--text-muted)] mb-1">Nombre(s)</label>
                            <input v-model="form.nombre" type="text" class="w-full bg-transparent border border-[var(--border-color)] rounded-sm px-4 py-2 text-[var(--text-color)] focus:outline-none focus:border-[var(--primary-color)] font-light">
                            <div v-if="fe.nombre || form.errors.nombre" class="text-rose-500 text-xs mt-1">{{ fe.nombre || form.errors.nombre }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-light text-[var(--text-muted)] mb-1">Apellido(s)</label>
                            <input v-model="form.apellido" type="text" class="w-full bg-transparent border border-[var(--border-color)] rounded-sm px-4 py-2 text-[var(--text-color)] focus:outline-none focus:border-[var(--primary-color)] font-light">
                            <div v-if="fe.apellido || form.errors.apellido" class="text-rose-500 text-xs mt-1">{{ fe.apellido || form.errors.apellido }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-light text-[var(--text-muted)] mb-1">Cédula de Identidad (DNI)</label>
                            <input v-model="form.dni" type="text" class="w-full bg-transparent border border-[var(--border-color)] rounded-sm px-4 py-2 text-[var(--text-color)] focus:outline-none focus:border-[var(--primary-color)] font-light">
                            <p class="text-[11px] text-[var(--text-muted)] mt-1 font-light opacity-60">Se usará como contraseña inicial.</p>
                            <div v-if="fe.dni || form.errors.dni" class="text-rose-500 text-xs mt-1">{{ fe.dni || form.errors.dni }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-light text-[var(--text-muted)] mb-1">Teléfono (Opcional)</label>
                            <input v-model="form.telefono" type="text" class="w-full bg-transparent border border-[var(--border-color)] rounded-sm px-4 py-2 text-[var(--text-color)] focus:outline-none focus:border-[var(--primary-color)] font-light">
                            <div v-if="fe.telefono || form.errors.telefono" class="text-rose-500 text-xs mt-1">{{ fe.telefono || form.errors.telefono }}</div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-light text-[var(--text-muted)] mb-1">Correo Electrónico</label>
                            <input v-model="form.email" type="text" class="w-full bg-transparent border border-[var(--border-color)] rounded-sm px-4 py-2 text-[var(--text-color)] focus:outline-none focus:border-[var(--primary-color)] font-light">
                            <div v-if="fe.email || form.errors.email" class="text-rose-500 text-xs mt-1">{{ fe.email || form.errors.email }}</div>
                        </div>
                    </div>

                    <div class="border-b border-[var(--border-color)] pb-4 mb-4 mt-8">
                        <h4 class="text-[11px] font-semibold uppercase tracking-widest opacity-70" style="color: var(--text-secondary);">Datos Académicos y Pago</h4>
                    </div>

                    <div>
                        <label class="block text-sm font-light text-[var(--text-muted)] mb-1">Carrera a Postular</label>
                        <select v-model="form.id_carrera" class="w-full bg-[var(--bg-color)] border border-[var(--border-color)] rounded-sm px-4 py-2 text-[var(--text-color)] focus:outline-none focus:border-[var(--primary-color)] font-light appearance-none" required>
                            <option value="" disabled selected class="bg-[var(--bg-color)] text-[var(--text-color)]">Seleccione una carrera...</option>
                            <option v-for="c in carreras" :key="c.id_carrera" :value="c.id_carrera" class="bg-[var(--bg-color)] text-[var(--text-color)]">
                                {{ c.nombre }} ({{ c.duracion_niveles }} semestres)
                            </option>
                        </select>
                        <div v-if="fe.id_carrera || form.errors.id_carrera" class="text-rose-500 text-xs mt-1">{{ fe.id_carrera || form.errors.id_carrera }}</div>
                    </div>

                    <div class="bg-[var(--bg-color)] border border-[var(--border-color)] rounded-sm p-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-[var(--text-color)]">Cobro de Matrícula Única</p>
                            <p class="text-[11px] text-[var(--text-muted)] mt-0.5 font-light">Se registrará automáticamente como "Pago en Efectivo - Caja"</p>
                        </div>
                        <div class="text-xl font-light text-[var(--primary-color)]">
                            Bs. 500
                        </div>
                    </div>

                    <div v-if="form.errors.general" class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-sm text-rose-500 text-sm">
                        {{ form.errors.general }}
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button type="submit" :disabled="form.processing" class="bg-[var(--primary-color)] text-[var(--primary-text)] px-8 py-3 rounded-sm text-sm font-medium hover:opacity-90 transition-opacity disabled:opacity-50 flex items-center gap-2">
                            <span v-if="form.processing">Registrando...</span>
                            <span v-else>Confirmar Inscripción y Pago</span>
                        </button>
                    </div>

                </form>
            </div>
            </template>
        </div>

    </AdminLayout>
</template>
