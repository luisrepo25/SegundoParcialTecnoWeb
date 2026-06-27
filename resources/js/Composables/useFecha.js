const TZ = 'America/La_Paz'; // Bolivia UTC-4

export function formatFecha(val) {
    if (!val) return '—';
    return new Date(val).toLocaleDateString('es-BO', {
        timeZone: TZ,
        day:   '2-digit',
        month: '2-digit',
        year:  'numeric',
    });
}

export function formatFechaHora(val) {
    if (!val) return '—';
    return new Date(val).toLocaleString('es-BO', {
        timeZone: TZ,
        day:    '2-digit',
        month:  '2-digit',
        year:   'numeric',
        hour:   '2-digit',
        minute: '2-digit',
    });
}

export function formatHora(val) {
    if (!val) return '—';
    // val puede ser "HH:MM:SS" (time de Postgres) o timestamp completo
    if (typeof val === 'string' && val.length <= 8) {
        // es solo hora "14:30:00" — muestra directo
        return val.slice(0, 5);
    }
    return new Date(val).toLocaleTimeString('es-BO', {
        timeZone: TZ,
        hour:   '2-digit',
        minute: '2-digit',
    });
}
