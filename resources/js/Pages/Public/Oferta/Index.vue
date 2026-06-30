<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({ carreras: Array });

const busqueda = ref('');
const filtroTipo = ref('todos');

const TIPO_LABEL = {
    tecnico:          'Técnico',
    tecnico_superior: 'Técnico Superior',
    curso_libre:      'Curso Libre',
};

const formatBs = (n) => new Intl.NumberFormat('es-BO', { minimumFractionDigits: 0 }).format(n ?? 0);

const irACarreras = (tipo) => {
    const params = {};
    if (busqueda.value.trim()) params.q = busqueda.value.trim();
    if (tipo && tipo !== 'todos') params.tipo = tipo;
    router.get(route('oferta.carreras'), params);
};
</script>

<template>
    <Head title="Oferta Académica — Instituto San Pablo" />
    <PublicLayout>

        <!-- ══ HERO ══ -->
        <section class="hero">
            <div class="hero-inner">
                <div class="hero-left">
                    <div class="eyebrow">
                        <span class="eyebrow-dot"></span>
                        Inscripciones {{ new Date().getFullYear() }} abiertas
                    </div>
                    <h1 class="hero-h1">
                        Tu futuro comienza<br>
                        con la <em>carrera correcta</em>
                    </h1>
                    <p class="hero-sub">
                        Carreras técnicas y de formación superior diseñadas para el mercado laboral
                        del oriente boliviano. Inscripción 100% en línea.
                    </p>
                    <div class="search-wrap">
                        <span class="search-icon">🔍</span>
                        <input
                            v-model="busqueda"
                            class="search-input"
                            placeholder="Buscar carrera, materia…"
                            @keyup.enter="irACarreras(filtroTipo)"
                        />
                        <button class="search-btn" type="button" @click="irACarreras(filtroTipo)">Buscar</button>
                    </div>
                    <div class="pills">
                        <button class="pill" @click="irACarreras('todos')">Todos</button>
                        <button class="pill" @click="irACarreras('tecnico_superior')">Técnico Superior</button>
                        <button class="pill" @click="irACarreras('curso_libre')">Cursos Libres</button>
                        <button class="pill" @click="irACarreras('tecnico')">Técnico</button>
                    </div>
                </div>

                <!-- Tarjetas flotantes — primeras 3 carreras -->
                <div class="hero-right" v-if="carreras && carreras.length">
                    <Link v-for="c in carreras.slice(0,3)" :key="c.id_carrera"
                          :href="route('oferta.show', c.id_carrera)"
                          class="float-card">
                        <span class="fc-icon">{{ c.tipo === 'curso_libre' ? '📗' : '🎓' }}</span>
                        <div>
                            <div class="fc-name">{{ c.nombre }}</div>
                            <div class="fc-meta">{{ TIPO_LABEL[c.tipo] ?? c.tipo }} · {{ c.duracion_niveles }} {{ c.duracion_unidad === 'meses' ? 'mes(es)' : 'nivel(es)' }}</div>
                            <div class="fc-price">Bs. {{ formatBs(c.costo_carrera_completa) }}</div>
                            <span class="fc-link">Ver malla →</span>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ══ STATS ══ -->
        <div class="stats-bar">
            <div class="stat">
                <span class="stat-n">{{ carreras?.length ?? 0 }}+</span>
                <div class="stat-l">Carreras</div>
            </div>
            <div class="stat">
                <span class="stat-n">100%</span>
                <div class="stat-l">En línea</div>
            </div>
            <div class="stat">
                <span class="stat-n">{{ new Date().getFullYear() }}</span>
                <div class="stat-l">Inscripciones</div>
            </div>
            <div class="stat">
                <span class="stat-n">Bs 500</span>
                <div class="stat-l">Matrícula</div>
            </div>
        </div>

        <!-- ══ CTA VER TODO ══ -->
        <section class="explore-cta">
            <div class="explore-inner">
                <div>
                    <h2 class="explore-h2">Conocé <mark>toda nuestra oferta académica</mark></h2>
                    <p class="explore-sub">{{ carreras?.length ?? 0 }} programas disponibles — técnicos, técnicos superiores y cursos libres.</p>
                </div>
                <Link :href="route('oferta.carreras')" class="explore-btn">
                    Ver todas las carreras →
                </Link>
            </div>
        </section>

    </PublicLayout>
</template>

<style scoped>
/* ── Hero ── */
.hero {
    background: linear-gradient(135deg,
        color-mix(in srgb, var(--primary-color) 6%, var(--bg-color)) 0%,
        var(--bg-color) 60%);
    padding: 3.5rem 1.5rem 3rem;
    position: relative; overflow: hidden;
}
.hero::before {
    content: ''; position: absolute; inset: 0; pointer-events: none;
    background:
        radial-gradient(ellipse 50% 60% at 10% 50%, color-mix(in srgb, var(--primary-color) 10%, transparent) 0%, transparent 70%),
        radial-gradient(ellipse 40% 50% at 90% 20%, color-mix(in srgb, var(--primary-color) 7%, transparent) 0%, transparent 70%);
}
html[data-theme="youth"] .hero::after {
    content: ''; position: absolute; inset: 0; pointer-events: none;
    background-image:
        radial-gradient(1.5px 1.5px at 5% 10%, rgba(255,255,255,0.4) 0%, transparent 100%),
        radial-gradient(1px 1px at 22% 55%, rgba(255,255,255,0.25) 0%, transparent 100%),
        radial-gradient(1px 1px at 42% 8%, rgba(255,255,255,0.35) 0%, transparent 100%),
        radial-gradient(1.5px 1.5px at 67% 28%, rgba(255,255,255,0.3) 0%, transparent 100%),
        radial-gradient(1px 1px at 80% 65%, rgba(255,255,255,0.3) 0%, transparent 100%),
        radial-gradient(1px 1px at 90% 18%, rgba(255,255,255,0.25) 0%, transparent 100%);
    background-repeat: no-repeat;
}
.hero-inner {
    max-width: 72rem; margin: 0 auto;
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 2rem; flex-wrap: wrap; position: relative; z-index: 1;
}
.hero-left { flex: 1; min-width: 260px; }
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
.hero-h1 {
    font-size: clamp(1.7rem, 3.5vw, 2.6rem);
    font-weight: 900; letter-spacing: -0.04em; line-height: 1.08;
    color: var(--text-color); margin-bottom: 1rem;
}
.hero-h1 em { font-style: normal; color: var(--primary-color); display: block; }
.hero-sub {
    font-size: 0.93rem; color: var(--text-secondary);
    line-height: 1.75; max-width: 30rem; margin-bottom: 1.75rem;
}
.search-wrap {
    display: flex; align-items: stretch; max-width: 28rem;
    border-radius: var(--border-radius); overflow: hidden;
    border: 1.5px solid color-mix(in srgb, var(--primary-color) 30%, transparent);
    box-shadow: 0 4px 20px color-mix(in srgb, var(--primary-color) 10%, transparent);
    background: var(--card-bg);
}
.search-icon {
    padding: 0 0.75rem; font-size: 0.9rem;
    display: flex; align-items: center; color: var(--text-secondary);
}
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
.hero-right { display: flex; flex-direction: column; gap: 0.65rem; min-width: 210px; }
.float-card {
    border-radius: var(--border-radius); padding: 0.85rem 1rem;
    border: 1.5px solid var(--border-color); background: var(--card-bg);
    display: flex; align-items: flex-start; gap: 0.65rem;
    text-decoration: none; transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.float-card:hover {
    transform: translateX(-3px); border-color: var(--primary-color);
    box-shadow: 0 6px 24px color-mix(in srgb, var(--primary-color) 15%, transparent);
}
.fc-icon { font-size: 1.5rem; flex-shrink: 0; }
.fc-name { font-size: 0.82rem; font-weight: 700; color: var(--text-color); line-height: 1.3; margin-bottom: 0.2rem; }
.fc-meta { font-size: 0.67rem; color: var(--text-secondary); }
.fc-price { font-size: 0.75rem; font-weight: 700; color: var(--primary-color); margin-top: 0.2rem; }
.fc-link { font-size: 0.62rem; color: var(--primary-color); margin-top: 0.3rem; display: block; }

/* ── Stats ── */
.stats-bar {
    display: flex; flex-wrap: wrap;
    border-top: 1px solid var(--border-color);
    border-bottom: 1px solid var(--border-color);
    background: color-mix(in srgb, var(--primary-color) 4%, var(--bg-color));
}
.stat {
    flex: 1; min-width: 80px; padding: 1rem;
    text-align: center; border-right: 1px solid var(--border-color);
}
.stat:last-child { border-right: none; }
.stat-n { font-size: 1.5rem; font-weight: 900; color: var(--primary-color); display: block; line-height: 1; }
.stat-l { font-size: 0.57rem; color: var(--text-secondary); opacity: 0.7; text-transform: uppercase; letter-spacing: 0.08em; margin-top: 0.2rem; }

/* ── Explore CTA ── */
.explore-cta { padding: 2.5rem 1.5rem; max-width: 72rem; margin: 0 auto; }
.explore-inner {
    display: flex; align-items: center; justify-content: space-between;
    gap: 1.5rem; flex-wrap: wrap;
    padding: 1.75rem 2rem; border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
    background: color-mix(in srgb, var(--primary-color) 5%, var(--bg-color));
}
.explore-h2 { font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin-bottom: 0.3rem; }
.explore-h2 mark { background: none; color: var(--primary-color); }
.explore-sub { font-size: 0.8rem; color: var(--text-secondary); }
.explore-btn {
    flex-shrink: 0; font-size: 0.85rem; font-weight: 700;
    padding: 0.7rem 1.4rem; border-radius: 999px;
    background: var(--primary-color); color: var(--primary-text);
    text-decoration: none; transition: background 0.15s;
}
.explore-btn:hover { background: var(--primary-hover); }
</style>
