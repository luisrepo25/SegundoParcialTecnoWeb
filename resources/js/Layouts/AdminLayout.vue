<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import InstituteLogo from '@/Components/InstituteLogo.vue';
import ThemeBar from '@/Components/ThemeBar.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const dashboardRoute = computed(() => {
    const role = user.value?.role;
    if (role === 'director')   return 'dashboard.director';
    if (role === 'propietario') return 'dashboard.propietario';
    if (role === 'secretaria') return 'secretaria.dashboard';
    return 'dashboard';
});

const dashboardUrl = computed(() => {
    const role = user.value?.role;
    if (role === 'director')   return '/panel/director';
    if (role === 'propietario') return '/panel/propietario';
    return '/secretaria/panel';
});

const perfilRoute = computed(() => {
    const role = user.value?.role;
    if (role === 'propietario') return 'propietario.perfil';
    if (role === 'director')    return 'director.perfil';
    if (role === 'secretaria')  return 'secretaria.perfil';
    return 'profile.edit';
});

const roleBadge = computed(() => {
    const role = user.value?.role;
    if (role === 'director')   return { emoji: '🎓', label: 'Director' };
    if (role === 'secretaria') return { emoji: '📋', label: 'Secretaría' };
    return { emoji: '🏛️', label: 'Propietario' };
});

const todasSecciones = [
    {
        key: 'academico',
        label: 'Académico',
        items: [
            { name: 'CU3 · Materias',            route: 'director.materias.index',       activeUrls: ['/director/materias'],          roles: null },
            { name: 'CU4/5 · Carreras y Malla',  route: 'director.carreras.index',       activeUrls: ['/director/carreras'],          roles: null },
            { name: 'CU8 · Períodos Académicos', route: 'director.periodos.index',       activeUrls: ['/director/periodos'],          roles: null },
            { name: 'CU9/12 · Grupos y Notas',   route: 'director.grupos.index',         activeUrls: ['/director/grupos'],            roles: null },
            { name: 'CU10 · Cronogramas',        route: 'secretaria.cronogramas.index',  activeUrls: ['/secretaria/cronogramas'],     roles: null },
            { name: 'CU13 · Seguimiento',        route: 'propietario.seguimiento.index', activeUrls: ['/propietario/seguimiento'],    roles: ['propietario', 'director'] },
        ],
    },
    {
        key: 'financiero',
        label: 'Financiero',
        items: [
            { name: 'CU6 · Inscripciones', route: 'secretaria.inscripciones.index', activeUrls: ['/secretaria/inscripciones'], roles: null },
            { name: 'CU7 · Caja y Pagos',  route: 'secretaria.pagos.index',         activeUrls: ['/secretaria/pagos'],         roles: null },
        ],
    },
    {
        key: 'administrativo',
        label: 'Administrativo',
        items: [
            { name: 'CU1 · Gestión de Usuarios', route: 'propietario.usuarios.index', activeUrls: ['/propietario/usuarios'], roles: null },
            { name: 'CU2 · Gestión de Aulas',    route: 'propietario.aulas.index',    activeUrls: ['/propietario/aulas'],    roles: ['propietario'] },
            { name: 'CU11 · Gestión de Horarios',route: 'propietario.horarios.index', activeUrls: ['/propietario/horarios'], roles: ['propietario'] },
        ],
    },
];

const secciones = computed(() => {
    const role = user.value?.role;
    return todasSecciones.map(sec => ({
        ...sec,
        items: sec.items.filter(item => !item.roles || item.roles.includes(role)),
    })).filter(sec => sec.items.length > 0);
});

const reportesItem = { name: 'Reportes y Estadísticas', route: 'propietario.reportes.index', activeUrls: ['/propietario/reportes'] };

function detectActiveSection(url) {
    if (url.startsWith('/director/') || url.startsWith('/secretaria/cronogramas') || url.startsWith('/propietario/seguimiento')) return 'academico';
    if (url.startsWith('/secretaria/inscripciones') || url.startsWith('/secretaria/pagos')) return 'financiero';
    if (url.startsWith('/propietario/usuarios') || url.startsWith('/propietario/aulas') || url.startsWith('/propietario/horarios')) return 'administrativo';
    return null;
}

const activeSection = detectActiveSection(page.url);
const openSections = ref({
    academico:      activeSection === 'academico',
    financiero:     activeSection === 'financiero',
    administrativo: activeSection === 'administrativo',
});
const toggleSection = (key) => { openSections.value[key] = !openSections.value[key]; };

const isActive = (urls) => urls.some(url => page.url.startsWith(url));

const showMobileMenu = ref(false);
const showUserMenu   = ref(false);
</script>

<template>
    <div class="flex h-screen overflow-hidden" style="background-color: var(--bg-color); font-family: Inter, system-ui, sans-serif;">

        <!-- Mobile overlay -->
        <div v-if="showMobileMenu"
             @click="showMobileMenu = false"
             class="fixed inset-0 z-40 bg-black bg-opacity-30 lg:hidden">
        </div>

        <!-- SIDEBAR -->
        <aside :class="[
                'fixed inset-y-0 left-0 z-50 w-[260px] flex flex-col transition-transform duration-300 lg:static lg:translate-x-0',
                showMobileMenu ? 'translate-x-0' : '-translate-x-full'
            ]"
            style="background-color: var(--card-bg); border-right: 1px solid var(--border-color);">

            <!-- Logo -->
            <div class="h-16 flex items-center justify-between px-4 shrink-0 border-b" style="border-color: var(--border-color);">
                <Link :href="route(dashboardRoute)" class="flex items-center gap-3 flex-1 overflow-hidden" @click="showMobileMenu = false">
                    <InstituteLogo :size="36" />
                    <div class="leading-tight overflow-hidden">
                        <p class="font-bold text-sm leading-none truncate" style="color: var(--text-color);">Instituto San Pablo</p>
                        <p class="text-xs leading-none mt-0.5 truncate" style="color: #f59e0b;">del Oriente</p>
                    </div>
                </Link>
                <button @click="showMobileMenu = false" class="lg:hidden text-xs font-medium ml-2" style="color: var(--text-secondary);">✕</button>
            </div>

            <!-- Badge rol -->
            <div class="px-4 pt-3 pb-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                      style="background-color: var(--primary-color); color: var(--primary-text);">
                    {{ roleBadge.label }}
                </span>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto pb-4 px-4 space-y-0.5">

                <!-- Principal -->
                <p class="px-2 pt-3 pb-1.5 text-[11px] font-semibold uppercase tracking-widest opacity-50" style="color: var(--text-secondary);">Principal</p>
                <Link
                    :href="route(dashboardRoute)"
                    @click="showMobileMenu = false"
                    class="flex items-center gap-2 px-3 py-2.5 rounded-md text-sm font-medium transition-colors duration-150"
                    :style="page.url.startsWith(dashboardUrl)
                        ? 'background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);'
                        : 'color: var(--text-secondary);'"
                >
                    Dashboard
                </Link>

                <!-- Reportes — propietario y director -->
                <Link v-if="user?.role === 'propietario' || user?.role === 'director'"
                    :href="route(reportesItem.route)"
                    @click="showMobileMenu = false"
                    class="flex items-center gap-2 px-3 py-2.5 rounded-md text-sm font-medium transition-colors duration-150"
                    :style="isActive(reportesItem.activeUrls)
                        ? 'background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);'
                        : 'color: var(--text-secondary);'"
                >
                    Reportes
                </Link>

                <!-- Secciones colapsables -->
                <div v-for="sec in secciones" :key="sec.key" class="pt-1">
                    <!-- Header de sección (colapsable) -->
                    <button
                        @click="toggleSection(sec.key)"
                        class="w-full flex items-center justify-between px-2 py-1.5 rounded text-[11px] font-semibold uppercase tracking-widest transition-colors"
                        style="color: var(--text-secondary);"
                        onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--text-color) 4%, transparent)'"
                        onmouseout="this.style.backgroundColor='transparent'"
                    >
                        <span class="opacity-60">{{ sec.label }}</span>
                        <span class="opacity-50 text-[10px] transition-transform duration-200"
                              :style="openSections[sec.key] ? 'transform: rotate(180deg)' : ''">▾</span>
                    </button>

                    <!-- Items de sección -->
                    <div v-show="openSections[sec.key]" class="mt-0.5 space-y-0.5">
                        <Link
                            v-for="mod in sec.items"
                            :key="mod.name"
                            :href="route(mod.route)"
                            @click="showMobileMenu = false"
                            class="block px-3 py-2.5 rounded-md text-sm font-medium transition-colors duration-150 pl-5"
                            :style="isActive(mod.activeUrls)
                                ? 'background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);'
                                : 'color: var(--text-secondary);'"
                        >
                            {{ mod.name }}
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- Footer usuario -->
            <div class="p-4 border-t shrink-0 relative" style="border-color: var(--border-color);">
                <Transition
                    enter-active-class="transition ease-out duration-150"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-1"
                >
                    <div v-if="showUserMenu" class="absolute bottom-full left-0 w-full px-4 mb-2 z-50">
                        <div class="rounded-lg border py-1 shadow-sm"
                             style="background-color: var(--card-bg); border-color: var(--border-color);">
                            <div class="px-4 py-2 border-b" style="border-color: var(--border-color);">
                                <p class="text-xs truncate" style="color: var(--text-secondary);">{{ user?.email }}</p>
                            </div>
                            <Link :href="route(perfilRoute)"
                                class="block px-4 py-2 text-sm transition-colors"
                                style="color: var(--text-color);"
                                onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--text-color) 5%, transparent)'"
                                onmouseout="this.style.backgroundColor='transparent'">
                                Perfil
                            </Link>
                            <Link :href="route('logout')" method="post" as="button"
                                class="w-full text-left block px-4 py-2 text-sm transition-colors"
                                style="color: var(--text-color);"
                                onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--text-color) 5%, transparent)'"
                                onmouseout="this.style.backgroundColor='transparent'">
                                Cerrar sesión
                            </Link>
                        </div>
                    </div>
                </Transition>

                <button type="button" @click="showUserMenu = !showUserMenu"
                    class="w-full text-left flex items-center justify-between p-2 rounded-lg transition-colors"
                    onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--text-color) 5%, transparent)'"
                    onmouseout="this.style.backgroundColor='transparent'">
                    <div class="flex items-center gap-3 flex-1 overflow-hidden">
                          <img v-if="user?.foto_perfil" :src="($page.props.asset_url || '') + '/imagenes/' + user.foto_perfil" alt="User" class="w-8 h-8 rounded-full object-cover">
                          <div v-else class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
                               style="background-color: var(--primary-color); color: var(--primary-text);">
                              {{ (user?.name ?? 'A')[0].toUpperCase() }}
                          </div>
                        <div class="flex-1 overflow-hidden">
                            <span class="block text-sm font-medium truncate" style="color: var(--text-color);">{{ user?.name }}</span>
                            <span class="block text-[11px] truncate" style="color: var(--text-secondary);">Opciones de cuenta</span>
                        </div>
                    </div>
                    <span class="text-[10px] opacity-50 shrink-0 ml-2" style="color: var(--text-secondary);">▼</span>
                </button>
            </div>

            <div v-if="showUserMenu" @click="showUserMenu = false" class="fixed inset-0 z-40"></div>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Top bar -->
            <header class="h-16 flex items-center justify-between px-6 shrink-0 z-10 border-b shadow-sm"
                    style="background-color: var(--card-bg); border-color: var(--border-color);">
                <div class="flex items-center gap-4 w-full">
                    <button @click="showMobileMenu = true" class="lg:hidden text-sm font-medium" style="color: var(--text-secondary);">MENÚ</button>
                    <div class="flex-1 min-w-0 truncate pr-4">
                        <slot name="header" />
                    </div>
                </div>
                <div class="flex items-center gap-4 pl-4 border-l" style="border-color: var(--border-color);">
                    <ThemeBar />
                </div>
            </header>

            <!-- Contenido scrolleable -->
            <main class="flex-1 overflow-y-auto px-6 py-8">
                <div class="mx-auto max-w-6xl">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
::-webkit-scrollbar { width: 4px; height: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background-color: var(--border-color); border-radius: 4px; }
</style>
