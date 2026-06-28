<script setup>
import { ref, computed } from 'vue';
import InstituteLogo from '@/Components/InstituteLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import ThemeBar from '@/Components/ThemeBar.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme';

const showingNavigationDropdown = ref(false);
const page = usePage();

const roleLabels = {
    propietario: { label: 'Propietario', icon: '🏛️' },
    director:    { label: 'Director',    icon: '🎓' },
    secretaria:  { label: 'Secretaría',  icon: '📋' },
    profesor:    { label: 'Docente',     icon: '👨‍🏫' },
    estudiante:  { label: 'Estudiante',  icon: '📚' },
};

const userRole = computed(() => page.props.auth?.user?.role ?? 'estudiante');
const roleInfo = computed(() => roleLabels[userRole.value] ?? { label: 'Panel', icon: '🏠' });

</script>

<template>
    <div>
        <div class="min-h-screen" style="background-color: var(--bg-color);">
            <nav class="border-b" style="background-color: var(--nav-bg); border-color: var(--border-color);">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 items-center justify-between">

                        <!-- Izquierda: Logo + nombre panel -->
                        <div class="flex items-center gap-4">
                            <Link :href="route('dashboard')" class="flex shrink-0 items-center">
                                <InstituteLogo :size="38" />
                            </Link>
                            <div class="hidden sm:flex items-center gap-2.5">
                                <div class="leading-tight">
                                    <p class="font-bold text-sm leading-none" style="color: var(--text-color);">
                                        Instituto San Pablo
                                    </p>
                                    <p class="text-xs leading-none mt-0.5" style="color: #f59e0b;">
                                        del Oriente
                                    </p>
                                </div>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                    style="background-color: var(--primary-color); color: var(--primary-text);">
                                    {{ roleInfo.label }}
                                </span>
                            </div>
                        </div>

                        <!-- Derecha: controles + usuario -->
                        <div class="hidden sm:flex items-center gap-3">

                            <!-- Controles de tema/fuente/contraste (componente reutilizable) -->
                            <ThemeBar />

                            <!-- Separador -->
                            <div class="h-6 w-px" style="background-color: var(--border-color);"></div>

                            <!-- Menú usuario -->
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button type="button"
                                        class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200"
                                        style="background-color: var(--bg-color); color: var(--text-color); border: 1px solid var(--border-color);">
                                          <img v-if="$page.props.auth.user.foto_perfil" :src="($page.props.asset_url || '') + '/imagenes/' + $page.props.auth.user.foto_perfil" alt="User" class="w-6 h-6 rounded-full object-cover border border-[var(--border-color)]">
                                          <div v-else class="flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold"
                                              style="background-color: var(--primary-color); color: var(--primary-text);">
                                              {{ ($page.props.auth.user.name ?? 'U')[0].toUpperCase() }}
                                          </div>
                                        <span class="hidden lg:block max-w-[120px] truncate">{{ $page.props.auth.user.name }}</span>
                                        <svg class="h-3.5 w-3.5 opacity-60" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>
                                <template #content>
                                    <div class="px-4 py-2 border-b" style="border-color: var(--border-color);">
                                        <p class="text-xs font-semibold" style="color: var(--text-secondary);">{{ roleInfo.label }}</p>
                                        <p class="text-xs truncate" style="color: var(--text-secondary);">{{ $page.props.auth.user.email }}</p>
                                    </div>
                                    <DropdownLink :href="$page.props.auth.user?.role === 'estudiante' ? route('estudiante.perfil') : route('profile.edit')">Perfil</DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">Cerrar sesión</DropdownLink>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- Hamburger móvil -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-md p-2 transition"
                                style="color: var(--text-secondary);">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Menú móvil desplegable -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                    <div class="border-b px-4 py-3 space-y-2" style="border-color: var(--border-color); background-color: var(--card-bg);">
                        <p class="text-xs font-semibold" style="color: var(--text-secondary);">Apariencia</p>
                        <ThemeBar />
                    </div>

                    <div class="border-b py-3 px-4" style="border-color: var(--border-color);">
                        <p class="font-medium text-sm" style="color: var(--text-color);">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs" style="color: var(--text-secondary);">{{ $page.props.auth.user.email }}</p>
                    </div>

                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink :href="$page.props.auth.user?.role === 'estudiante' ? route('estudiante.perfil') : route('profile.edit')">Perfil</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">Cerrar sesión</ResponsiveNavLink>
                    </div>
                </div>
            </nav>

            <!-- Encabezado de página -->
            <header class="shadow" v-if="$slots.header" style="background-color: var(--card-bg); border-bottom: 1px solid var(--border-color);">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Contenido -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
