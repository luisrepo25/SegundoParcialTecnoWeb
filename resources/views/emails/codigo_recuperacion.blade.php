<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:sans-serif;">
    <div style="max-width:480px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
        <div style="background:linear-gradient(135deg,#0f172a,#1d4ed8);padding:32px 32px 24px;">
            <p style="margin:0;color:#93c5fd;font-size:12px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;">Instituto San Pablo del Oriente</p>
            <h1 style="margin:8px 0 0;color:#f8fafc;font-size:22px;font-weight:800;">Recuperar contraseña</h1>
        </div>
        <div style="padding:32px;">
            <p style="color:#475569;margin:0 0 8px;">Hola <strong>{{ $nombre }}</strong>,</p>
            <p style="color:#475569;margin:0 0 24px;">Recibimos una solicitud para restablecer tu contraseña. Usá el siguiente código en la plataforma:</p>

            <div style="background:#f8fafc;border:2px dashed #cbd5e1;border-radius:12px;padding:24px;text-align:center;margin-bottom:24px;">
                <p style="margin:0 0 4px;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.1em;">Tu código de verificación</p>
                <p style="margin:0;font-size:40px;font-weight:900;letter-spacing:14px;color:#0f172a;">{{ $codigo }}</p>
            </div>

            <p style="color:#64748b;font-size:13px;margin:0 0 8px;">⏱ Este código expira en <strong>15 minutos</strong>.</p>
            <p style="color:#64748b;font-size:13px;margin:0;">Si no solicitaste esto, podés ignorar este correo.</p>
        </div>
        <div style="background:#f8fafc;padding:16px 32px;border-top:1px solid #e2e8f0;">
            <p style="margin:0;color:#94a3b8;font-size:12px;">Instituto San Pablo del Oriente — Sistema de Gestión Académica</p>
        </div>
    </div>
</body>
</html>
