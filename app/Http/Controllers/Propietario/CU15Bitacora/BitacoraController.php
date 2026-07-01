<?php

namespace App\Http\Controllers\Propietario\CU15Bitacora;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BitacoraController extends Controller
{
    // ── CU15.1 — Listado paginado del log de auditoría ────────────────────────
    public function index(Request $request)
    {
        $filtros = [
            'buscar'      => $request->input('buscar', ''),
            'accion'      => $request->input('accion', ''),
            'fecha_desde' => $request->input('fecha_desde', ''),
            'fecha_hasta' => $request->input('fecha_hasta', ''),
        ];

        // Acciones únicas disponibles para el selector
        $acciones = DB::table('seguimiento_log')
            ->select('accion')
            ->distinct()
            ->orderBy('accion')
            ->pluck('accion');

        $query = DB::table('seguimiento_log as sl')
            ->leftJoin('usuarios as u', 'sl.id_usuario', '=', 'u.id_usuario')
            ->select(
                'sl.id_log',
                'sl.accion',
                'sl.descripcion',
                'sl.ip_origen',
                'sl.fecha_hora',
                DB::raw("COALESCE(u.nombre || ' ' || u.apellido, '(Desconocido)') AS usuario_nombre"),
                'u.nombre   AS usuario_nombre_solo',
                'u.apellido AS usuario_apellido',
                'u.foto_perfil',
                'u.id_usuario',
                'u.id_rol'
            );

        // Filtro: búsqueda libre por usuario, acción o descripción
        if ($filtros['buscar']) {
            $buscar = $filtros['buscar'];
            $query->where(function ($q) use ($buscar) {
                $q->where('u.nombre',       'ilike', "%{$buscar}%")
                  ->orWhere('u.apellido',   'ilike', "%{$buscar}%")
                  ->orWhere('sl.accion',    'ilike', "%{$buscar}%")
                  ->orWhere('sl.descripcion','ilike', "%{$buscar}%")
                  ->orWhere('sl.ip_origen', 'ilike', "%{$buscar}%");
            });
        }

        // Filtro: acción exacta
        if ($filtros['accion']) {
            $query->where('sl.accion', $filtros['accion']);
        }

        // Filtro: rango de fechas
        if ($filtros['fecha_desde']) {
            $query->whereDate('sl.fecha_hora', '>=', $filtros['fecha_desde']);
        }
        if ($filtros['fecha_hasta']) {
            $query->whereDate('sl.fecha_hora', '<=', $filtros['fecha_hasta']);
        }

        $logs = $query
            ->orderByDesc('sl.fecha_hora')
            ->paginate(25)
            ->withQueryString();

        $recursosAccedidos = DB::table('page_views')
            ->orderByDesc('visitas')
            ->limit(10)
            ->get(['pagina', 'visitas']);

        return Inertia::render('Propietario/CU15Bitacora/Index', [
            'logs'              => $logs,
            'acciones'          => $acciones,
            'filtros'           => $filtros,
            'recursosAccedidos' => $recursosAccedidos,
        ]);
    }
}
