<?php

namespace App\Http\Controllers\Profesor\CU12Evaluaciones;

use App\Http\Controllers\Controller;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EvaluacionController extends Controller
{
    const PORCENTAJES = [
        'parcial1' => 25,
        'parcial2' => 25,
        'final'    => 40,
        'otros'    => 10,
    ];

    public function store(Request $request)
    {
        DB::reconnect();
        $request->validate([
            'id_inscripcion'              => 'required|integer|exists:inscripciones,id_inscripcion',
            'evaluaciones'                => 'required|array|min:1',
            'evaluaciones.*.tipo'         => 'required|in:parcial1,parcial2,final,otros',
            'evaluaciones.*.calificacion' => 'nullable|numeric|min:0|max:100',
            'evaluaciones.*.descripcion'  => 'nullable|string|max:150',
            'evaluaciones.*.fecha'        => 'nullable|date',
        ], [
            'evaluaciones.*.calificacion.numeric' => 'La nota debe ser un número (ej: 75 o 85.5).',
            'evaluaciones.*.calificacion.min'     => 'La nota debe ser mayor o igual a 0.',
            'evaluaciones.*.calificacion.max'     => 'La nota no puede superar 100.',
        ]);

        $profesor = $this->resolverProfesor(inscripcionId: $request->id_inscripcion);
        $this->verificarActaAbierta($request->id_inscripcion);

        foreach ($request->evaluaciones as $eval) {
            $isEmpty = ($eval['calificacion'] === null || $eval['calificacion'] === '');

            $existing = DB::table('evaluaciones')
                ->where('id_inscripcion', $request->id_inscripcion)
                ->where('tipo', $eval['tipo'])
                ->first();

            // Campo vacío → eliminar evaluación existente
            if ($isEmpty) {
                if ($existing) {
                    DB::table('evaluaciones')->where('id_evaluacion', $existing->id_evaluacion)->delete();
                }
                continue;
            }

            $porcentaje = self::PORCENTAJES[$eval['tipo']];

            $data = [
                'calificacion' => $eval['calificacion'],
                'porcentaje'   => $porcentaje,
                'fecha'        => $eval['fecha'] ?? now()->toDateString(),
            ];

            if ($eval['tipo'] === 'otros' && !empty($eval['descripcion'])) {
                $data['descripcion'] = $eval['descripcion'];
            }

            if ($existing) {
                DB::table('evaluaciones')
                    ->where('id_evaluacion', $existing->id_evaluacion)
                    ->update($data);
            } else {
                DB::table('evaluaciones')->insert(array_merge($data, [
                    'id_inscripcion' => $request->id_inscripcion,
                    'id_profesor'    => $profesor->id_profesor,
                    'tipo'           => $eval['tipo'],
                ]));
            }
        }

        $this->recalcularFinal($request->id_inscripcion);

        BitacoraService::registrar('evaluacion', "Evaluación cargada para inscripción #{$request->id_inscripcion}");
        return back()->with('success', 'Calificaciones guardadas.');
    }

    public function storeMasivo(Request $request)
    {
        DB::reconnect();
        $request->validate([
            'id_oferta'                               => 'required|integer',
            'descripcion_extra'                       => 'nullable|string|max:150',
            'notas'                                   => 'required|array',
            'notas.*.id_inscripcion'                  => 'required|integer|exists:inscripciones,id_inscripcion',
            'notas.*.evaluaciones'                    => 'required|array',
            'notas.*.evaluaciones.*.tipo'             => 'required|in:parcial1,parcial2,final,otros',
            'notas.*.evaluaciones.*.calificacion'     => 'nullable|numeric|min:0|max:100',
            'notas.*.evaluaciones.*.fecha'            => 'nullable|date',
        ], [
            'notas.*.evaluaciones.*.calificacion.numeric' => 'La nota debe ser un número (ej: 75 o 85.5).',
            'notas.*.evaluaciones.*.calificacion.min'     => 'La nota debe ser mayor o igual a 0.',
            'notas.*.evaluaciones.*.calificacion.max'     => 'La nota no puede superar 100.',
        ]);

        $profesor = $this->resolverProfesor(ofertaId: $request->id_oferta);

        $cronograma = DB::table('cronogramas')
            ->where('id_periodo', $profesor->id_periodo_grupo)
            ->where('tipo_periodo', 'clases')
            ->first();

        if ($cronograma && $cronograma->fecha_fin < now()->toDateString()) {
            abort(403, 'El acta de notas está cerrada (período de clases finalizado).');
        }

        $descripcionExtra = $request->descripcion_extra ?? 'Nota Extra';

        // Todos los id_oferta del mismo grupo (multi-horario: un id_oferta por día).
        $grupoBase = DB::table('grupos')->where('id_oferta', $request->id_oferta)->first();
        if (!$grupoBase) abort(404, 'Grupo no encontrado.');
        $todosLosIdOfertas = DB::table('grupos')
            ->where('codigo_grupo', $grupoBase->codigo_grupo)
            ->where('id_periodo',   $grupoBase->id_periodo)
            ->pluck('id_oferta');

        // Validar que todas las inscripciones pertenecen a alguna oferta de este grupo
        $idsInscripcion = array_column($request->notas, 'id_inscripcion');
        $pertenecen = DB::table('inscripciones')
            ->whereIn('id_inscripcion', $idsInscripcion)
            ->whereIn('id_oferta', $todosLosIdOfertas)
            ->count();
        if ($pertenecen !== count($idsInscripcion)) {
            abort(403, 'Una o más inscripciones no pertenecen a este grupo.');
        }

        foreach ($request->notas as $nota) {
            foreach ($nota['evaluaciones'] as $eval) {
                if ($eval['calificacion'] === null || $eval['calificacion'] === '') continue;

                $porcentaje = self::PORCENTAJES[$eval['tipo']];

                $data = [
                    'calificacion' => $eval['calificacion'],
                    'porcentaje'   => $porcentaje,
                    'fecha'        => $eval['fecha'] ?? now()->toDateString(),
                ];

                if ($eval['tipo'] === 'otros') {
                    $data['descripcion'] = $descripcionExtra;
                }

                $existing = DB::table('evaluaciones')
                    ->where('id_inscripcion', $nota['id_inscripcion'])
                    ->where('tipo', $eval['tipo'])
                    ->first();

                if ($existing) {
                    DB::table('evaluaciones')
                        ->where('id_evaluacion', $existing->id_evaluacion)
                        ->update($data);
                } else {
                    DB::table('evaluaciones')->insert(array_merge($data, [
                        'id_inscripcion' => $nota['id_inscripcion'],
                        'id_profesor'    => $profesor->id_profesor,
                        'tipo'           => $eval['tipo'],
                    ]));
                }
            }

            $this->recalcularFinal($nota['id_inscripcion']);
        }

        BitacoraService::registrar('nota_masiva', "Carga masiva de notas para grupo #{$request->id_oferta} (" . count($request->notas) . " estudiantes)");
        return back()->with('success', 'Notas guardadas para todo el grupo.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Si el usuario autenticado es profesor, devuelve su registro.
     * Si es secretaria, deriva el profesor del grupo asociado a la inscripción u oferta.
     */
    private function resolverProfesor(?int $inscripcionId = null, ?int $ofertaId = null): object
    {
        $profesor = DB::table('profesores')
            ->where('id_usuario', Auth::user()->id_usuario)
            ->first();

        if ($profesor) {
            // Profesor autenticado: verificar que el grupo le pertenece
            if ($inscripcionId) {
                $this->autorizarInscripcion($inscripcionId, $profesor->id_profesor);
            }
            if ($ofertaId) {
                $grupo = DB::table('grupos')
                    ->where('id_oferta', $ofertaId)
                    ->where('id_profesor', $profesor->id_profesor)
                    ->first();
                if (!$grupo) abort(403, 'No tenés permiso sobre este grupo.');
                $profesor->id_periodo_grupo = $grupo->id_periodo;
            }
            return $profesor;
        }

        // Secretaria: derivar profesor del grupo
        if ($inscripcionId) {
            $fila = DB::table('inscripciones')
                ->join('grupos', 'inscripciones.id_oferta', '=', 'grupos.id_oferta')
                ->where('inscripciones.id_inscripcion', $inscripcionId)
                ->select('grupos.id_profesor', 'grupos.id_periodo')
                ->first();
            if (!$fila) abort(404);
            $ofertaId = $ofertaId ?? null;
            $profesor = DB::table('profesores')->where('id_profesor', $fila->id_profesor)->first();
            if (!$profesor) abort(403, 'El grupo no tiene profesor asignado.');
            $profesor->id_periodo_grupo = $fila->id_periodo;
            return $profesor;
        }

        if ($ofertaId) {
            $grupo = DB::table('grupos')->where('id_oferta', $ofertaId)->first();
            if (!$grupo) abort(404);
            $profesor = DB::table('profesores')->where('id_profesor', $grupo->id_profesor)->first();
            if (!$profesor) abort(403, 'El grupo no tiene profesor asignado.');
            $profesor->id_periodo_grupo = $grupo->id_periodo;
            return $profesor;
        }

        abort(403, 'No se pudo determinar el profesor.');
    }

    private function autorizarInscripcion(int $idInscripcion, int $idProfesor): void
    {
        $ok = DB::table('inscripciones')
            ->join('grupos', 'inscripciones.id_oferta', '=', 'grupos.id_oferta')
            ->where('inscripciones.id_inscripcion', $idInscripcion)
            ->where('grupos.id_profesor', $idProfesor)
            ->exists();

        if (!$ok) {
            abort(403, 'No tenés permiso para calificar esta inscripción.');
        }
    }

    private function verificarActaAbierta(int $idInscripcion): void
    {
        $resultado = DB::table('inscripciones')
            ->join('grupos', 'inscripciones.id_oferta', '=', 'grupos.id_oferta')
            ->join('cronogramas', function ($join) {
                $join->on('cronogramas.id_periodo', '=', 'grupos.id_periodo')
                     ->where('cronogramas.tipo_periodo', '=', 'clases');
            })
            ->where('inscripciones.id_inscripcion', $idInscripcion)
            ->select('cronogramas.fecha_fin')
            ->first();

        if ($resultado && $resultado->fecha_fin < now()->toDateString()) {
            abort(403, 'El acta de notas está cerrada (período de clases finalizado).');
        }
    }

    private function recalcularFinal(int $idInscripcion): void
    {
        $evals = DB::table('evaluaciones')
            ->where('id_inscripcion', $idInscripcion)
            ->get();

        if ($evals->isEmpty()) {
            DB::table('inscripciones')
                ->where('id_inscripcion', $idInscripcion)
                ->update(['calificacion_final' => null, 'aprobado' => false]);
            return;
        }

        // Divide sobre 100 (peso total), no sobre los pesos existentes.
        // Así el final acumula: parcial1=100 → 25.00; parcial1+parcial2=100+80 → 45.00.
        $notaFinal = round($evals->sum(fn($e) => $e->calificacion * $e->porcentaje) / 100, 2);

        DB::table('inscripciones')
            ->where('id_inscripcion', $idInscripcion)
            ->update([
                'calificacion_final' => $notaFinal,
                'aprobado'           => $notaFinal >= 51,
            ]);
    }
}
