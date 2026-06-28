<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({ carreras: Array });

const TIPO_LABEL = {
    tecnico:          { label: 'Técnico',          bg: 'rgba(56,189,248,.12)', color: '#38bdf8' },
    tecnico_superior: { label: 'Técnico Superior', bg: 'rgba(129,140,248,.12)', color: '#818cf8' },
    curso_libre:      { label: 'Curso Libre',      bg: 'rgba(52,211,153,.12)', color: '#34d399' },
};

const formatBs = (n) => new Intl.NumberFormat('es-BO', { minimumFractionDigits: 0 }).format(n ?? 0);
</script>

<template>
    <Head title="Oferta Académica — Instituto San Pablo" />
    <PublicLayout>

        <!-- Hero -->
        <section style="padding:4rem 1.5rem 3rem;text-align:center;background:radial-gradient(ellipse 80% 50% at 50% 0%,rgba(56,189,248,0.08) 0%,transparent 70%);">
            <p style="font-size:0.72rem;font-weight:700;letter-spacing:0.12em;color:#38bdf8;text-transform:uppercase;margin-bottom:0.75rem;">Instituto San Pablo del Oriente</p>
            <h1 style="font-size:clamp(2rem,4vw,3rem);font-weight:800;color:#f1f5f9;letter-spacing:-0.03em;margin-bottom:1rem;line-height:1.1;">
                Nuestra oferta<br>
                <span style="background:linear-gradient(90deg,#38bdf8,#818cf8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">académica</span>
            </h1>
            <p style="max-width:36rem;margin:0 auto;font-size:1rem;color:#94a3b8;line-height:1.7;">
                Carreras técnicas y de formación superior diseñadas para el mercado laboral del oriente boliviano.
            </p>
        </section>

        <!-- Grid de carreras -->
        <section style="max-width:72rem;margin:0 auto;padding:0 1.5rem 5rem;">
            <div v-if="!carreras.length" style="text-align:center;padding:4rem;color:#64748b;">
                No hay carreras disponibles en este momento.
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(18rem,1fr));gap:1.25rem;">
                <div v-for="c in carreras" :key="c.id_carrera"
                     style="border-radius:1rem;border:1px solid rgba(255,255,255,0.08);background:rgba(15,23,42,0.6);padding:1.75rem;display:flex;flex-direction:column;gap:1rem;transition:border-color 0.2s,background 0.2s;"
                     onmouseover="this.style.borderColor='rgba(56,189,248,0.3)';this.style.background='rgba(15,23,42,0.9)'"
                     onmouseout="this.style.borderColor='rgba(255,255,255,0.08)';this.style.background='rgba(15,23,42,0.6)'">

                    <div>
                        <!-- Tipo badge -->
                        <span style="font-size:0.7rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;padding:0.2rem 0.6rem;border-radius:999px;"
                              :style="`background:${(TIPO_LABEL[c.tipo] ?? TIPO_LABEL.tecnico).bg};color:${(TIPO_LABEL[c.tipo] ?? TIPO_LABEL.tecnico).color};`">
                            {{ (TIPO_LABEL[c.tipo] ?? TIPO_LABEL.tecnico).label }}
                        </span>
                        <h2 style="font-size:1.15rem;font-weight:700;color:#f1f5f9;margin-top:0.75rem;line-height:1.3;">{{ c.nombre }}</h2>
                        <p v-if="c.descripcion" style="font-size:0.85rem;color:#64748b;line-height:1.6;margin-top:0.4rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                            {{ c.descripcion }}
                        </p>
                    </div>

                    <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
                        <div>
                            <p style="font-size:0.68rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#475569;margin-bottom:0.15rem;">Duración</p>
                            <p style="font-size:0.9rem;font-weight:600;color:#cbd5e1;">{{ c.duracion_niveles }} niveles</p>
                        </div>
                        <div>
                            <p style="font-size:0.68rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#475569;margin-bottom:0.15rem;">Inversión total</p>
                            <p style="font-size:0.9rem;font-weight:700;color:#38bdf8;">Bs. {{ formatBs(c.costo_carrera_completa) }}</p>
                        </div>
                    </div>

                    <div style="border-top:1px solid rgba(255,255,255,0.07);padding-top:1rem;display:flex;justify-content:space-between;align-items:center;">
                        <Link :href="route('oferta.show', c.id_carrera)"
                              style="font-size:0.82rem;font-weight:600;color:#38bdf8;text-decoration:none;transition:opacity 0.15s;"
                              onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                            Ver malla curricular →
                        </Link>
                        <Link :href="route('oferta.formulario', c.id_carrera)"
                              style="font-size:0.8rem;font-weight:600;color:#0f172a;background:linear-gradient(135deg,#38bdf8,#818cf8);padding:0.45rem 1rem;border-radius:0.5rem;text-decoration:none;transition:opacity 0.15s;"
                              onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                            Inscribirme
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
