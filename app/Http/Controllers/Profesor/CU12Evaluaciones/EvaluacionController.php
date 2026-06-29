<?php

namespace App\Http\Controllers\Profesor\CU12Evaluaciones;

use App\Http\Controllers\Controller;
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
        ]);

        $profesor = $this->getProfesor();
        $this->autorizarInscripcion($request->id_inscripcion, $profesor->id_profesor);
        $this->verificarActaAbierta($request->id_inscripcion);

        foreach ($request->evaluaciones as $eval) {
            if ($eval['calificacion'] === null || $eval['calificacion'] === '') continue;

            $porcentaje = self::PORCENTAJES[$eval['tipo']];

            $data = [
                'calificacion' => $eval['calificacion'],
                'porcentaje'   => $porcentaje,
                'fecha'        => $eval['fecha'] ?? now()->toDateString(),
            ];

            if ($eval['tipo'] === 'otros' && !empty($eval['descripcion'])) {
                $data['descripcion'] = $eval['descripcion'];
            }

            $existing = DB::table('evaluaciones')
                ->where('id_inscripcion', $request->id_inscripcion)
                ->where('tipo', $eval['tipo'])
                ->first();

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
        ]);

        $profesor = $this->getProfesor();

        $grupo = DB::table('grupos')
            ->where('id_oferta', $request->id_oferta)
            ->where('id_profesor', $profesor->id_profesor)
            ->first();

        if (!$grupo) {
            abort(403, 'No tenés permiso sobre este grupo.');
        }

        $cronograma = DB::table('cronogramas')
            ->where('id_periodo', $grupo->id_periodo)
            ->where('tipo_periodo', 'clases')
            ->first();

        if ($cronograma && $cronograma->fecha_fin < now()->toDateString()) {
            abort(403, 'El acta de notas está cerrada (período de clases finalizado).');
        }

        $descripcionExtra = $request->descripcion_extra ?? 'Nota Extra';

        foreach ($request->notas as $nota) {
            $this->autorizarInscripcion($nota['id_inscripcion'], $profesor->id_profesor);

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

        return back()->with('success', 'Notas guardadas para todo el grupo.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getProfesor()
    {
        $profesor = DB::table('profesores')
            ->where('id_usuario', Auth::user()->id_usuario)
            ->first();

        if (!$profesor) {
            abort(403, 'No sos profesor.');
        }

        return $profesor;
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

        if ($evals->isEmpty()) return;

        $totalPeso = $evals->sum('porcentaje');
        $nota = $totalPeso > 0
            ? $evals->sum(fn($e) => $e->calificacion * $e->porcentaje) / $totalPeso
            : $evals->avg('calificacion');

        DB::table('inscripciones')
            ->where('id_inscripcion', $idInscripcion)
            ->update(['calificacion_final' => round($nota, 2)]);
    }
}
