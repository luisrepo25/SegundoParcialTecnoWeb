<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    logs:               Object,
    acciones:           Array,
    filtros:            Object,
    recursosAccedidos:  Array,
});

// ── Filtros reactivos ──────────────────────────────────────────────────────────
const buscar      = ref(props.filtros?.buscar      ?? '');
const accion      = ref(props.filtros?.accion      ?? '');
const fechaDesde  = ref(props.filtros?.fecha_desde ?? '');
const fechaHasta  = ref(props.filtros?.fecha_hasta ?? '');

let debounceTimer = null;
function debounced(ms = 700) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(aplicarFiltros, ms);
}

watch(buscar,    () => debounced(600));
watch(accion,    () => debounced(300));
watch(fechaDesde, () => debounced(800));
watch(fechaHasta, () => debounced(800));

function aplicarFiltros() {
    router.get(route('propietario.bitacora.index'), {
        buscar:      buscar.value      || undefined,
        accion:      accion.value      || undefined,
        fecha_desde: fechaDesde.value  || undefined,
        fecha_hasta: fechaHasta.value  || undefined,
    }, { preserveState: true, replace: true });
}

function limpiarFiltros() {
    buscar.value     = '';
    accion.value     = '';
    fechaDesde.value = '';
    fechaHasta.value = '';
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const ROLES_MAP = {
    1: { label: 'Propietario', badge: 'badge-purple' },
    2: { label: 'Director',    badge: 'badge-blue'   },
    3: { label: 'Secretaria',  badge: 'badge-green'  },
    4: { label: 'Profesor',    badge: 'badge-yellow' },
    5: { label: 'Estudiante',  badge: 'badge-gray'   },
};
function rolInfo(id) { return ROLES_MAP[id] ?? { label: 'Desconocido', badge: 'badge-gray' }; }

function formatFecha(ts) {
    if (!ts) return '—';
    // Los timestamps vienen de PostgreSQL como UTC (sin indicador de zona).
    // Agregamos 'Z' para que JS los interprete como UTC y luego convierte a Bolivia (UTC-4).
    const iso = ts.includes('T') || ts.endsWith('Z') ? ts : ts.replace(' ', 'T') + 'Z';
    return new Date(iso).toLocaleString('es-BO', {
        timeZone: 'America/La_Paz',
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
    });
}

// Color de badge por tipo de acción
function accionBadgeStyle(accion) {
    const a = (accion || '').toLowerCase();
    if (a.includes('crear') || a.includes('store') || a.includes('registr'))
        return 'background: rgba(16,185,129,0.18); color: #34d399;';
    if (a.includes('editar') || a.includes('update') || a.includes('actualiz'))
        return 'background: rgba(245,158,11,0.18); color: #f59e0b;';
    if (a.includes('elimin') || a.includes('destroy') || a.includes('borra'))
        return 'background: rgba(239,68,68,0.18); color: #f87171;';
    if (a === 'login_fallido')
        return 'background: rgba(239,68,68,0.18); color: #f87171;';
    if (a.includes('login') || a.includes('logout') || a.includes('acceso') || a.includes('sesion'))
        return 'background: rgba(99,102,241,0.18); color: #818cf8;';
    if (a.includes('desactiv') || a.includes('bloqueo') || a.includes('toggle'))
        return 'background: rgba(236,72,153,0.18); color: #f472b6;';
    return 'background: rgba(107,114,128,0.18); color: #9ca3af;';
}

const hayFiltro = () => buscar.value || accion.value || fechaDesde.value || fechaHasta.value;
</script>

<template>
    <Head title="Bitácora del Sistema" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--text-color);">
                Bitácora del Sistema
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <!-- Volver -->
                <div class="mb-5">
                    <Link :href="route('dashboard.propietario')"
                        class="inline-flex items-center gap-1.5 text-sm font-medium transition-opacity hover:opacity-70"
                        style="color: var(--text-secondary);">
                        ← Volver al Dashboard
                    </Link>
                </div>

                <!-- Descripción -->
                <div class="mb-5 rounded-xl px-5 py-4"
                     style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <p class="text-sm" style="color: var(--text-secondary);">
                        Registro cronológico de todas las acciones realizadas por los usuarios del sistema.
                        Solo el <strong style="color: var(--text-color);">Propietario</strong> puede consultar esta bitácora.
                    </p>
                </div>

                <!-- Recursos más accedidos -->
                <div v-if="recursosAccedidos && recursosAccedidos.length"
                     class="mb-5 rounded-xl px-5 py-4"
                     style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <h3 class="text-sm font-semibold mb-3" style="color: var(--text-color);">
                        Páginas más visitadas
                    </h3>
                    <div class="flex flex-col gap-1.5">
                        <div v-for="(rec, i) in recursosAccedidos" :key="rec.pagina"
                             class="flex items-center gap-3">
                            <!-- Posición -->
                            <span class="text-xs font-mono w-5 text-right shrink-0" style="color: var(--text-secondary);">
                                {{ i + 1 }}.
                            </span>
                            <!-- Barra de progreso relativa -->
                            <div class="flex-1 rounded-full overflow-hidden h-1.5"
                                 style="background-color: color-mix(in srgb, var(--primary-color) 12%, transparent);">
                                <div class="h-full rounded-full"
                                     style="background-color: var(--primary-color);"
                                     :style="{ width: Math.round((rec.visitas / recursosAccedidos[0].visitas) * 100) + '%' }">
                                </div>
                            </div>
                            <!-- Nombre de ruta -->
                            <span class="text-xs font-mono truncate max-w-[220px]" style="color: var(--text-color);">
                                {{ rec.pagina }}
                            </span>
                            <!-- Contador -->
                            <span class="text-xs font-semibold shrink-0" style="color: var(--primary-color);">
                                {{ rec.visitas.toLocaleString('es-BO') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Barra de filtros -->
                <div class="mb-4 rounded-xl px-5 py-4 flex flex-col gap-3"
                     style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <div class="flex flex-wrap gap-3 items-end">

                        <!-- Búsqueda libre -->
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">
                                Buscar
                            </label>
                            <input v-model="buscar" type="text"
                                placeholder="Usuario, acción, descripción, IP..."
                                class="w-full rounded-lg px-3 py-2 text-sm focus:outline-none"
                                style="background-color: var(--bg-color); color: var(--text-color); border: 1px solid var(--border-color);" />
                        </div>

                        <!-- Selector de acción -->
                        <div class="min-w-[180px]">
                            <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">
                                Acción
                            </label>
                            <select v-model="accion"
                                class="w-full rounded-lg px-3 py-2 text-sm focus:outline-none"
                                style="background-color: var(--bg-color); color: var(--text-color); border: 1px solid var(--border-color);">
                                <option value="">Todas las acciones</option>
                                <option v-for="a in acciones" :key="a" :value="a">{{ a }}</option>
                            </select>
                        </div>

                        <!-- Desde -->
                        <div class="min-w-[150px]">
                            <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">
                                Desde
                            </label>
                            <input v-model="fechaDesde" type="date"
                                class="w-full rounded-lg px-3 py-2 text-sm focus:outline-none"
                                style="background-color: var(--bg-color); color: var(--text-color); border: 1px solid var(--border-color);" />
                        </div>

                        <!-- Hasta -->
                        <div class="min-w-[150px]">
                            <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">
                                Hasta
                            </label>
                            <input v-model="fechaHasta" type="date"
                                class="w-full rounded-lg px-3 py-2 text-sm focus:outline-none"
                                style="background-color: var(--bg-color); color: var(--text-color); border: 1px solid var(--border-color);" />
                        </div>

                        <!-- Limpiar filtros -->
                        <button v-if="hayFiltro()" @click="limpiarFiltros"
                            class="rounded-lg px-4 py-2 text-sm font-medium transition-opacity hover:opacity-70"
                            style="color: var(--text-secondary); border: 1px solid var(--border-color);">
                            ✕ Limpiar
                        </button>
                    </div>

                    <!-- Chip de resultados -->
                    <p v-if="logs.total !== undefined" class="text-xs" style="color: var(--text-secondary);">
                        {{ logs.total.toLocaleString() }} registro{{ logs.total !== 1 ? 's' : '' }} encontrado{{ logs.total !== 1 ? 's' : '' }}
                    </p>
                </div>

                <!-- Tabla -->
                <div class="overflow-hidden rounded-xl shadow"
                     style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y" style="border-color: var(--border-color);">
                            <thead>
                                <tr style="background-color: var(--bg-color);">
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider whitespace-nowrap"
                                        style="color: var(--text-secondary);">Fecha / Hora</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider"
                                        style="color: var(--text-secondary);">Usuario</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider"
                                        style="color: var(--text-secondary);">Acción</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider"
                                        style="color: var(--text-secondary);">Descripción</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider hidden md:table-cell"
                                        style="color: var(--text-secondary);">IP Origen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="log in logs.data" :key="log.id_log"
                                    class="border-t transition-colors hover:opacity-90"
                                    style="border-color: var(--border-color);">

                                    <!-- Fecha/Hora -->
                                    <td class="px-4 py-3 whitespace-nowrap font-mono text-xs"
                                        style="color: var(--text-secondary);">
                                        {{ formatFecha(log.fecha_hora) }}
                                    </td>

                                    <!-- Usuario -->
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <!-- Avatar: foto real o iniciales -->
                                            <img v-if="log.foto_perfil"
                                                :src="($page.props.asset_url || '') + '/imagenes/' + log.foto_perfil"
                                                class="w-8 h-8 rounded-full object-cover shrink-0"
                                                :alt="log.usuario_nombre" />
                                            <div v-else
                                                class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                                                style="background-color: var(--primary-color); color: var(--primary-text);">
                                                {{ (log.usuario_nombre_solo || '?')[0].toUpperCase() }}{{ (log.usuario_apellido || '')[0]?.toUpperCase() ?? '' }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium whitespace-nowrap"
                                                     style="color: var(--text-color);">
                                                    {{ log.usuario_nombre }}
                                                </div>
                                                <span :class="['badge', rolInfo(log.id_rol).badge]">
                                                    {{ rolInfo(log.id_rol).label }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Acción -->
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-semibold whitespace-nowrap"
                                              :style="accionBadgeStyle(log.accion)">
                                            {{ log.accion }}
                                        </span>
                                    </td>

                                    <!-- Descripción -->
                                    <td class="px-4 py-3 text-sm max-w-xs"
                                        style="color: var(--text-color);">
                                        <span :title="log.descripcion" class="line-clamp-2">
                                            {{ log.descripcion || '—' }}
                                        </span>
                                    </td>

                                    <!-- IP -->
                                    <td class="px-4 py-3 hidden md:table-cell font-mono text-xs whitespace-nowrap"
                                        style="color: var(--text-secondary);">
                                        {{ log.ip_origen || '—' }}
                                    </td>
                                </tr>

                                <!-- Sin resultados -->
                                <tr v-if="logs.data.length === 0">
                                    <td colspan="5" class="px-4 py-12 text-center text-sm"
                                        style="color: var(--text-secondary);">
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="text-2xl">📋</span>
                                            <span>No se encontraron registros en la bitácora.</span>
                                            <button v-if="hayFiltro()" @click="limpiarFiltros"
                                                class="text-xs underline mt-1"
                                                style="color: var(--primary-color);">
                                                Limpiar filtros
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="logs.last_page > 1"
                         class="flex flex-col sm:flex-row items-center justify-between gap-2 px-4 py-3 border-t"
                         style="border-color: var(--border-color);">
                        <p class="text-sm" style="color: var(--text-secondary);">
                            Mostrando {{ logs.from }}–{{ logs.to }} de {{ logs.total }} registros
                        </p>
                        <div class="flex gap-1 flex-wrap">
                            <template v-for="link in logs.links" :key="link.label">
                                <button v-if="link.url" @click="router.get(link.url)"
                                    class="px-3 py-1 rounded text-sm border transition"
                                    :style="link.active
                                        ? 'background-color: var(--primary-color); color: var(--primary-text); border-color: var(--primary-color);'
                                        : 'background-color: var(--card-bg); color: var(--text-color); border-color: var(--border-color);'"
                                    v-html="link.label" />
                                <span v-else
                                    class="px-3 py-1 rounded text-sm border"
                                    style="color: var(--text-secondary); border-color: var(--border-color); opacity: 0.5;"
                                    v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Badges de rol */
.badge { display: inline-flex; border-radius: 9999px; padding: 0.1rem 0.5rem; font-size: 0.65rem; font-weight: 600; }
.badge-purple { background: rgba(139,92,246,0.2);  color: #a78bfa; }
.badge-blue   { background: rgba(59,130,246,0.2);  color: #60a5fa; }
.badge-green  { background: rgba(16,185,129,0.2);  color: #34d399; }
.badge-yellow { background: rgba(245,158,11,0.2);  color: #fbbf24; }
.badge-gray   { background: rgba(107,114,128,0.2); color: #9ca3af; }

/* Icono del calendario visible en temas oscuros */
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(0.7);
    cursor: pointer;
    opacity: 0.8;
}
input[type="date"] {
    color-scheme: dark;
}

/* Texto recortado a 2 líneas */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
