<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    carrera:    Object,
    malla:      Array,
    tieneMalla: Boolean,
});

const abiertos = ref(props.malla?.map((_, i) => i === 0) ?? []);
const toggle   = (i) => { abiertos.value[i] = !abiertos.value[i]; };

const TIPO_LABEL = {
    tecnico:          'Técnico',
    tecnico_superior: 'Técnico Superior',
    curso_libre:      'Curso Libre',
};
const formatBs = (n) => new Intl.NumberFormat('es-BO', { minimumFractionDigits: 0 }).format(n ?? 0);
</script>

<template>
    <Head :title="carrera.nombre + ' — Instituto San Pablo'" />
    <PublicLayout>

        <!-- ══ HEADER CARRERA ══ -->
        <section class="carrera-header">
            <div class="carrera-inner">
                <Link :href="route('oferta.index')" class="back-link">← Volver a carreras</Link>

                <div class="carrera-top">
                    <div class="carrera-info">
                        <span class="badge" :class="carrera.tipo === 'curso_libre' ? 'badge-libre' : 'badge-ts'">
                            {{ TIPO_LABEL[carrera.tipo] ?? carrera.tipo }}
                        </span>
                        <h1 class="carrera-title">{{ carrera.nombre }}</h1>
                        <p v-if="carrera.descripcion" class="carrera-desc">{{ carrera.descripcion }}</p>
                    </div>

                    <!-- Card inversión -->
                    <div class="price-card">
                        <p class="price-label">Inversión</p>
                        <div class="price-row">
                            <span class="price-sub">Matrícula</span>
                            <span class="price-val">Bs. 500</span>
                        </div>
                        <div class="price-divider"></div>
                        <div class="price-row">
                            <span class="price-sub">Carrera completa</span>
                            <span class="price-big">Bs. {{ formatBs(carrera.costo_carrera_completa) }}</span>
                        </div>
                        <div class="price-divider"></div>
                        <div class="price-row">
                            <span class="price-sub">Duración</span>
                            <span class="price-dur">
                                {{ carrera.duracion_niveles }}
                                {{ carrera.duracion_unidad === 'meses' ? 'mes(es)' : 'nivel(es)' }}
                            </span>
                        </div>
                        <Link :href="route('oferta.formulario', carrera.id_carrera)" class="btn-inscribir">
                            Inscribirme →
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══ MALLA CURRICULAR ══ -->
        <section class="malla-section">
            <div class="malla-inner">
                <h2 class="malla-title">Malla Curricular</h2>

                <!-- Sin malla -->
                <div v-if="!tieneMalla" class="malla-empty">
                    La malla curricular de esta carrera estará disponible próximamente.
                </div>

                <!-- Accordion -->
                <div v-else class="malla-list">
                    <div v-for="(nivel, i) in malla" :key="nivel.id_nivel ?? i" class="nivel-block">

                        <button type="button" class="nivel-head" @click="toggle(i)">
                            <div class="nivel-left">
                                <span class="nivel-num">{{ nivel.numero_nivel ?? '★' }}</span>
                                <span class="nivel-name">
                                    {{ nivel.nombre || (nivel.numero_nivel ? 'Nivel ' + nivel.numero_nivel : 'Módulos del Curso') }}
                                </span>
                                <span class="nivel-cnt">{{ nivel.materias.length }} materia{{ nivel.materias.length !== 1 ? 's' : '' }}</span>
                            </div>
                            <span class="nivel-arrow" :class="{ open: abiertos[i] }">▼</span>
                        </button>

                        <div v-if="abiertos[i]" class="materias">
                            <div v-if="!nivel.materias.length" class="mat-empty">
                                Sin materias registradas.
                            </div>
                            <div v-for="(m, mi) in nivel.materias" :key="mi" class="mat-row">
                                <span class="mat-code">{{ m.codigo }}</span>
                                <span class="mat-name">{{ m.nombre }}</span>
                                <span v-if="!m.obligatoria" class="mat-opt">Optativa</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CTA al final -->
                <div v-if="tieneMalla" class="malla-cta">
                    <Link :href="route('oferta.formulario', carrera.id_carrera)" class="btn-big">
                        Inscribirme en {{ carrera.nombre }} →
                    </Link>
                </div>
            </div>
        </section>

    </PublicLayout>
</template>

<style scoped>
/* ── Header ── */
.carrera-header {
    padding: 2.5rem 1.5rem 2rem;
    background: linear-gradient(135deg,
        color-mix(in srgb, var(--primary-color) 6%, var(--bg-color)) 0%,
        var(--bg-color) 70%);
    border-bottom: 1px solid var(--border-color);
}
.carrera-inner { max-width: 60rem; margin: 0 auto; }
.back-link {
    font-size: 0.78rem; color: var(--text-secondary); text-decoration: none;
    display: inline-flex; align-items: center; gap: 0.3rem;
    margin-bottom: 1.5rem; transition: color 0.15s;
}
.back-link:hover { color: var(--primary-color); }
.carrera-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 2rem; flex-wrap: wrap;
}
.carrera-info { flex: 1; min-width: 16rem; }
.badge {
    display: inline-block; font-size: 0.65rem; font-weight: 700;
    letter-spacing: 0.06em; text-transform: uppercase;
    padding: 0.2rem 0.65rem; border-radius: 999px;
}
.badge-ts {
    background: color-mix(in srgb, var(--primary-color) 12%, transparent);
    color: var(--primary-color);
}
.badge-libre {
    background: color-mix(in srgb, #059669 12%, transparent); color: #059669;
}
html[data-theme="youth"] .badge-libre { color: #34d399; background: color-mix(in srgb, #34d399 12%, transparent); }
.carrera-title {
    font-size: clamp(1.5rem, 3vw, 2.2rem); font-weight: 900;
    color: var(--text-color); letter-spacing: -0.03em;
    margin-top: 0.75rem; line-height: 1.2;
}
.carrera-desc {
    margin-top: 0.75rem; font-size: 0.93rem;
    color: var(--text-secondary); line-height: 1.7; max-width: 40rem;
}

/* ── Price Card ── */
.price-card {
    border-radius: var(--border-radius);
    border: 1.5px solid color-mix(in srgb, var(--primary-color) 25%, transparent);
    background: var(--card-bg);
    padding: 1.5rem; min-width: 17rem;
    box-shadow: 0 4px 24px color-mix(in srgb, var(--primary-color) 8%, transparent);
}
.price-label {
    font-size: 0.58rem; font-weight: 700; letter-spacing: 0.12em;
    text-transform: uppercase; color: var(--text-secondary);
    opacity: 0.65; margin-bottom: 1rem;
}
.price-row { display: flex; justify-content: space-between; align-items: center; }
.price-sub { font-size: 0.78rem; color: var(--text-secondary); }
.price-val { font-size: 1.1rem; font-weight: 700; color: var(--primary-color); }
.price-big { font-size: 1.1rem; font-weight: 700; color: var(--text-color); }
.price-dur { font-size: 0.9rem; font-weight: 600; color: var(--text-color); }
.price-divider { height: 1px; background: var(--border-color); margin: 0.75rem 0; }
.btn-inscribir {
    display: block; margin-top: 1.25rem; text-align: center;
    font-size: 0.88rem; font-weight: 700;
    background: var(--primary-color); color: var(--primary-text);
    padding: 0.7rem; border-radius: var(--border-radius);
    text-decoration: none; transition: background 0.15s;
}
.btn-inscribir:hover { background: var(--primary-hover); }

/* ── Malla ── */
.malla-section { padding: 2rem 1.5rem 4rem; }
.malla-inner { max-width: 60rem; margin: 0 auto; }
.malla-title {
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em;
    text-transform: uppercase; color: var(--text-secondary);
    opacity: 0.65; margin-bottom: 1.25rem;
}
.malla-empty {
    border: 1.5px dashed var(--border-color); border-radius: var(--border-radius);
    padding: 2.5rem; text-align: center; color: var(--text-secondary);
    font-size: 0.88rem;
}
.malla-list { display: flex; flex-direction: column; gap: 0.65rem; }
.nivel-block {
    border-radius: var(--border-radius); overflow: hidden;
    border: 1px solid var(--border-color);
    background: var(--card-bg);
}
.nivel-head {
    width: 100%; display: flex; align-items: center; justify-content: space-between;
    padding: 0.9rem 1.25rem;
    background: color-mix(in srgb, var(--primary-color) 5%, var(--card-bg));
    border: none; cursor: pointer; text-align: left;
    transition: background 0.15s;
}
.nivel-head:hover { background: color-mix(in srgb, var(--primary-color) 10%, var(--card-bg)); }
.nivel-left { display: flex; align-items: center; gap: 0.75rem; }
.nivel-num {
    width: 1.7rem; height: 1.7rem; border-radius: 0.35rem; flex-shrink: 0;
    background: var(--primary-color); color: var(--primary-text);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem; font-weight: 800;
}
.nivel-name { font-size: 0.9rem; font-weight: 600; color: var(--text-color); }
.nivel-cnt { font-size: 0.68rem; color: var(--text-secondary); }
.nivel-arrow {
    color: var(--text-secondary); font-size: 0.7rem;
    transition: transform 0.2s;
}
.nivel-arrow.open { transform: rotate(180deg); }

.materias { border-top: 1px solid var(--border-color); }
.mat-empty { padding: 1rem 1.25rem; font-size: 0.82rem; color: var(--text-secondary); }
.mat-row {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.6rem 1.25rem;
    border-top: 1px solid color-mix(in srgb, var(--border-color) 50%, transparent);
}
.mat-row:first-child { border-top: none; }
.mat-code {
    font-size: 0.67rem; font-weight: 700; color: var(--text-secondary);
    font-family: monospace; min-width: 70px;
}
.mat-name { font-size: 0.85rem; color: var(--text-color); flex: 1; }
.mat-opt {
    font-size: 0.57rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;
    padding: 0.12rem 0.45rem; border-radius: 999px;
    background: color-mix(in srgb, #f59e0b 12%, transparent); color: #f59e0b;
}

/* ── CTA ── */
.malla-cta { text-align: center; margin-top: 2rem; }
.btn-big {
    display: inline-flex; align-items: center; gap: 0.5rem;
    font-size: 0.95rem; font-weight: 700;
    background: var(--primary-color); color: var(--primary-text);
    padding: 0.8rem 2rem; border-radius: 999px;
    text-decoration: none; transition: background 0.15s;
    box-shadow: 0 4px 16px color-mix(in srgb, var(--primary-color) 35%, transparent);
}
.btn-big:hover { background: var(--primary-hover); }

@media (max-width: 640px) {
    .carrera-top { flex-direction: column; }
    .price-card { min-width: 0; width: 100%; }
}
</style>
