<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { errNombre, errDni, errEmail, errTelefono } from '@/utils/validacion.js';

const page      = usePage();
const canEdit   = computed(() => ['propietario', 'director', 'secretaria'].includes(page.props.auth?.user?.role));
const canToggle = computed(() => ['propietario', 'director'].includes(page.props.auth?.user?.role));

const dashboardRoute = computed(() => {
    const role = page.props.auth?.user?.role;
    if (role === 'director') return 'dashboard.director';
    if (role === 'secretaria') return 'secretaria.dashboard';
    return 'dashboard.propietario';
});

const props = defineProps({
    usuarios: Object,
    roles: Array,
    rolesPermitidos: Array,
    filtros: Object,
});

// ── Filtros ───────────────────────────────────────────────────────────────────
const buscar   = ref(props.filtros?.buscar ?? '');
const rolFiltro = ref(props.filtros?.rol ?? '');

let buscarTimeout = null;
watch(buscar, () => {
    clearTimeout(buscarTimeout);
    buscarTimeout = setTimeout(aplicarFiltros, 400);
});
watch(rolFiltro, aplicarFiltros);

function aplicarFiltros() {
    router.get(route('propietario.usuarios.index'), {
        buscar: buscar.value || undefined,
        rol:    rolFiltro.value || undefined,
    }, { preserveState: true, replace: true });
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const ROLES_MAP = {
    1: { label: 'Propietario', badge: 'badge-purple' },
    2: { label: 'Director',    badge: 'badge-blue'   },
    3: { label: 'Secretaria',  badge: 'badge-green'  },
    4: { label: 'Profesor',    badge: 'badge-yellow' },
    5: { label: 'Estudiante',  badge: 'badge-gray'   },
};

const rolesCreables = computed(() =>
    props.roles.filter(r => props.rolesPermitidos.includes(r.id_rol))
);

function rolInfo(id) { return ROLES_MAP[id] ?? { label: 'Desconocido', badge: 'badge-gray' }; }

// ── Modal VER ─────────────────────────────────────────────────────────────────
const showVerModal      = ref(false);
const usuarioVer        = ref(null);
const showPassEnVer     = ref(false);

const passVerForm = useForm({ password: '', password_confirmation: '' });

function abrirVer(u) {
    usuarioVer.value    = u;
    showPassEnVer.value = false;
    passVerForm.reset();
    passVerForm.clearErrors();
    showVerModal.value  = true;
}

function guardarPassVer() {
    passVerForm.patch(route('propietario.usuarios.password', usuarioVer.value.id_usuario), {
        onSuccess: () => {
            showPassEnVer.value = false;
            passVerForm.reset();
        },
    });
}

// ── Modal CREAR / EDITAR ──────────────────────────────────────────────────────
const showFormModal = ref(false);
const modoEdicion   = ref(false);
const editandoId    = ref(null);

const form = useForm({
    nombre: '', apellido: '', email: '', dni: '',
    telefono: '', direccion: '', id_rol: '',
    password: '', password_confirmation: '',
    especialidad: '', titulo_maximo: '', fecha_contratacion: '',
    cargo: '', fecha_ingreso: '',
});

const fe = ref({});

function validarCampos() {
    const e = {};
    const n = errNombre(form.nombre);                        if (n) e.nombre   = n;
    const a = errNombre(form.apellido, 'El apellido');       if (a) e.apellido = a;
    const d = errDni(form.dni);                             if (d) e.dni      = d;
    const t = errTelefono(form.telefono);                   if (t) e.telefono = t;

    if (!modoEdicion.value) {
        const em = errEmail(form.email);                    if (em) e.email = em;
        if (!form.id_rol) e.id_rol = 'Seleccione un rol.';
        if (!form.password)
            e.password = 'La contraseña es obligatoria.';
        else if (form.password.length < 6)
            e.password = 'Mínimo 6 caracteres.';
        else if (form.password !== form.password_confirmation)
            e.password_confirmation = 'Las contraseñas no coinciden.';
    } else {
        const em = errEmail(form.email);                    if (em) e.email = em;
    }

    fe.value = e;
    return Object.keys(e).length === 0;
}

// Revalidar en tiempo real solo si ya se intentó enviar
watch(() => [form.nombre, form.apellido, form.dni, form.telefono, form.email, form.id_rol, form.password, form.password_confirmation], () => {
    if (Object.keys(fe.value).length) validarCampos();
});

function abrirCrear() {
    form.reset(); form.clearErrors();
    modoEdicion.value = false;
    editandoId.value  = null;
    showFormModal.value = true;
}

function abrirEditar(u) {
    form.reset(); form.clearErrors();
    form.nombre    = u.nombre;
    form.apellido  = u.apellido;
    form.email     = u.email;
    form.dni       = u.dni;
    form.telefono  = u.telefono ?? '';
    form.direccion = u.direccion ?? '';
    form.id_rol    = u.id_rol;
    modoEdicion.value   = true;
    editandoId.value    = u.id_usuario;
    showFormModal.value = true;
}

function cerrarForm() { showFormModal.value = false; form.reset(); form.clearErrors(); }

function guardar() {
    if (!validarCampos()) return;
    if (modoEdicion.value) {
        form.put(route('propietario.usuarios.update', editandoId.value), { onSuccess: cerrarForm });
    } else {
        form.post(route('propietario.usuarios.store'), { onSuccess: cerrarForm });
    }
}

const rolSel     = computed(() => Number(form.id_rol));
const esProfesor = computed(() => rolSel.value === 4);
const esAdmRol   = computed(() => [1, 2, 3].includes(rolSel.value));

// ── Toggle Activo ─────────────────────────────────────────────────────────────
function toggleActivo(u) {
    const accion = u.activo ? 'desactivar' : 'activar';
    if (!confirm(`¿Desea ${accion} a ${u.nombre} ${u.apellido}?`)) return;
    router.patch(route('propietario.usuarios.toggle-activo', u.id_usuario));
}
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--text-color);">
                Gestión de Usuarios
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <!-- Volver -->
                <div class="mb-5">
                    <Link :href="route(dashboardRoute)"
                        class="inline-flex items-center gap-1.5 text-sm font-medium transition-opacity hover:opacity-70"
                        style="color: var(--text-secondary);">
                        ← Volver al Dashboard
                    </Link>
                </div>

                <!-- Flash -->
                <div v-if="$page.props.flash?.success" class="mb-4 rounded-lg p-4 text-sm font-medium"
                     style="background-color: #d1fae5; color: #065f46; border: 1px solid #6ee7b7;">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error" class="mb-4 rounded-lg p-4 text-sm font-medium"
                     style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Barra superior -->
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex gap-2 flex-1 flex-wrap">
                        <input v-model="buscar" type="text" placeholder="Buscar por nombre, email o DNI..."
                            class="flex-1 min-w-[200px] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2"
                            style="background-color: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color); focus-ring-color: var(--primary-color);" />
                        <select v-model="rolFiltro"
                            class="rounded-lg pl-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2"
                            style="background-color: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color);">
                            <option value="">Todos los roles</option>
                            <option v-for="r in roles" :key="r.id_rol" :value="r.id_rol">{{ r.nombre_rol }}</option>
                        </select>
                    </div>
                    <button v-if="canEdit && rolesCreables.length > 0" @click="abrirCrear"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition"
                        style="background-color: var(--primary-color); color: var(--primary-text);">
                        + Nuevo Usuario
                    </button>
                </div>

                <!-- Tabla -->
                <div class="overflow-hidden rounded-xl shadow" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y" style="border-color: var(--border-color);">
                        <thead>
                            <tr style="background-color: var(--bg-color);">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Usuario</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider hidden md:table-cell" style="color: var(--text-secondary);">DNI</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Rol</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Estado</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="u in usuarios.data" :key="u.id_usuario"
                                class="border-t transition-colors hover:opacity-90"
                                style="border-color: var(--border-color);">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img v-if="u.foto_perfil" :src="($page.props.asset_url || '') + '/imagenes/' + u.foto_perfil" class="w-8 h-8 rounded-full object-cover">
                                        <div v-else class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0" style="background-color: var(--primary-color); color: var(--primary-text);">
                                            {{ u.nombre.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-sm" style="color: var(--text-color);">{{ u.nombre }} {{ u.apellido }}</div>
                                            <div class="text-xs" style="color: var(--text-secondary);">ID: {{ u.id_usuario }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm" style="color: var(--text-secondary);">{{ u.email }}</td>
                                <td class="px-4 py-3 text-sm hidden md:table-cell" style="color: var(--text-secondary);">{{ u.dni }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['badge', rolInfo(u.id_rol).badge]">{{ rolInfo(u.id_rol).label }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['badge', u.activo ? 'badge-activo' : 'badge-inactivo']">
                                        {{ u.activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-1">
                                        <button @click="abrirVer(u)" class="btn-accion" style="color: var(--primary-color);">Ver</button>
                                        <button v-if="canEdit" @click="abrirEditar(u)" class="btn-accion" style="color: #d97706;">Editar</button>
                                        <button v-if="canToggle" @click="toggleActivo(u)" class="btn-accion"
                                            :style="u.activo ? 'color: #dc2626;' : 'color: #059669;'">
                                            {{ u.activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="usuarios.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-sm" style="color: var(--text-secondary);">
                                    No se encontraron usuarios.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>

                    <!-- Paginación -->
                    <Pagination :data="usuarios" label="usuarios" />
                </div>
            </div>
        </div>

        <!-- ── Modal VER ───────────────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showVerModal" class="modal-overlay" @click.self="showVerModal = false">
                <div class="modal-box" style="max-width: 30rem;">
                    <div class="modal-header">
                        <h3 class="modal-title">Detalle de Usuario</h3>
                        <button @click="showVerModal = false" class="modal-close">&times;</button>
                    </div>

                    <div class="p-6 space-y-3" v-if="usuarioVer">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold"
                                 style="background-color: var(--primary-color); color: var(--primary-text);">
                                {{ usuarioVer.nombre[0] }}{{ usuarioVer.apellido[0] }}
                            </div>
                            <div>
                                <p class="font-semibold text-base" style="color: var(--text-color);">{{ usuarioVer.nombre }} {{ usuarioVer.apellido }}</p>
                                <span :class="['badge', rolInfo(usuarioVer.id_rol).badge]">{{ rolInfo(usuarioVer.id_rol).label }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="font-medium mb-0.5" style="color: var(--text-secondary);">Email</p>
                                <p style="color: var(--text-color);">{{ usuarioVer.email }}</p>
                            </div>
                            <div>
                                <p class="font-medium mb-0.5" style="color: var(--text-secondary);">DNI</p>
                                <p style="color: var(--text-color);">{{ usuarioVer.dni }}</p>
                            </div>
                            <div>
                                <p class="font-medium mb-0.5" style="color: var(--text-secondary);">Teléfono</p>
                                <p style="color: var(--text-color);">{{ usuarioVer.telefono ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="font-medium mb-0.5" style="color: var(--text-secondary);">Estado</p>
                                <span :class="['badge', usuarioVer.activo ? 'badge-activo' : 'badge-inactivo']">
                                    {{ usuarioVer.activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>
                            <div class="col-span-2">
                                <p class="font-medium mb-0.5" style="color: var(--text-secondary);">Dirección</p>
                                <p style="color: var(--text-color);">{{ usuarioVer.direccion ?? '—' }}</p>
                            </div>
                        </div>

                        <!-- Cambiar Contraseña (dentro del modal Ver) -->
                        <div class="border-t pt-3 mt-3" style="border-color: var(--border-color);">
                            <button @click="showPassEnVer = !showPassEnVer" class="text-sm font-medium transition"
                                style="color: var(--primary-color);">
                                {{ showPassEnVer ? '▲ Cancelar cambio de contraseña' : '🔑 Cambiar contraseña' }}
                            </button>

                            <form v-if="showPassEnVer" @submit.prevent="guardarPassVer" class="mt-3 space-y-3">
                                <div>
                                    <label class="block text-sm font-medium mb-1" style="color: var(--text-color);">Nueva contraseña *</label>
                                    <input v-model="passVerForm.password" type="password" class="field-input" />
                                    <p v-if="passVerForm.errors.password" class="mt-1 text-xs" style="color: #ef4444;">{{ passVerForm.errors.password }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1" style="color: var(--text-color);">Confirmar contraseña *</label>
                                    <input v-model="passVerForm.password_confirmation" type="password" class="field-input" />
                                </div>
                                <button type="submit" :disabled="passVerForm.processing"
                                    class="rounded-lg px-4 py-2 text-sm font-medium transition disabled:opacity-50"
                                    style="background-color: var(--primary-color); color: var(--primary-text);">
                                    {{ passVerForm.processing ? 'Guardando...' : 'Actualizar contraseña' }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button @click="showVerModal = false" class="btn-secondary">Cerrar</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── Modal CREAR / EDITAR ────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showFormModal" class="modal-overlay" @click.self="cerrarForm">
                <div class="modal-box" style="max-width: 32rem;">
                    <div class="modal-header">
                        <h3 class="modal-title">{{ modoEdicion ? 'Editar Usuario' : 'Nuevo Usuario' }}</h3>
                        <button @click="cerrarForm" class="modal-close">&times;</button>
                    </div>

                    <form @submit.prevent="guardar" class="p-6 space-y-4 overflow-y-auto" style="max-height: 70vh;">

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="field-label">Nombre *</label>
                                <input v-model="form.nombre" type="text" class="field-input" />
                                <p v-if="fe.nombre || form.errors.nombre" class="field-error">{{ fe.nombre || form.errors.nombre }}</p>
                            </div>
                            <div>
                                <label class="field-label">Apellido *</label>
                                <input v-model="form.apellido" type="text" class="field-input" />
                                <p v-if="fe.apellido || form.errors.apellido" class="field-error">{{ fe.apellido || form.errors.apellido }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="field-label">Email *</label>
                            <input v-model="form.email" type="text" class="field-input" />
                            <p v-if="fe.email || form.errors.email" class="field-error">{{ fe.email || form.errors.email }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="field-label">DNI *</label>
                                <input v-model="form.dni" type="text" class="field-input" />
                                <p v-if="fe.dni || form.errors.dni" class="field-error">{{ fe.dni || form.errors.dni }}</p>
                            </div>
                            <div>
                                <label class="field-label">Teléfono</label>
                                <input v-model="form.telefono" type="text" class="field-input" />
                                <p v-if="fe.telefono || form.errors.telefono" class="field-error">{{ fe.telefono || form.errors.telefono }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="field-label">Dirección</label>
                            <input v-model="form.direccion" type="text" class="field-input" />
                        </div>

                        <div v-if="!modoEdicion">
                            <label class="field-label">Rol *</label>
                            <select v-model="form.id_rol" class="field-input">
                                <option value="">Seleccione un rol</option>
                                <option v-for="r in rolesCreables" :key="r.id_rol" :value="r.id_rol">{{ r.nombre_rol }}</option>
                            </select>
                            <p v-if="fe.id_rol || form.errors.id_rol" class="field-error">{{ fe.id_rol || form.errors.id_rol }}</p>
                        </div>

                        <!-- Extra: Profesor -->
                        <template v-if="esProfesor && !modoEdicion">
                            <div>
                                <label class="field-label">Especialidad *</label>
                                <input v-model="form.especialidad" type="text" class="field-input" />
                                <p v-if="form.errors.especialidad" class="field-error">{{ form.errors.especialidad }}</p>
                            </div>
                            <div>
                                <label class="field-label">Título Máximo</label>
                                <input v-model="form.titulo_maximo" type="text" class="field-input" />
                            </div>
                            <div>
                                <label class="field-label">Fecha de Contratación *</label>
                                <input v-model="form.fecha_contratacion" type="date" class="field-input" />
                                <p v-if="form.errors.fecha_contratacion" class="field-error">{{ form.errors.fecha_contratacion }}</p>
                            </div>
                        </template>

                        <!-- Extra: Personal Administrativo -->
                        <template v-if="esAdmRol && !modoEdicion">
                            <div>
                                <label class="field-label">Cargo</label>
                                <input v-model="form.cargo" type="text" class="field-input" placeholder="Se asigna automáticamente si se deja vacío" />
                            </div>
                            <div>
                                <label class="field-label">Fecha de Ingreso</label>
                                <input v-model="form.fecha_ingreso" type="date" class="field-input" />
                            </div>
                        </template>

                        <!-- Password solo al crear -->
                        <template v-if="!modoEdicion">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="field-label">Contraseña *</label>
                                    <input v-model="form.password" type="password" class="field-input" />
                                    <p v-if="fe.password || form.errors.password" class="field-error">{{ fe.password || form.errors.password }}</p>
                                </div>
                                <div>
                                    <label class="field-label">Confirmar *</label>
                                    <input v-model="form.password_confirmation" type="password" class="field-input" />
                                    <p v-if="fe.password_confirmation" class="field-error">{{ fe.password_confirmation }}</p>
                                </div>
                            </div>
                        </template>

                        <div class="modal-footer border-t pt-4" style="border-color: var(--border-color);">
                            <button type="button" @click="cerrarForm" class="btn-secondary">Cancelar</button>
                            <button type="submit" :disabled="form.processing"
                                class="rounded-lg px-4 py-2 text-sm font-medium transition disabled:opacity-50"
                                style="background-color: var(--primary-color); color: var(--primary-text);">
                                {{ form.processing ? 'Guardando...' : (modoEdicion ? 'Actualizar' : 'Crear Usuario') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
/* Overlay y caja modal */
.modal-overlay {
    position: fixed; inset: 0; z-index: 50;
    display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,0.55); padding: 1rem;
}
.modal-box {
    width: 100%; border-radius: 1rem; overflow: hidden;
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4);
}
.modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
}
.modal-title  { font-size: 1rem; font-weight: 600; color: var(--text-color); }
.modal-close  { font-size: 1.5rem; line-height: 1; color: var(--text-secondary); background: none; border: none; cursor: pointer; }
.modal-close:hover { color: var(--text-color); }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 0.75rem 1.5rem; }

/* Campos */
.field-label {
    display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;
    color: var(--text-color);
}
.field-input {
    width: 100%; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem;
    background-color: var(--bg-color);
    color: var(--text-color);
    border: 1px solid var(--border-color);
    outline: none;
}
.field-input:focus { border-color: var(--primary-color); box-shadow: 0 0 0 2px color-mix(in srgb, var(--primary-color) 30%, transparent); }
.field-error { margin-top: 0.25rem; font-size: 0.75rem; color: #ef4444; }

/* Botón secundario */
.btn-secondary {
    border-radius: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem;
    background: none; cursor: pointer; transition: opacity 0.15s;
    color: var(--text-color); border: 1px solid var(--border-color);
}
.btn-secondary:hover { opacity: 0.8; }

/* Botón de acción en tabla */
.btn-accion {
    padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500;
    background: none; border: none; cursor: pointer; transition: opacity 0.15s;
}
.btn-accion:hover { opacity: 0.7; }

/* Badges */
.badge { display: inline-flex; border-radius: 9999px; padding: 0.125rem 0.625rem; font-size: 0.75rem; font-weight: 600; }
.badge-purple { background: rgba(139,92,246,0.2); color: #a78bfa; }
.badge-blue   { background: rgba(59,130,246,0.2); color: #60a5fa; }
.badge-green  { background: rgba(16,185,129,0.2); color: #34d399; }
.badge-yellow { background: rgba(245,158,11,0.2); color: #fbbf24; }
.badge-gray   { background: rgba(107,114,128,0.2); color: #9ca3af; }
.badge-activo   { background: rgba(16,185,129,0.2); color: #34d399; }
.badge-inactivo { background: rgba(239,68,68,0.2);  color: #f87171; }
</style>
