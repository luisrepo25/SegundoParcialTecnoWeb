<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import EstudianteLayout from '@/Layouts/EstudianteLayout.vue';

const props = defineProps({
    estudiante:                { type: Object, default: null },
    afiliacion:                { type: Object, default: null },
    pagoCarrera:                { type: Object, default: null },
    materiaEnCurso:             { type: Object, default: null },
    materiaReprobadaEsperando:  { type: Object, default: null },
    proximaMateria:             { type: Object, default: null },
    totalInscripciones:         { type: Number, default: 0 },
});

const page  = usePage();
const user  = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash ?? {});

const planLabel = (fp) => ({
    contado: 'Pago Total',
    credito: 'Enganche + Materias',
    materia: 'Por Materia',
}[fp] ?? fp);

const accesos = [
    {
        nombre: 'Mis Materias', route: 'estudiante.materias', desc: 'Plan, oferta, grupos e inscripciones',
        path: 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
    },
    {
        nombre: 'Mi Malla Académica', route: 'estudiante.malla', desc: 'Avance por nivel',
        path: 'M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z',
    },
    {
        nombre: 'Historial de Notas', route: 'estudiante.notas', desc: 'Calificaciones por materia',
        path: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
    },
    {
        nombre: 'Historial de Pagos', route: 'estudiante.pagos', desc: 'Matrícula, plan y cuotas',
        path: 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z',
    },
];
</script>

<template>
    <Head title="Dashboard" />
    <EstudianteLayout>
        <template #header>
            <span class="font-semibold text-lg" style="color: var(--text-color);">Dashboard</span>
        </template>

        <div class="space-y-5">

            <div v-if="flash.success"
                 class="rounded-lg px-4 py-3 text-sm font-medium"
                 style="background-color: #dcfce7; color: #15803d; border: 1px solid #86efac;">
                {{ flash.success }}
            </div>

            <!-- Sin perfil -->
            <div v-if="!estudiante" class="rounded-xl p-8 text-center"
                 style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <p class="font-semibold" style="color: var(--text-color);">Tu cuenta no tiene perfil de estudiante.</p>
                <p class="text-sm mt-1" style="color: var(--text-secondary);">Contacta a la secretaría para completar tu registro.</p>
            </div>

            <template v-else>

                <!-- ── SALUDO ── -->
                <div class="rounded-xl p-5" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <img v-if="user?.foto_perfil" :src="(page.props.asset_url || '') + '/imagenes/' + user.foto_perfil" class="w-14 h-14 rounded-full object-cover shrink-0">
                        <div v-else class="flex items-center justify-center w-14 h-14 rounded-full text-xl font-bold shrink-0"
                             style="background-color: var(--primary-color); color: var(--primary-text);">
                            {{ (user?.name ?? 'E')[0].toUpperCase() }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h1 class="text-2xl font-bold leading-tight" style="color: var(--text-color);">{{ user?.name }}</h1>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                      style="background-color: color-mix(in srgb, var(--primary-color) 15%, transparent); color: var(--primary-color);">
                                    {{ estudiante.legajo }}
                                </span>
                                <span v-if="estudiante.carrera" class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                      style="background-color: color-mix(in srgb, #3b82f6 15%, transparent); color: #3b82f6;">
                                    {{ estudiante.carrera.nombre }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── ESTADO ACTUAL: materia en curso / reprobada / próxima ── -->
                <div class="rounded-xl p-5"
                     :style="materiaEnCurso
                        ? 'background-color: color-mix(in srgb,#f59e0b 6%,var(--card-bg)); border: 1px solid #fcd34d;'
                        : (materiaReprobadaEsperando
                            ? 'background-color: color-mix(in srgb,#ef4444 6%,var(--card-bg)); border: 1px solid #fca5a5;'
                            : 'background-color: var(--card-bg); border: 1px solid var(--border-color);')">
                    <template v-if="materiaEnCurso">
                        <p class="text-xs font-semibold uppercase tracking-wide" style="color: #92400e;">⏳ Materia en curso</p>
                        <p class="text-lg font-bold mt-1" style="color: var(--text-color);">{{ materiaEnCurso.nombre }}</p>
                        <p class="text-xs mt-0.5" style="color: var(--text-secondary);">{{ materiaEnCurso.codigo }} — termina este mes</p>
                    </template>
                    <template v-else-if="materiaReprobadaEsperando">
                        <p class="text-xs font-semibold uppercase tracking-wide" style="color: #b91c1c;">✕ Materia reprobada</p>
                        <p class="text-lg font-bold mt-1" style="color: var(--text-color);">{{ materiaReprobadaEsperando.nombre }}</p>
                        <p class="text-xs mt-0.5" style="color: var(--text-secondary);">
                            {{ materiaReprobadaEsperando.codigo }} — espera la convocatoria del siguiente mes
                            <template v-if="materiaReprobadaEsperando.proxima_convocatoria">
                                ({{ new Date(materiaReprobadaEsperando.proxima_convocatoria + 'T12:00:00').toLocaleDateString('es-BO', { day:'2-digit', month:'short', year:'numeric' }) }})
                            </template>
                        </p>
                    </template>
                    <template v-else-if="proximaMateria">
                        <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--primary-color);">📚 Tu próxima materia</p>
                        <p class="text-lg font-bold mt-1" style="color: var(--text-color);">{{ proximaMateria.nombre }}</p>
                        <p class="text-xs mt-0.5" style="color: var(--text-secondary);">{{ proximaMateria.codigo }}</p>
                        <Link :href="route('estudiante.materias')" class="inline-block mt-3 text-xs font-semibold px-3 py-1.5 rounded-lg"
                              style="background-color: var(--primary-color); color: var(--primary-text);">
                            Ver grupos disponibles →
                        </Link>
                    </template>
                    <template v-else>
                        <p class="text-sm font-medium" style="color: var(--text-color);">Sin materia pendiente por el momento.</p>
                        <p class="text-xs mt-0.5" style="color: var(--text-secondary);">Revisa Mis Materias para más detalle.</p>
                    </template>
                </div>

                <!-- ── ESTADO MATRÍCULA Y PAGO ── -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="rounded-xl p-4" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <p class="text-xs font-semibold uppercase" style="color: var(--text-secondary);">Matrícula</p>
                        <p class="text-sm font-bold mt-1" :style="estudiante.tiene_matricula ? 'color:#15803d' : 'color:#b91c1c'">
                            {{ estudiante.tiene_matricula ? '✓ Pagada' : '✗ Pendiente' }}
                        </p>
                    </div>
                    <div class="rounded-xl p-4" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <p class="text-xs font-semibold uppercase" style="color: var(--text-secondary);">Plan de carrera</p>
                        <p class="text-sm font-bold mt-1" :style="afiliacion ? 'color:#15803d' : 'color:#b91c1c'">
                            {{ afiliacion ? planLabel(pagoCarrera?.forma_pago ?? 'materia') : '✗ Sin elegir' }}
                        </p>
                    </div>
                    <div class="rounded-xl p-4" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                        <p class="text-xs font-semibold uppercase" style="color: var(--text-secondary);">Materias cursadas</p>
                        <p class="text-sm font-bold mt-1" style="color: var(--text-color);">{{ totalInscripciones }}</p>
                    </div>
                </div>

                <!-- ── ACCESOS RÁPIDOS ── -->
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide mb-2" style="color: var(--text-secondary);">Accesos rápidos</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <Link v-for="a in accesos" :key="a.route" :href="route(a.route)"
                              class="rounded-xl p-4 flex items-center gap-3 transition-colors"
                              style="background-color: var(--card-bg); border: 1px solid var(--border-color);"
                              onmouseover="this.style.borderColor='var(--primary-color)'"
                              onmouseout="this.style.borderColor='var(--border-color)'">
                            <span class="flex items-center justify-center w-10 h-10 rounded-lg shrink-0"
                                  style="background-color: color-mix(in srgb, var(--primary-color) 12%, transparent);">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                     stroke-width="1.5" style="stroke: var(--primary-color);">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="a.path" />
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block font-semibold text-sm" style="color: var(--text-color);">{{ a.nombre }}</span>
                                <span class="block text-xs truncate" style="color: var(--text-secondary);">{{ a.desc }}</span>
                            </span>
                        </Link>
                    </div>
                </div>

            </template>
        </div>
    </EstudianteLayout>
</template>
