<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * BitacoraService
 *
 * Servicio centralizado para registrar eventos en la tabla seguimiento_log.
 * Usar mediante la helper global: bitacora($accion, $descripcion, $idUsuario)
 */
class BitacoraService
{
    /**
     * Registra un evento en seguimiento_log.
     *
     * @param  string   $accion      Etiqueta corta de la acción (ej: 'LOGIN', 'CREAR_USUARIO')
     * @param  string   $descripcion Detalle legible del evento
     * @param  int|null $idUsuario   ID del usuario que realizó la acción (null = usuario actual)
     */
    public static function registrar(string $accion, string $descripcion, ?int $idUsuario = null): void
    {
        try {
            $uid = $idUsuario ?? Auth::id() ?? null;

            DB::table('seguimiento_log')->insert([
                'id_usuario'  => $uid ?: null,
                'accion'      => strtoupper($accion),
                'descripcion' => $descripcion,
                'ip_origen'   => Request::ip(),
                'fecha_hora'  => now(),
            ]);
        } catch (\Throwable) {
            // No interrumpir el flujo si el log falla
        }
    }
}
