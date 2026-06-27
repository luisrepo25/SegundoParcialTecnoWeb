<script setup>
const roles = [
    {
        key: 'admin',
        code: 'AD',
        title: 'Administrador',
        description: 'Controla configuración, acceso y estructura del sistema.',
        items: ['Usuarios y permisos', 'Catálogos generales', 'Auditoría y seguridad'],
    },
    {
        key: 'director',
        code: 'DI',
        title: 'Dirección',
        description: 'Supervisa rendimiento académico y visión general del instituto.',
        items: ['Reportes académicos', 'Seguimiento institucional', 'Cohortes y avance'],
    },
    {
        key: 'secretary',
        code: 'SE',
        title: 'Secretaría',
        description: 'Gestiona matrículas, registros y atención operativa.',
        items: ['Inscripciones', 'Pagos y comprobantes', 'Registro de estudiantes'],
    },
    {
        key: 'teacher',
        code: 'DO',
        title: 'Docente',
        description: 'Administra evaluaciones, grupos y desempeño de las materias.',
        items: ['Evaluaciones', 'Asistencia y notas', 'Seguimiento del grupo'],
    },
    {
        key: 'student',
        code: 'ES',
        title: 'Estudiante',
        description: 'Consulta su progreso, pagos y estado académico.',
        items: ['Panel personal', 'Kardex y avance', 'Estado de pagos'],
    },
];
</script>

<template>
    <div class="auth-role-grid">
        <article
            v-for="role in roles"
            :key="role.key"
            :class="[`auth-role-card`, `auth-role-card--${role.key}`]"
        >
            <div class="auth-role-top">
                <div class="auth-role-badge">{{ role.code }}</div>
                <h3>{{ role.title }}</h3>
            </div>

            <p>{{ role.description }}</p>

            <ul class="auth-role-list">
                <li v-for="item in role.items" :key="item">{{ item }}</li>
            </ul>
        </article>
    </div>
</template>