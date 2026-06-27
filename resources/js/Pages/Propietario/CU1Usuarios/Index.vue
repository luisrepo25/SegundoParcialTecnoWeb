<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    usuarios: Object,
    roles: Array,
    rolesPermitidos: Array,
    filtros: Object,
});

// ── Filtros ──────────────────────────────────────────────────────────────────
const buscar = ref(props.filtros?.buscar ?? '');
const rolFiltro = ref(props.filtros?.rol ?? '');

let buscarTimeout = null;
watch(buscar, (val) => {
    clearTimeout(buscarTimeout);
    buscarTimeout = setTimeout(() => aplicarFiltros(), 400);
});
watch(rolFiltro, () => aplicarFiltros());

function aplicarFiltros() {
    router.get(route('admin.usuarios.index'), {
        buscar: buscar.value || undefined,
        rol: rolFiltro.value || undefined,
    }, { preserveState: true, replace: true });
}

// ── Helpers ──────────────────────────────────────────────────────────────────
const ROLES_LABEL = {
    1: { label: 'Propietario', color: 'bg-purple-100 text-purple-800' },
    2: { label: 'Director',    color: 'bg-blue-100 text-blue-800' },
    3: { label: 'Secretaria',  color: 'bg-green-100 text-green-800' },
    4: { label: 'Profesor',    color: 'bg-yellow-100 text-yellow-800' },
    5: { label: 'Estudiante',  color: 'bg-gray-100 text-gray-800' },
};

const rolesCreables = computed(() =>
    props.roles.filter(r => props.rolesPermitidos.includes(r.id_rol))
);

function rolInfo(idRol) {
    return ROLES_LABEL[idRol] ?? { label: 'Desconocido', color: 'bg-gray-100 text-gray-500' };
}

// ── Modal Crear / Editar ──────────────────────────────────────────────────────
const showModal = ref(false);
const modoEdicion = ref(false);
const editandoId = ref(null);

const form = useForm({
    nombre: '', apellido: '', email: '', dni: '',
    telefono: '', direccion: '', id_rol: '',
    password: '', password_confirmation: '',
    // Profesor
    especialidad: '', titulo_maximo: '', fecha_contratacion: '',
    // Personal admin
    cargo: '', fecha_ingreso: '',
});

function abrirCrear() {
    form.reset();
    modoEdicion.value = false;
    editandoId.value = null;
    showModal.value = true;
}

function abrirEditar(usuario) {
    form.reset();
    form.nombre    = usuario.nombre;
    form.apellido  = usuario.apellido;
    form.email     = usuario.email;
    form.dni       = usuario.dni;
    form.telefono  = usuario.telefono ?? '';
    form.direccion = usuario.direccion ?? '';
    form.id_rol    = usuario.id_rol;
    modoEdicion.value = true;
    editandoId.value  = usuario.id_usuario;
    showModal.value   = true;
}

function cerrarModal() {
    showModal.value = false;
    form.reset();
    form.clearErrors();
}

function guardar() {
    if (modoEdicion.value) {
        form.put(route('admin.usuarios.update', editandoId.value), {
            onSuccess: () => cerrarModal(),
        });
    } else {
        form.post(route('admin.usuarios.store'), {
            onSuccess: () => cerrarModal(),
        });
    }
}

const rolSeleccionado = computed(() => Number(form.id_rol));
const esProfesor     = computed(() => rolSeleccionado.value === 4);
const esPersonalAdm  = computed(() => [1, 2, 3].includes(rolSeleccionado.value));

// ── Modal Cambiar Password ────────────────────────────────────────────────────
const showPasswordModal = ref(false);
const passwordUsuarioId = ref(null);
const passwordUsuarioNombre = ref('');

const passForm = useForm({
    password: '',
    password_confirmation: '',
});

function abrirCambiarPassword(usuario) {
    passForm.reset();
    passForm.clearErrors();
    passwordUsuarioId.value     = usuario.id_usuario;
    passwordUsuarioNombre.value = `${usuario.nombre} ${usuario.apellido}`;
    showPasswordModal.value     = true;
}

function guardarPassword() {
    passForm.patch(route('admin.usuarios.password', passwordUsuarioId.value), {
        onSuccess: () => {
            showPasswordModal.value = false;
            passForm.reset();
        },
    });
}

// ── Toggle Activo ─────────────────────────────────────────────────────────────
function toggleActivo(usuario) {
    const accion = usuario.activo ? 'desactivar' : 'activar';
    if (!confirm(`¿Desea ${accion} a ${usuario.nombre} ${usuario.apellido}?`)) return;
    router.patch(route('admin.usuarios.toggle-activo', usuario.id_usuario));
}
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Gestión de Usuarios
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <!-- Flash messages -->
                <div v-if="$page.props.flash?.success"
                     class="mb-4 rounded-lg bg-green-50 p-4 text-green-800 border border-green-200">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error"
                     class="mb-4 rounded-lg bg-red-50 p-4 text-red-800 border border-red-200">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Barra superior -->
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex gap-2 flex-1 flex-wrap">
                        <input
                            v-model="buscar"
                            type="text"
                            placeholder="Buscar por nombre, email o DNI..."
                            class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                        <select
                            v-model="rolFiltro"
                            class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Todos los roles</option>
                            <option v-for="r in roles" :key="r.id_rol" :value="r.id_rol">
                                {{ r.nombre_rol }}
                            </option>
                        </select>
                    </div>
                    <button
                        v-if="rolesCreables.length > 0"
                        @click="abrirCrear"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition"
                    >
                        + Nuevo Usuario
                    </button>
                </div>

                <!-- Tabla -->
                <div class="overflow-hidden rounded-xl bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">DNI</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="u in usuarios.data" :key="u.id_usuario" class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ u.nombre }} {{ u.apellido }}</div>
                                    <div class="text-xs text-gray-400">ID: {{ u.id_usuario }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ u.email }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ u.dni }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex rounded-full px-2 py-0.5 text-xs font-semibold', rolInfo(u.id_rol).color]">
                                        {{ rolInfo(u.id_rol).label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="u.activo
                                        ? 'inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-700'
                                        : 'inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700'">
                                        {{ u.activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-1">
                                        <button @click="abrirEditar(u)"
                                            class="rounded px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50 transition">
                                            Editar
                                        </button>
                                        <button @click="abrirCambiarPassword(u)"
                                            class="rounded px-2 py-1 text-xs font-medium text-amber-600 hover:bg-amber-50 transition">
                                            Contraseña
                                        </button>
                                        <button @click="toggleActivo(u)"
                                            :class="u.activo
                                                ? 'rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 transition'
                                                : 'rounded px-2 py-1 text-xs font-medium text-emerald-600 hover:bg-emerald-50 transition'">
                                            {{ u.activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="usuarios.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">
                                    No se encontraron usuarios.
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Paginación -->
                    <div v-if="usuarios.last_page > 1" class="flex items-center justify-between border-t border-gray-200 px-4 py-3">
                        <p class="text-sm text-gray-500">
                            Mostrando {{ usuarios.from }}–{{ usuarios.to }} de {{ usuarios.total }} usuarios
                        </p>
                        <div class="flex gap-1">
                            <template v-for="link in usuarios.links" :key="link.label">
                                <button
                                    v-if="link.url"
                                    @click="router.get(link.url)"
                                    :class="['px-3 py-1 rounded text-sm border', link.active
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : 'text-gray-600 border-gray-300 hover:bg-gray-50']"
                                    v-html="link.label"
                                />
                                <span v-else class="px-3 py-1 rounded text-sm border border-gray-200 text-gray-300"
                                    v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Modal Crear / Editar ──────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl overflow-y-auto max-h-[90vh]">
                    <div class="flex items-center justify-between border-b px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ modoEdicion ? 'Editar Usuario' : 'Nuevo Usuario' }}
                        </h3>
                        <button @click="cerrarModal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                    </div>

                    <form @submit.prevent="guardar" class="px-6 py-4 space-y-4">

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label-form">Nombre *</label>
                                <input v-model="form.nombre" type="text" class="input-form" />
                                <p v-if="form.errors.nombre" class="text-error">{{ form.errors.nombre }}</p>
                            </div>
                            <div>
                                <label class="label-form">Apellido *</label>
                                <input v-model="form.apellido" type="text" class="input-form" />
                                <p v-if="form.errors.apellido" class="text-error">{{ form.errors.apellido }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="label-form">Email *</label>
                            <input v-model="form.email" type="email" class="input-form" />
                            <p v-if="form.errors.email" class="text-error">{{ form.errors.email }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label-form">DNI *</label>
                                <input v-model="form.dni" type="text" class="input-form" />
                                <p v-if="form.errors.dni" class="text-error">{{ form.errors.dni }}</p>
                            </div>
                            <div>
                                <label class="label-form">Teléfono</label>
                                <input v-model="form.telefono" type="text" class="input-form" />
                            </div>
                        </div>

                        <div>
                            <label class="label-form">Dirección</label>
                            <input v-model="form.direccion" type="text" class="input-form" />
                        </div>

                        <!-- Rol (solo al crear) -->
                        <div v-if="!modoEdicion">
                            <label class="label-form">Rol *</label>
                            <select v-model="form.id_rol" class="input-form">
                                <option value="">Seleccione un rol</option>
                                <option v-for="r in rolesCreables" :key="r.id_rol" :value="r.id_rol">
                                    {{ r.nombre_rol }}
                                </option>
                            </select>
                            <p v-if="form.errors.id_rol" class="text-error">{{ form.errors.id_rol }}</p>
                        </div>

                        <!-- Campos extra: Profesor -->
                        <template v-if="esProfesor && !modoEdicion">
                            <div>
                                <label class="label-form">Especialidad *</label>
                                <input v-model="form.especialidad" type="text" class="input-form" />
                                <p v-if="form.errors.especialidad" class="text-error">{{ form.errors.especialidad }}</p>
                            </div>
                            <div>
                                <label class="label-form">Título Máximo</label>
                                <input v-model="form.titulo_maximo" type="text" class="input-form" />
                            </div>
                            <div>
                                <label class="label-form">Fecha de Contratación *</label>
                                <input v-model="form.fecha_contratacion" type="date" class="input-form" />
                                <p v-if="form.errors.fecha_contratacion" class="text-error">{{ form.errors.fecha_contratacion }}</p>
                            </div>
                        </template>

                        <!-- Campos extra: Personal Administrativo -->
                        <template v-if="esPersonalAdm && !modoEdicion">
                            <div>
                                <label class="label-form">Cargo</label>
                                <input v-model="form.cargo" type="text" class="input-form" placeholder="Se asigna automáticamente si se deja vacío" />
                            </div>
                            <div>
                                <label class="label-form">Fecha de Ingreso</label>
                                <input v-model="form.fecha_ingreso" type="date" class="input-form" />
                            </div>
                        </template>

                        <!-- Password (solo al crear) -->
                        <template v-if="!modoEdicion">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="label-form">Contraseña *</label>
                                    <input v-model="form.password" type="password" class="input-form" />
                                    <p v-if="form.errors.password" class="text-error">{{ form.errors.password }}</p>
                                </div>
                                <div>
                                    <label class="label-form">Confirmar Contraseña *</label>
                                    <input v-model="form.password_confirmation" type="password" class="input-form" />
                                </div>
                            </div>
                        </template>

                        <div class="flex justify-end gap-2 pt-2 border-t">
                            <button type="button" @click="cerrarModal"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                                {{ form.processing ? 'Guardando...' : (modoEdicion ? 'Actualizar' : 'Crear Usuario') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── Modal Cambiar Contraseña ───────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-sm rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900">Cambiar Contraseña</h3>
                        <button @click="showPasswordModal = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                    </div>
                    <form @submit.prevent="guardarPassword" class="px-6 py-4 space-y-4">
                        <p class="text-sm text-gray-500">Usuario: <strong>{{ passwordUsuarioNombre }}</strong></p>
                        <div>
                            <label class="label-form">Nueva Contraseña *</label>
                            <input v-model="passForm.password" type="password" class="input-form" />
                            <p v-if="passForm.errors.password" class="text-error">{{ passForm.errors.password }}</p>
                        </div>
                        <div>
                            <label class="label-form">Confirmar Contraseña *</label>
                            <input v-model="passForm.password_confirmation" type="password" class="input-form" />
                        </div>
                        <div class="flex justify-end gap-2 pt-2 border-t">
                            <button type="button" @click="showPasswordModal = false"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="passForm.processing"
                                class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50">
                                {{ passForm.processing ? 'Guardando...' : 'Actualizar Contraseña' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.label-form { @apply block text-sm font-medium text-gray-700 mb-1; }
.input-form  { @apply w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500; }
.text-error  { @apply mt-1 text-xs text-red-600; }
</style>
