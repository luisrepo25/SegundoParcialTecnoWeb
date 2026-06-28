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

        <!-- Header de la carrera -->
        <section style="padding:3.5rem 1.5rem 2.5rem;background:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(129,140,248,0.09) 0%,transparent 70%);">
            <div style="max-width:56rem;margin:0 auto;">
                <Link :href="route('oferta.index')"
                      style="font-size:0.8rem;color:#64748b;text-decoration:none;display:inline-flex;align-items:center;gap:0.3rem;margin-bottom:1.5rem;transition:color 0.15s;"
                      onmouseover="this.style.color='#94a3b8'" onmouseout="this.style.color='#64748b'">
                    ← Volver a carreras
                </Link>

                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:2rem;flex-wrap:wrap;">
                    <div style="flex:1;min-width:16rem;">
                        <span style="font-size:0.7rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#818cf8;padding:0.2rem 0.6rem;background:rgba(129,140,248,0.12);border-radius:999px;">
                            {{ TIPO_LABEL[carrera.tipo] ?? carrera.tipo }}
                        </span>
                        <h1 style="font-size:clamp(1.6rem,3.5vw,2.4rem);font-weight:800;color:#f1f5f9;letter-spacing:-0.03em;margin-top:0.75rem;line-height:1.2;">
                            {{ carrera.nombre }}
                        </h1>
                        <p v-if="carrera.descripcion" style="margin-top:0.75rem;font-size:0.95rem;color:#64748b;line-height:1.7;max-width:40rem;">
                            {{ carrera.descripcion }}
                        </p>
                    </div>

                    <!-- Card de precios -->
                    <div style="border-radius:1rem;border:1px solid rgba(56,189,248,0.2);background:rgba(15,23,42,0.8);padding:1.5rem;min-width:16rem;">
                        <p style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#475569;margin-bottom:1rem;">Inversión</p>
                        <div style="margin-bottom:0.75rem;">
                            <p style="font-size:0.75rem;color:#64748b;margin-bottom:0.15rem;">Matrícula</p>
                            <p style="font-size:1.3rem;font-weight:700;color:#38bdf8;">Bs. 500</p>
                        </div>
                        <div style="border-top:1px solid rgba(255,255,255,0.07);padding-top:0.75rem;margin-bottom:0.75rem;">
                            <p style="font-size:0.75rem;color:#64748b;margin-bottom:0.15rem;">Carrera completa</p>
                            <p style="font-size:1.3rem;font-weight:700;color:#f1f5f9;">Bs. {{ formatBs(carrera.costo_carrera_completa) }}</p>
                            <p style="font-size:0.72rem;color:#64748b;margin-top:0.2rem;">Desde 30% · Contado con 20% off</p>
                        </div>
                        <div style="border-top:1px solid rgba(255,255,255,0.07);padding-top:0.75rem;">
                            <p style="font-size:0.75rem;color:#64748b;margin-bottom:0.15rem;">Duración</p>
                            <p style="font-size:0.95rem;font-weight:600;color:#cbd5e1;">{{ carrera.duracion_niveles }} niveles</p>
                        </div>
                        <Link :href="route('oferta.formulario', carrera.id_carrera)"
                              style="display:block;margin-top:1.25rem;text-align:center;font-size:0.88rem;font-weight:700;color:#0f172a;background:linear-gradient(135deg,#38bdf8,#818cf8);padding:0.65rem;border-radius:0.6rem;text-decoration:none;transition:opacity 0.15s;"
                              onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                            Inscribirme en esta carrera →
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Malla curricular -->
        <section style="max-width:56rem;margin:0 auto;padding:2rem 1.5rem 5rem;">
            <h2 style="font-size:1.05rem;font-weight:700;color:#94a3b8;letter-spacing:0.04em;text-transform:uppercase;font-size:0.75rem;margin-bottom:1.5rem;">Malla Curricular</h2>

            <!-- Sin malla -->
            <div v-if="!tieneMalla" style="border-radius:0.75rem;border:1px dashed rgba(255,255,255,0.1);padding:2.5rem;text-align:center;color:#475569;">
                La malla curricular de esta carrera estará disponible próximamente.
            </div>

            <!-- Niveles -->
            <div v-else style="display:flex;flex-direction:column;gap:0.75rem;">
                <div v-for="(nivel, i) in malla" :key="nivel.id_nivel"
                     style="border-radius:0.75rem;border:1px solid rgba(255,255,255,0.08);overflow:hidden;">

                    <!-- Header del nivel (click para abrir) -->
                    <button
                        type="button"
                        @click="toggle(i)"
                        style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;background:rgba(15,23,42,0.7);border:none;cursor:pointer;text-align:left;transition:background 0.15s;"
                        onmouseover="this.style.background='rgba(15,23,42,0.95)'"
                        onmouseout="this.style.background='rgba(15,23,42,0.7)'">
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <span style="width:1.6rem;height:1.6rem;border-radius:0.4rem;background:linear-gradient(135deg,#38bdf8,#818cf8);display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:800;color:#0f172a;flex-shrink:0;">
                                {{ nivel.numero_nivel }}
                            </span>
                            <span style="font-size:0.95rem;font-weight:600;color:#f1f5f9;">
                                {{ nivel.nombre || 'Nivel ' + nivel.numero_nivel }}
                            </span>
                            <span style="font-size:0.72rem;color:#475569;">{{ nivel.materias.length }} materias</span>
                        </div>
                        <span style="color:#475569;font-size:0.85rem;transition:transform 0.2s;" :style="abiertos[i] ? 'transform:rotate(180deg)' : ''">▼</span>
                    </button>

                    <!-- Materias -->
                    <div v-if="abiertos[i]" style="border-top:1px solid rgba(255,255,255,0.06);">
                        <div v-if="!nivel.materias.length" style="padding:1rem 1.25rem;font-size:0.85rem;color:#475569;">
                            Sin materias registradas en este nivel.
                        </div>
                        <div v-for="(m, mi) in nivel.materias" :key="mi"
                             style="display:flex;align-items:center;justify-content:space-between;padding:0.7rem 1.25rem;border-top:1px solid rgba(255,255,255,0.04);">
                            <div style="display:flex;align-items:center;gap:0.75rem;">
                                <span style="font-size:0.7rem;font-weight:700;color:#475569;font-family:monospace;">{{ m.codigo }}</span>
                                <span style="font-size:0.88rem;color:#cbd5e1;">{{ m.nombre }}</span>
                            </div>
                            <span v-if="!m.obligatoria"
                                  style="font-size:0.65rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;padding:0.15rem 0.5rem;border-radius:999px;background:rgba(245,158,11,0.1);color:#f59e0b;">
                                Optativa
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA al final -->
            <div v-if="tieneMalla" style="margin-top:2.5rem;text-align:center;">
                <Link :href="route('oferta.formulario', carrera.id_carrera)"
                      style="display:inline-flex;align-items:center;gap:0.5rem;font-size:0.95rem;font-weight:700;color:#0f172a;background:linear-gradient(135deg,#38bdf8,#818cf8);padding:0.8rem 2rem;border-radius:0.75rem;text-decoration:none;transition:opacity 0.15s;"
                      onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                    Inscribirme en {{ carrera.nombre }} →
                </Link>
            </div>
        </section>
    </PublicLayout>
</template>
