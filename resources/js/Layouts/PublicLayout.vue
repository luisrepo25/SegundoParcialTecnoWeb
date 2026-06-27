<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div style="min-height:100vh;background:#030712;color:#f1f5f9;font-family:inherit;">
        <!-- Topbar -->
        <header style="border-bottom:1px solid rgba(255,255,255,0.07);background:rgba(3,7,18,0.9);backdrop-filter:blur(12px);position:sticky;top:0;z-index:50;">
            <div style="max-width:72rem;margin:0 auto;padding:0 1.5rem;height:3.5rem;display:flex;align-items:center;justify-content:space-between;">
                <Link :href="route('oferta.index')" style="display:flex;align-items:center;gap:0.6rem;text-decoration:none;">
                    <span style="width:2rem;height:2rem;border-radius:0.5rem;background:linear-gradient(135deg,#38bdf8,#818cf8);display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:800;color:#fff;">S</span>
                    <span style="font-size:0.9rem;font-weight:700;color:#f1f5f9;letter-spacing:-0.01em;">Instituto San Pablo</span>
                </Link>
                <Link :href="route('login')" style="font-size:0.82rem;color:#94a3b8;text-decoration:none;transition:color 0.15s;" onmouseover="this.style.color='#f1f5f9'" onmouseout="this.style.color='#94a3b8'">
                    Iniciar sesión →
                </Link>
            </div>
        </header>

        <!-- Contenido -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer style="border-top:1px solid rgba(255,255,255,0.06);padding:2rem 1.5rem;text-align:center;">
            <p style="font-size:0.78rem;color:#475569;">
                © {{ new Date().getFullYear() }} Instituto San Pablo del Oriente — Sistema de Gestión Académica
            </p>
        </footer>
    </div>
</template>
