<script setup>
import DirectorLayout from '@/Layouts/DirectorLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    totalCarreras: Number,
    totalMaterias: Number,
    carrerasActivas: Number,
});

const resumen = [
    { label: 'Carreras Registradas', valor: props.totalCarreras ?? 0,   detalle: 'Total en el sistema' },
    { label: 'Carreras Activas',     valor: props.carrerasActivas ?? 0,  detalle: 'Disponibles para inscripción' },
    { label: 'Materias Registradas', valor: props.totalMaterias ?? 0,    detalle: 'Total en catálogo' },
];

const modulos = [
    {
        numero:      'CU4',
        titulo:      'Gestión de Carreras',
        descripcion: 'Registrar, editar y administrar carreras técnicas y cursos libres.',
        ruta:        'director.carreras.index',
    },
    {
        numero:      'CU5',
        titulo:      'Gestión de Materias',
        descripcion: 'Registrar, editar y administrar materias, costos y requisitos.',
        ruta:        'director.materias.index',
    },
];
</script>

<template>
    <Head title="Panel Director" />

    <DirectorLayout>
        <template #header>
            <h2 class="text-xl font-semibold tracking-tight" style="color: var(--text-color);">
                Panel de Dirección
            </h2>
        </template>

        <div class="space-y-10">

            <!-- Bienvenida -->
            <div>
                <p class="text-sm font-medium uppercase tracking-widest opacity-60" style="color: var(--text-secondary);">
                    {{ new Date().toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                </p>
                <h3 class="text-2xl font-light mt-1" style="color: var(--text-color);">
                    Bienvenido, <span class="font-medium">{{ $page.props.auth.user.name.split(' ')[0] }}</span>
                </h3>
            </div>

            <!-- Métricas -->
            <section>
                <h3 class="text-[11px] font-semibold uppercase tracking-widest mb-4 opacity-50" style="color: var(--text-secondary);">Resumen</h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div v-for="item in resumen" :key="item.label"
                         class="flex flex-col p-5 rounded-xl border shadow-sm"
                         style="background-color: var(--card-bg); border-color: var(--border-color);">
                        <p class="text-[13px] font-medium mb-1" style="color: var(--text-secondary);">{{ item.label }}</p>
                        <p class="text-4xl font-light tracking-tight mb-2" style="color: var(--text-color);">{{ item.valor }}</p>
                        <p class="text-xs opacity-60" style="color: var(--text-secondary);">{{ item.detalle }}</p>
                    </div>
                </div>
            </section>

            <!-- Módulos -->
            <section>
                <h3 class="text-[11px] font-semibold uppercase tracking-widest mb-4 opacity-50" style="color: var(--text-secondary);">Módulos</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Link
                        v-for="m in modulos"
                        :key="m.ruta"
                        :href="route(m.ruta)"
                        class="group relative flex flex-col justify-between p-6 rounded-xl border shadow-sm transition-all duration-200 hover:shadow-md hover:-translate-y-0.5"
                        style="background-color: var(--card-bg); border-color: var(--border-color);">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-widest mb-2 opacity-50" style="color: var(--text-secondary);">{{ m.numero }}</p>
                            <h4 class="text-[15px] font-medium mb-2" style="color: var(--text-color);">{{ m.titulo }}</h4>
                            <p class="text-[13px] leading-relaxed opacity-70" style="color: var(--text-secondary);">{{ m.descripcion }}</p>
                        </div>
                        <div class="mt-8 flex items-center justify-between">
                            <span class="text-[11px] font-medium uppercase tracking-wider opacity-60 group-hover:opacity-100 transition-opacity" style="color: var(--text-color);">Ingresar al módulo</span>
                            <span class="opacity-40 group-hover:opacity-100 group-hover:translate-x-1 transition-all duration-200" style="color: var(--text-color);">→</span>
                        </div>
                    </Link>
                </div>
            </section>

        </div>
    </DirectorLayout>
</template>
