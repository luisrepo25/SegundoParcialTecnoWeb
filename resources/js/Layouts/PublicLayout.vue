<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTheme } from '@/Composables/useTheme';

const { theme, setTheme, changeFontScale } = useTheme();

const toggleDayNight = () => setTheme(theme.value === 'youth' ? 'adults' : 'youth');
const esModoNoche    = computed(() => theme.value === 'youth');

const sitio = computed(() => usePage().props.sitio ?? {});
const logoUrl = computed(() => sitio.value.logo_url ?? '/images/logo.png');
const nombre  = computed(() => sitio.value.nombre_institucion ?? 'Instituto San Pablo');

const navActive = (names) => names.some(n => route().current(n));
</script>

<template>
    <div class="pub-wrap">

        <!-- ══ BARRA ACCESIBILIDAD ══ -->
        <div class="access-bar">
            <div class="age-group">
                <button :class="['age-btn', theme === 'adults' ? 'active' : '']"
                        @click="setTheme('adults')">Adultos</button>
                <button :class="['age-btn', theme === 'youth'  ? 'active' : '']"
                        @click="setTheme('youth')">Jóvenes</button>
                <button :class="['age-btn', theme === 'kids'   ? 'active' : '']"
                        @click="setTheme('kids')">Niños</button>
            </div>
            <div class="acc-sep"></div>
            <div class="font-group">
                <button class="font-btn" @click="changeFontScale('decrease')">A−</button>
                <button class="font-btn" @click="changeFontScale('reset')">A</button>
                <button class="font-btn" @click="changeFontScale('increase')">A+</button>
            </div>
            <div class="acc-sep"></div>
            <button class="day-night-btn" @click="toggleDayNight" :title="esModoNoche ? 'Modo día' : 'Modo noche'">
                <svg v-if="esModoNoche" xmlns="http://www.w3.org/2000/svg" class="day-night-icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="day-night-icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                </svg>
            </button>
        </div>

        <!-- ══ NAVBAR ══ -->
        <nav class="navbar">
            <Link :href="route('oferta.index')" class="logo">
                <div class="logo-shield">
                    <img :src="logoUrl" :alt="'Escudo ' + nombre"
                         @error="$event.target.style.display='none'; $event.target.nextElementSibling.style.display='flex'">
                    <div class="logo-shield-fallback">{{ nombre.charAt(0) }}</div>
                </div>
                <div class="logo-divider"></div>
                <div class="logo-texts">
                    <div class="logo-label">Instituto Técnico</div>
                    <div class="logo-name">{{ nombre }}</div>
                    <div class="logo-tagline">del Oriente · Bolivia</div>
                </div>
            </Link>

            <div class="nav-links">
                <Link :href="route('oferta.index')" :class="['nav-link', navActive(['oferta.index']) ? 'active' : '']">Inicio</Link>
                <Link :href="route('oferta.carreras')" :class="['nav-link', navActive(['oferta.carreras']) ? 'active' : '']">Carreras</Link>
                <Link :href="route('oferta.malla')" :class="['nav-link', navActive(['oferta.malla', 'oferta.show']) ? 'active' : '']">Malla Curricular</Link>
            </div>

            <Link :href="route('login')" class="nav-login">Iniciar sesión →</Link>
        </nav>

        <!-- ══ CONTENIDO ══ -->
        <main>
            <slot />
        </main>

        <!-- ══ FOOTER ══ -->
        <footer class="footer">
            <div class="footer-grid">
                <div>
                    <Link :href="route('oferta.index')" class="footer-logo-link">
                        <img :src="logoUrl" :alt="nombre" class="footer-logo-img"
                             @error="$event.target.style.display='none'">
                        <span class="footer-logo-name">{{ nombre }}</span>
                    </Link>
                    <p class="footer-desc">{{ sitio.descripcion ?? 'Formación técnica y superior para el mercado laboral del oriente boliviano.' }}</p>
                </div>
                <div>
                    <p class="footer-col-title">Contacto</p>
                    <div class="footer-links">
                        <a v-if="sitio.email" :href="'mailto:' + sitio.email" class="footer-link footer-link-a">✉️ {{ sitio.email }}</a>
                        <a v-if="sitio.telefono_1" :href="'tel:' + sitio.telefono_1" class="footer-link footer-link-a">📞 {{ sitio.telefono_1 }}</a>
                        <a v-if="sitio.telefono_2" :href="'tel:' + sitio.telefono_2" class="footer-link footer-link-a">📞 {{ sitio.telefono_2 }}</a>
                        <span v-if="sitio.direccion" class="footer-link">📍 {{ sitio.direccion }}</span>
                    </div>
                </div>
                <div>
                    <p class="footer-col-title">Acceso rápido</p>
                    <div class="footer-links">
                        <Link :href="route('oferta.carreras')" class="footer-link footer-link-a">→ Todas las carreras</Link>
                        <Link :href="route('oferta.malla')" class="footer-link footer-link-a">→ Malla curricular</Link>
                        <Link :href="route('login')" class="footer-link footer-link-a">→ Iniciar sesión</Link>
                    </div>
                </div>
                <div v-if="sitio.facebook_url || sitio.instagram_url || sitio.youtube_url">
                    <p class="footer-col-title">Síguenos</p>
                    <div class="footer-links">
                        <a v-if="sitio.facebook_url" :href="sitio.facebook_url" target="_blank" rel="noopener" class="footer-link social-link social-fb">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                            Facebook
                        </a>
                        <a v-if="sitio.instagram_url" :href="sitio.instagram_url" target="_blank" rel="noopener" class="footer-link social-link social-ig">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                            Instagram
                        </a>
                        <a v-if="sitio.youtube_url" :href="sitio.youtube_url" target="_blank" rel="noopener" class="footer-link social-link social-yt">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 00-1.95-1.97C18.88 4 12 4 12 4s-6.88 0-8.59.45A2.78 2.78 0 001.46 6.42 29 29 0 001 12a29 29 0 00.46 5.58A2.78 2.78 0 003.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.45a2.78 2.78 0 001.95-1.95A29 29 0 0023 12a29 29 0 00-.46-5.58zM9.75 15.5v-7l6.5 3.5-6.5 3.5z"/></svg>
                            YouTube
                        </a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <span class="footer-cr">© {{ new Date().getFullYear() }} Instituto San Pablo del Oriente — Sistema de Gestión Académica</span>
                <span class="footer-cr" style="font-style:italic;">Todos los derechos reservados</span>
                <span class="footer-cr">
                    Visitas a esta página:
                    <strong style="color: var(--primary-color);">{{ ($page.props.visitas_pagina ?? 0).toLocaleString('es-BO') }}</strong>
                </span>
            </div>
        </footer>
    </div>
</template>

<style>
/* ── Base ── */
.pub-wrap {
    min-height: 100vh;
    background: var(--bg-color);
    color: var(--text-color);
    font-family: var(--font-family);
    font-size: calc(14px * var(--font-scale, 1));
    transition: background 0.3s, color 0.3s;
}

/* ── Access Bar ── */
.access-bar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 0.3rem 1.5rem;
    background: var(--nav-bg);
    border-bottom: 1px solid var(--border-color);
    flex-wrap: wrap;
}
.acc-sep {
    width: 1px; height: 1rem;
    background: var(--border-color);
}
.age-group {
    display: flex; align-items: center; gap: 0.15rem;
    padding: 0.12rem;
    border-radius: 0.5rem;
    background: var(--bg-color);
    border: 1px solid var(--border-color);
}
.age-btn {
    font-size: 0.63rem; font-weight: 600;
    padding: 0.2rem 0.6rem; border-radius: 0.35rem;
    border: none; background: transparent;
    color: var(--text-secondary);
    cursor: pointer; transition: all 0.15s;
    font-family: var(--font-family);
}
.age-btn.active {
    background: var(--primary-color);
    color: var(--primary-text);
    font-weight: 700;
}
.font-group {
    display: flex;
    border: 1px solid var(--border-color);
    border-radius: 0.4rem;
    overflow: hidden;
    background: var(--bg-color);
}
.font-btn {
    font-size: 0.7rem; font-weight: 700;
    padding: 0.28rem 0.58rem;
    border: none; background: transparent;
    color: var(--text-secondary);
    cursor: pointer;
    border-right: 1px solid var(--border-color);
    font-family: var(--font-family);
    transition: background 0.15s, color 0.15s;
}
.font-btn:last-child { border-right: none; }
.font-btn:hover { background: color-mix(in srgb, var(--primary-color) 10%, transparent); color: var(--primary-color); }
.day-night-btn {
    width: 1.9rem; height: 1.9rem; border-radius: 50%;
    border: 1px solid var(--border-color);
    background: var(--bg-color);
    color: var(--text-secondary);
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
}
.day-night-btn:hover { border-color: var(--primary-color); color: var(--primary-color); transform: rotate(15deg); }
.day-night-icon { width: 1rem; height: 1rem; }

/* ── Navbar ── */
.navbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.5rem 1.5rem; min-height: 4.5rem;
    background: var(--nav-bg);
    border-bottom: 1px solid var(--border-color);
    position: sticky; top: 0; z-index: 50;
}
.logo {
    display: flex; align-items: center; gap: 0.85rem;
    text-decoration: none;
}
.logo-shield {
    position: relative; flex-shrink: 0;
    width: 3.4rem; height: 3.4rem;
    display: flex; align-items: center; justify-content: center;
}
.logo-shield img {
    width: 3.4rem; height: 3.4rem; object-fit: contain;
    filter: drop-shadow(0 3px 10px color-mix(in srgb, var(--primary-color) 40%, transparent));
    transition: filter 0.2s;
}
.logo:hover .logo-shield img {
    filter: drop-shadow(0 4px 16px color-mix(in srgb, var(--primary-color) 60%, transparent));
}
.logo-shield-fallback {
    display: none;
    width: 3.4rem; height: 3.4rem; border-radius: 50%;
    background: linear-gradient(145deg, var(--primary-color), var(--primary-hover));
    align-items: center; justify-content: center;
    font-size: 1.4rem; font-weight: 900; color: var(--primary-text);
    box-shadow: 0 4px 16px color-mix(in srgb, var(--primary-color) 35%, transparent);
    border: 2.5px solid color-mix(in srgb, var(--primary-color) 40%, white);
}
.logo-divider {
    width: 1.5px; height: 2.6rem; flex-shrink: 0;
    background: linear-gradient(to bottom, transparent, var(--border-color), transparent);
}
.logo-texts { line-height: 1.1; }
.logo-label {
    font-size: 0.45rem; font-weight: 700;
    letter-spacing: 0.2em; text-transform: uppercase;
    color: var(--text-secondary); margin-bottom: 0.18rem;
}
.logo-name {
    font-size: 1.15rem; font-weight: 900;
    color: var(--text-color); letter-spacing: -0.03em; line-height: 1;
}
.logo-name span { color: var(--primary-color); }
.logo-tagline {
    font-size: 0.5rem; color: var(--text-secondary);
    text-transform: uppercase; letter-spacing: 0.14em;
    font-weight: 600; margin-top: 0.2rem;
}
.nav-links { display: flex; gap: 0.25rem; }
.nav-link {
    font-size: 0.8rem; font-weight: 600;
    color: var(--text-secondary); text-decoration: none;
    padding: 0.4rem 0.9rem; border-radius: 999px;
    transition: all 0.15s;
}
.nav-link:hover { color: var(--text-color); background: color-mix(in srgb, var(--primary-color) 8%, transparent); }
.nav-link.active {
    color: var(--primary-text); background: var(--primary-color);
    font-weight: 700;
}
.nav-link.active:hover { background: var(--primary-hover); }
.nav-login {
    font-size: 0.78rem; font-weight: 600;
    padding: 0.45rem 1.1rem; border-radius: 999px;
    border: 1.5px solid var(--primary-color);
    background: transparent; color: var(--primary-color);
    text-decoration: none; transition: all 0.15s;
}
.nav-login:hover { background: var(--primary-color); color: var(--primary-text); }

/* ── Footer ── */
.footer {
    border-top: 1.5px solid var(--border-color);
    background: var(--nav-bg);
    padding: 2rem 1.5rem 1.25rem;
    margin-top: 2rem;
}
.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
    gap: 1.5rem;
    max-width: 72rem; margin: 0 auto 1.5rem;
}
.footer-logo-link {
    display: flex; align-items: center; gap: 0.6rem;
    text-decoration: none; margin-bottom: 0.6rem;
}
.footer-logo-img { width: 2rem; height: 2rem; object-fit: contain; }
.footer-logo-name { font-size: 0.82rem; font-weight: 800; color: var(--text-color); }
.footer-desc { font-size: 0.68rem; color: var(--text-secondary); line-height: 1.75; }
.footer-col-title {
    font-size: 0.58rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.1em; color: var(--text-secondary);
    opacity: 0.6; margin-bottom: 0.65rem;
}
.footer-links { display: flex; flex-direction: column; gap: 0.4rem; }
.footer-link { font-size: 0.7rem; color: var(--text-secondary); }
.footer-link-a { text-decoration: none; transition: color 0.15s; }
.footer-link-a:hover { color: var(--primary-color); }
.social-link {
    display: flex; align-items: center; gap: 0.45rem;
    text-decoration: none; transition: color 0.18s, gap 0.18s;
    cursor: pointer;
}
.social-link svg { flex-shrink: 0; }
.social-link:hover { gap: 0.65rem; }
.social-fb:hover { color: #1877f2; }
.social-ig:hover { color: #e1306c; }
.social-yt:hover { color: #ff0000; }
.footer-bottom {
    border-top: 1px solid var(--border-color);
    padding-top: 1rem; max-width: 72rem; margin: 0 auto;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 0.5rem;
}
.footer-cr { font-size: 0.65rem; color: var(--text-secondary); opacity: 0.7; }
</style>
