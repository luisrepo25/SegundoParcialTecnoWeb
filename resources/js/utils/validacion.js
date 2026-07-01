// ── Patrones ──────────────────────────────────────────────────────────────────
export const SOLO_LETRAS  = /^[\p{L}\s'\-]+$/u;
export const SOLO_DIGITOS = /^[0-9]+$/;
export const TEL_PAT      = /^[0-9+\-\s()]+$/;
export const EMAIL_PAT    = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
export const CODIGO_PAT   = /^[a-zA-Z0-9\-_]+$/;
export const HORA_PAT     = /^([01][0-9]|2[0-3]):[0-5][0-9]$/;

// ── Campos de persona ─────────────────────────────────────────────────────────
export function errNombre(v, label = 'El nombre') {
    const val = (v ?? '').trim();
    if (!val) return `${label} es obligatorio.`;
    if (!SOLO_LETRAS.test(val)) return `${label} no debe contener números ni símbolos.`;
    return null;
}

export function errDni(v) {
    const val = (v ?? '').trim();
    if (!val) return 'El CI/DNI es obligatorio.';
    if (!SOLO_DIGITOS.test(val)) return 'El CI/DNI solo debe contener números.';
    return null;
}

export function errEmail(v, requerido = true) {
    const val = (v ?? '').trim();
    if (!val) return requerido ? 'El email es obligatorio.' : null;
    if (!EMAIL_PAT.test(val)) return 'Ingrese un email válido (ej: usuario@dominio.com).';
    return null;
}

export function errTelefono(v) {
    const val = (v ?? '').trim();
    if (!val) return null;
    if (!TEL_PAT.test(val)) return 'El teléfono solo debe contener números y símbolos (+, -)';
    return null;
}

// ── Campos numéricos ──────────────────────────────────────────────────────────
export function errEntero(v, label = 'El campo', min = 1, max = null) {
    const val = String(v ?? '').trim();
    if (!val) return `${label} es obligatorio.`;
    if (!/^[0-9]+$/.test(val)) return `${label} debe ser un número entero positivo.`;
    const n = parseInt(val, 10);
    if (n < min) return `${label} debe ser al menos ${min}.`;
    if (max !== null && n > max) return `${label} no puede superar ${max}.`;
    return null;
}

export function errDecimal(v, label = 'El campo', min = 0, max = null) {
    const val = String(v ?? '').trim();
    if (!val) return `${label} es obligatorio.`;
    if (!/^[0-9]+(\.[0-9]+)?$/.test(val)) return `${label} debe ser un número válido (ej: 500 o 500.50).`;
    const n = parseFloat(val);
    if (n < min) return `${label} debe ser mayor o igual a ${min}.`;
    if (max !== null && n > max) return `${label} no puede superar ${max}.`;
    return null;
}

export function errNota(v) {
    const val = String(v ?? '').trim();
    if (!val) return null; // notas son opcionales (pueden quedar vacías)
    if (!/^[0-9]+(\.[0-9]{1,2})?$/.test(val)) return 'La nota debe ser un número (ej: 75 o 85.5).';
    const n = parseFloat(val);
    if (n < 0 || n > 100) return 'La nota debe estar entre 0 y 100.';
    return null;
}

// ── Campos de texto general (nombres institucionales — pueden tener números) ──
export function errTexto(v, label = 'El campo') {
    const val = (v ?? '').trim();
    if (!val) return `${label} es obligatorio.`;
    return null;
}

export function errCodigo(v, label = 'El código') {
    const val = (v ?? '').trim();
    if (!val) return `${label} es obligatorio.`;
    if (!CODIGO_PAT.test(val)) return `${label} solo debe contener letras, números, guiones y guiones bajos.`;
    return null;
}

// ── Campos de fecha y hora ────────────────────────────────────────────────────
export function errFecha(v, label = 'La fecha') {
    const val = (v ?? '').trim();
    if (!val) return `${label} es obligatoria.`;
    if (!/^\d{4}-\d{2}-\d{2}$/.test(val)) return `${label} debe tener formato YYYY-MM-DD.`;
    const d = new Date(val + 'T00:00:00');
    if (isNaN(d.getTime())) return `${label} no es válida.`;
    return null;
}

export function errFechaFin(fin, inicio, labelFin = 'La fecha de fin') {
    const ef = errFecha(fin, labelFin);
    if (ef) return ef;
    if (inicio && fin && fin < inicio) return `${labelFin} no puede ser anterior a la fecha de inicio.`;
    return null;
}

export function errHora(v, label = 'La hora') {
    const val = (v ?? '').trim();
    if (!val) return `${label} es obligatoria.`;
    if (!HORA_PAT.test(val)) return `${label} debe tener formato HH:MM (ej: 08:00).`;
    return null;
}

export function errHoraFin(fin, inicio) {
    const ef = errHora(fin, 'La hora de fin');
    if (ef) return ef;
    if (inicio && fin && fin <= inicio) return 'La hora de fin debe ser posterior a la hora de inicio.';
    return null;
}
