<?php

namespace App\Http\Controllers\Propietario\CU14Reportes;

use App\Http\Controllers\Controller;
use App\Models\Aula;
use App\Models\Carrera;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $filtros = [
            'activo_usuarios' => $request->get('activo_usuarios', 'todos'),
            'activo_aulas'    => $request->get('activo_aulas',    'todos'),
            'activo_horarios' => $request->get('activo_horarios', 'todos'),
        ];

        // ── Usuarios por rol ──────────────────────────────────────────
        $rolNames = [1 => 'Propietario', 2 => 'Director', 3 => 'Secretaria', 4 => 'Profesor', 5 => 'Estudiante'];

        $qUsuarios = Usuario::select('id_rol', DB::raw('count(*) as total'))->groupBy('id_rol')->orderBy('id_rol');
        if ($filtros['activo_usuarios'] === '1') $qUsuarios->whereRaw('activo IS TRUE');
        if ($filtros['activo_usuarios'] === '0') $qUsuarios->whereRaw('activo IS FALSE');

        $usuariosPorRol = $qUsuarios->get()->map(fn($r) => [
            'label' => $rolNames[$r->id_rol] ?? 'Desconocido',
            'valor' => (int) $r->total,
        ])->values();

        // ── Aulas por tipo ────────────────────────────────────────────
        $qAulas = Aula::select('tipo', DB::raw('count(*) as total'))->groupBy('tipo');
        if ($filtros['activo_aulas'] === '1') $qAulas->whereRaw('activo IS TRUE');
        if ($filtros['activo_aulas'] === '0') $qAulas->whereRaw('activo IS FALSE');

        $aulasPorTipo = $qAulas->get()->map(fn($r) => [
            'label' => ucfirst($r->tipo),
            'valor' => (int) $r->total,
        ])->values();

        // ── Horarios por día ──────────────────────────────────────────
        $diasOrden = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        $diasLabel = [
            'lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles',
            'jueves' => 'Jueves', 'viernes' => 'Viernes', 'sabado' => 'Sábado', 'domingo' => 'Domingo',
        ];

        $qHorarios = Horario::select('dia_semana', DB::raw('count(*) as total'))->groupBy('dia_semana');
        if ($filtros['activo_horarios'] === '1') $qHorarios->whereRaw('activo IS TRUE');
        if ($filtros['activo_horarios'] === '0') $qHorarios->whereRaw('activo IS FALSE');

        $horarioRaw = $qHorarios->get()->keyBy('dia_semana');
        $horariosPorDia = collect($diasOrden)->map(fn($dia) => [
            'label' => $diasLabel[$dia],
            'valor' => (int) ($horarioRaw->get($dia)?->total ?? 0),
        ])->values();

        return Inertia::render('Propietario/CU14Reportes/Index', [
            'esPropietario' => auth()->user()->role === 'propietario',
            'filtros'       => $filtros,
            'administrativo' => [
                'usuariosPorRol' => $usuariosPorRol,
                'aulasPorTipo'   => $aulasPorTipo,
                'aulasActivas'   => Aula::whereRaw('activo IS TRUE')->count(),
                'aulasInactivas' => Aula::whereRaw('activo IS FALSE')->count(),
            ],
            'academico' => [
                'carrerasActivas'   => Carrera::whereRaw('activo IS TRUE')->count(),
                'carrerasInactivas' => Carrera::whereRaw('activo IS FALSE')->count(),
                'materiasActivas'   => Materia::whereRaw('activo IS TRUE')->count(),
                'materiasInactivas' => Materia::whereRaw('activo IS FALSE')->count(),
                'horariosPorDia'    => $horariosPorDia,
            ],
        ]);
    }
}
