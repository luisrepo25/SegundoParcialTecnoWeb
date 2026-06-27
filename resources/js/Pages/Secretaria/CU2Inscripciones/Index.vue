<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Gestión de Inscripciones" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight" style="color: var(--text-color);">
                Inscripciones
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow-sm sm:rounded-lg border" style="background-color: var(--card-bg); border-color: var(--border-color);">
                    <div class="p-6" style="color: var(--text-color);">
                        Próximamente: Módulo de inscripción de alumnos a grupos.
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
