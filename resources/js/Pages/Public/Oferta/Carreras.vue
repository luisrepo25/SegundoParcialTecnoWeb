<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    carreras: Array,
    filtros: Object,
});

const busqueda = ref(props.filtros?.q ?? '');
const filtroTipo = ref(props.filtros?.tipo ?? 'todos');

const TIPO_LABEL = {
    tecnico:          'Técnico',
    tecnico_superior: 'Técnico Superior',
    curso_libre:      'Curso Libre',
};

const carrerasFiltradas = computed(() => {
    return (props.carreras ?? []).filter(c => {
        const matchTipo = filtroTipo.value === 'todos' || c.tipo === filtroTipo.value;
        const q = busqueda.value.trim().toLowerCase();
        const matchQ = !q || c.nombre.toLowerCase().includes(q) || (c.descripcion ?? '').toLowerCase().includes(q);
        return matchTipo && matchQ;
    });
});

const formatBs = (n) => new Intl.NumberFormat('es-BO', { minimumFractionDigits: 0 }).format(n ?? 0);

const tipoClass = (tipo) => tipo === 'curso_libre' ? 'badge-libre' : 'badge-ts';
</script>

<template>
    <Head title="Carreras — Instituto San Pablo" />
    <PublicLayout>

        <!-- ══ CABECERA ══ -->
        <section class="carreras-head">
            <div class="carreras-head-inner">
                <div class="eyebrow">
                    <span class="eyebrow-dot"></span>
                    Oferta académica completa
                </div>
                <h1 class="head-h1">Todas nuestras <em>carreras</em></h1>
                <p class="head-sub">
                    Explorá técnicos, técnicos superiores y cursos libres. Filtrá por tipo o buscá por nombre.
                </p>
                <div class="search-wrap">
                    <span class="search-icon">🔍</span>
                    <input
                        v-model="busqueda"
                        class="search-input"
                        placeholder="Buscar carrera, materia…"
                    />
                    <button class="search-btn" type="button">Buscar</button>
                </div>
                <div class="pills">
                    <button :class="['pill', filtroTipo === 'todos' ? 'active' : '']"
                            @click="filtroTipo = 'todos'">Todos</button>
                    <button :class="['pill', filtroTipo === 'tecnico_superior' ? 'active' : '']"
                            @click="filtroTipo = 'tecnico_superior'">Técnico Superior</button>
                    <button :class="['pill', filtroTipo === 'curso_libre' ? 'active' : '']"
                            @click="filtroTipo = 'curso_libre'">Cursos Libres</button>
                    <button :class="['pill', filtroTipo === 'tecnico' ? 'active' : '']"
                            @click="filtroTipo = 'tecnico'">Técnico</button>
                </div>
            </div>
        </section>

        <!-- ══ GRID CARRERAS ══ -->
        <section class="section">
            <div class="section-head">
                <div class="section-title">
                    Toda nuestra <mark>oferta académica</mark>
                </div>
                <span class="section-cnt" v-if="carrerasFiltradas.length !== carreras?.length">
                    {{ carrerasFiltradas.length }} resultado{{ carrerasFiltradas.length !== 1 ? 's' : '' }}
                </span>
            </div>

            <div v-if="!carrerasFiltradas.length" class="empty-state">
                <p>No se encontraron carreras con esos filtros.</p>
                <button class="btn-reset" @click="busqueda=''; filtroTipo='todos'">Limpiar filtros</button>
            </div>

            <div v-else class="cards-grid">
                <div v-for="c in carrerasFiltradas" :key="c.id_carrera" class="card">
                    <span :class="['badge', tipoClass(c.tipo)]">{{ TIPO_LABEL[c.tipo] ?? c.tipo }}</span>
                    <div class="card-name">{{ c.nombre }}</div>
                    <p v-if="c.descripcion" class="card-desc">{{ c.descripcion }}</p>
                    <div class="card-meta">
                        <div>
                            <div class="meta-l">Duración</div>
                            <div class="meta-v">{{ c.duracion_niveles }} {{ c.duracion_unidad === 'meses' ? 'mes(es)' : 'nivel(es)' }}</div>
                        </div>
                        <div>
                            <div class="meta-l">Inversión</div>
                            <div class="meta-v meta-price">Bs. {{ formatBs(c.costo_carrera_completa) }}</div>
                        </div>
                    </div>
                    <div class="card-foot">
                        <Link :href="route('oferta.show', c.id_carrera)" class="btn-link">
                            Ver malla →
                        </Link>
                        <Link :href="route('oferta.formulario', c.id_carrera)" class="btn-cta">
                            Inscribirme
                        </Link>
                    </div>
                </div>
            </div>
        </section>

    </PublicLayout>
</template>

<style scoped>
/* ── Cabecera ── */
.carreras-head {
    background: linear-gradient(135deg,
        color-mix(in srgb, var(--primary-color) 6%, var(--bg-color)) 0%,
        var(--bg-color) 60%);
    padding: 3rem 1.5rem 2.5rem;
    position: relative; overflow: hidden;
}
.carreras-head::before {
    content: ''; position: absolute; inset: 0; pointer-events: none;
    background: radial-gradient(ellipse 50% 60% at 10% 30%, color-mix(in srgb, var(--primary-color) 10%, transparent) 0%, transparent 70%);
}
.carreras-head-inner { max-width: 42rem; margin: 0 auto; position: relative; z-index: 1; }
.eyebrow {
    display: inline-flex; align-items: center; gap: 0.4rem;
    font-size: 0.63rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--primary-color);
    padding: 0.28rem 0.8rem; border-radius: 999px;
    background: color-mix(in srgb, var(--primary-color) 10%, transparent);
    border: 1px solid color-mix(in srgb, var(--primary-color) 25%, transparent);
    margin-bottom: 1rem;
}
.eyebrow-dot { width: 5px; height: 5px; border-radius: 50%; background: var(--primary-color); flex-shrink: 0; }
.head-h1 {
    font-size: clamp(1.6rem, 3vw, 2.3rem);
    font-weight: 900; letter-spacing: -0.04em; line-height: 1.1;
    color: var(--text-color); margin-bottom: 0.75rem;
}
.head-h1 em { font-style: normal; color: var(--primary-color); }
.head-sub {
    font-size: 0.9rem; color: var(--text-secondary);
    line-height: 1.7; margin-bottom: 1.5rem;
}
.search-wrap {
    display: flex; align-items: stretch; max-width: 28rem;
    border-radius: var(--border-radius); overflow: hidden;
    border: 1.5px solid color-mix(in srgb, var(--primary-color) 30%, transparent);
    box-shadow: 0 4px 20px color-mix(in srgb, var(--primary-color) 10%, transparent);
    background: var(--card-bg);
}
.search-icon { padding: 0 0.75rem; font-size: 0.9rem; display: flex; align-items: center; color: var(--text-secondary); }
.search-input {
    flex: 1; border: none; outline: none; background: transparent;
    font-size: 0.88rem; color: var(--text-color); padding: 0.75rem 0;
    font-family: var(--font-family);
}
.search-input::placeholder { color: var(--text-secondary); opacity: 0.6; }
.search-btn {
    padding: 0 1.4rem; border: none; background: var(--primary-color);
    color: var(--primary-text); font-size: 0.82rem; font-weight: 700;
    cursor: pointer; transition: background 0.15s; font-family: var(--font-family);
}
.search-btn:hover { background: var(--primary-hover); }
.pills { display: flex; gap: 0.4rem; flex-wrap: wrap; margin-top: 0.9rem; }
.pill {
    font-size: 0.7rem; padding: 0.25rem 0.8rem; border-radius: 999px;
    border: 1px solid var(--border-color); color: var(--text-secondary);
    cursor: pointer; background: transparent; transition: all 0.15s;
    font-family: var(--font-family);
}
.pill.active {
    background: color-mix(in srgb, var(--primary-color) 12%, transparent);
    border-color: color-mix(in srgb, var(--primary-color) 40%, transparent);
    color: var(--primary-color); font-weight: 700;
}

/* ── Section ── */
.section { padding: 2rem 1.5rem 4rem; max-width: 72rem; margin: 0 auto; }
.section-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
.section-title { font-size: 1.05rem; font-weight: 800; color: var(--text-color); }
.section-title mark { background: none; color: var(--primary-color); }
.section-cnt { font-size: 0.75rem; color: var(--text-secondary); }

/* ── Cards Grid ── */
.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1rem;
}
.card {
    border-radius: var(--border-radius); padding: 1.25rem;
    background: var(--card-bg); border: 1px solid var(--border-color);
    display: flex; flex-direction: column; gap: 0.7rem;
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}
.card:hover {
    transform: translateY(-3px); border-color: var(--primary-color);
    box-shadow: 0 8px 30px color-mix(in srgb, var(--primary-color) 12%, transparent);
}
.badge {
    display: inline-block; font-size: 0.6rem; font-weight: 700;
    letter-spacing: 0.05em; text-transform: uppercase;
    padding: 0.18rem 0.55rem; border-radius: 999px;
}
.badge-ts {
    background: color-mix(in srgb, var(--primary-color) 12%, transparent);
    color: var(--primary-color);
}
.badge-libre {
    background: color-mix(in srgb, #059669 12%, transparent);
    color: #059669;
}
html[data-theme="youth"] .badge-libre { color: #34d399; background: color-mix(in srgb, #34d399 12%, transparent); }
.card-name { font-size: 0.9rem; font-weight: 700; color: var(--text-color); line-height: 1.3; }
.card-desc {
    font-size: 0.72rem; color: var(--text-secondary); line-height: 1.6;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.card-meta { display: flex; gap: 0.75rem; }
.meta-l { font-size: 0.55rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--text-secondary); opacity: 0.7; margin-bottom: 0.1rem; }
.meta-v { font-size: 0.78rem; font-weight: 600; color: var(--text-color); }
.meta-price { color: var(--primary-color); }
.card-foot {
    border-top: 1px solid var(--border-color); padding-top: 0.75rem;
    display: flex; justify-content: space-between; align-items: center;
}
.btn-link {
    font-size: 0.7rem; font-weight: 600; color: var(--primary-color);
    text-decoration: none; transition: opacity 0.15s;
}
.btn-link:hover { opacity: 0.75; }
.btn-cta {
    font-size: 0.7rem; font-weight: 700; padding: 0.38rem 0.85rem;
    border-radius: 999px; background: var(--primary-color);
    color: var(--primary-text); text-decoration: none;
    transition: background 0.15s;
}
.btn-cta:hover { background: var(--primary-hover); }

/* ── Empty state ── */
.empty-state {
    text-align: center; padding: 3rem; color: var(--text-secondary);
    border: 1.5px dashed var(--border-color); border-radius: var(--border-radius);
}
.btn-reset {
    margin-top: 0.75rem; padding: 0.4rem 1rem; border-radius: 999px;
    border: 1px solid var(--border-color); background: transparent;
    color: var(--primary-color); cursor: pointer; font-family: var(--font-family);
    font-size: 0.78rem; transition: all 0.15s;
}
.btn-reset:hover { background: var(--primary-color); color: var(--primary-text); }
</style>
