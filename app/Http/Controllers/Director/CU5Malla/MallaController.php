<?php

namespace App\Http\Controllers\Director\CU5Malla;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MallaController extends Controller
{
    // POST /director/carreras/{id}/niveles
    public function storeNivel(Request $request, int $idCarrera)
    {
        Carrera::findOrFail($idCarrera);

        $request->validate([
            'nombre'      => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
        ]);

        $carrera = DB::table('carreras')->where('id_carrera', $idCarrera)->first();
        if ($carrera && $carrera->tipo === 'curso_libre') {
            return redirect()->back()->withErrors(['nivel' => 'Los cursos libres no tienen niveles. Usa la oferta acadÃ©mica directamente.']);
        }

        $maxNivel = DB::table('niveles_carrera')
            ->where('id_carrera', $idCarrera)
            ->max('numero_nivel') ?? 0;

        $numero = $maxNivel + 1;

        try {
            DB::table('niveles_carrera')->insert([
                'id_carrera'   => $idCarrera,
                'numero_nivel' => $numero,
                'nombre'       => $request->nombre ?: "Nivel $numero",
                'descripcion'  => $request->descripcion,
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['nivel' => 'No se pudo agregar el nivel: ' . $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Nivel agregado.');
    }

    // DELETE /director/niveles/{id}
    public function destroyNivel(int $idNivel)
    {
        $nivel = DB::table('niveles_carrera')->where('id_nivel', $idNivel)->first();
        if (!$nivel) return redirect()->back()->withErrors(['nivel' => 'Nivel no encontrado.']);

        $tieneMaterias = DB::table('malla_curricular')->where('id_nivel', $idNivel)->exists();

        if ($tieneMaterias) {
            return redirect()->route('director.carreras.materias', $nivel->id_carrera)
                ->withErrors(['nivel' => 'Quita las materias del nivel antes de eliminarlo.']);
        }

        DB::table('niveles_carrera')->where('id_nivel', $idNivel)->delete();

        return redirect()->route('director.carreras.materias', $nivel->id_carrera)
            ->with('success', 'Nivel eliminado.');
    }

    // POST /director/malla  â€” asigna materia existente a un nivel (o a un curso libre)
    public function storeMalla(Request $request)
    {
        $esCursoLibre = $request->boolean('es_curso_libre');

        $rules = [
            'id_carrera'     => 'required|integer|exists:carreras,id_carrera',
            'id_materia'     => 'required|integer|exists:materias,id_materia',
            'obligatoria'    => 'boolean',
            'orden_en_nivel' => 'nullable|integer|min:1',
        ];
        if (!$esCursoLibre) {
            $rules['id_nivel'] = 'required|integer|exists:niveles_carrera,id_nivel';
        }
        $request->validate($rules);

        $existe = DB::table('malla_curricular')
            ->where('id_carrera', $request->id_carrera)
            ->where('id_materia', $request->id_materia)
            ->exists();

        if ($existe) {
            return redirect()->back()->withErrors(['materia' => 'Esta materia ya estÃ¡ en la malla de esta carrera.']);
        }

        // Validar que el prerequisito ya estÃ© en la malla antes de agregar esta materia
        $materia = DB::table('materias')->where('id_materia', $request->id_materia)->first();

        // Si es materia base (sin prereq), verificar que no queden cadenas pendientes en la carrera
        if (!$materia->id_materia_requisito) {
            $cadenaPendiente = DB::table('materias as m')
                ->whereNotNull('m.id_materia_requisito')
                ->where('m.activo', true)
                ->whereExists(function ($q) use ($request) {
                    $q->select(DB::raw(1))
                      ->from('malla_curricular as mc_pre')
                      ->where('mc_pre.id_carrera', $request->id_carrera)
                      ->whereColumn('mc_pre.id_materia', 'm.id_materia_requisito');
                })
                ->whereNotExists(function ($q) use ($request) {
                    $q->select(DB::raw(1))
                      ->from('malla_curricular as mc_self')
                      ->where('mc_self.id_carrera', $request->id_carrera)
                      ->whereColumn('mc_self.id_materia', 'm.id_materia');
                })
                ->exists();

            if ($cadenaPendiente) {
                return redirect()->route('director.carreras.materias', $request->id_carrera)->withErrors([
                    'materia' => 'AÃºn hay materias que continÃºan cadenas existentes sin agregar. Completa las cadenas antes de iniciar una nueva rama.',
                ]);
            }
        }

        if ($materia->id_materia_requisito) {
            $prereqEnMalla = DB::table('malla_curricular')
                ->where('id_carrera', $request->id_carrera)
                ->where('id_materia', $materia->id_materia_requisito)
                ->exists();

            if (!$prereqEnMalla) {
                $prereq = DB::table('materias')->where('id_materia', $materia->id_materia_requisito)->first();
                return redirect()->back()->withErrors([
                    'materia' => "No se puede agregar \"{$materia->nombre}\": su prerequisito \"{$prereq->nombre}\" aÃºn no estÃ¡ en la malla. AgrÃ©galo primero.",
                ]);
            }
        }

        // â”€â”€ Verificar lÃ­mite max_materias del perÃ­odo â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ($esCursoLibre) {
            $periodo = DB::table('periodos_dictado')
                ->where('id_carrera', $request->id_carrera)
                ->whereNull('id_nivel')
                ->first();
            if ($periodo && $periodo->max_materias) {
                $count = DB::table('malla_curricular')
                    ->where('id_carrera', $request->id_carrera)
                    ->whereNull('id_nivel')
                    ->count();
                if ($count >= $periodo->max_materias) {
                    return redirect()->back()->withErrors([
                        'materia' => "LÃ­mite alcanzado: el curso solo permite {$periodo->max_materias} materia(s) segÃºn el perÃ­odo definido.",
                    ]);
                }
            }
        } else {
            $maxTotal = (int) DB::table('periodos_dictado')
                ->where('id_nivel', $request->id_nivel)
                ->sum('max_materias');
            if ($maxTotal > 0) {
                $count = DB::table('malla_curricular')
                    ->where('id_nivel', $request->id_nivel)
                    ->count();
                if ($count >= $maxTotal) {
                    return redirect()->back()->withErrors([
                        'materia' => "LÃ­mite alcanzado: este nivel admite {$maxTotal} materia(s) en total segÃºn sus perÃ­odos definidos.",
                    ]);
                }
            }
        }

        if ($esCursoLibre) {
            $orden = $request->orden_en_nivel ??
                (DB::table('malla_curricular')
                    ->where('id_carrera', $request->id_carrera)
                    ->whereNull('id_nivel')
                    ->max('orden_en_nivel') ?? 0) + 1;
        } else {
            $orden = $request->orden_en_nivel ??
                (DB::table('malla_curricular')->where('id_nivel', $request->id_nivel)->max('orden_en_nivel') ?? 0) + 1;
        }

        DB::table('malla_curricular')->insert([
            'id_carrera'     => $request->id_carrera,
            'id_nivel'       => $esCursoLibre ? null : $request->id_nivel,
            'id_materia'     => $request->id_materia,
            'obligatoria'    => $request->boolean('obligatoria', true),
            'orden_en_nivel' => $orden,
        ]);

        return redirect()->back()->with('success', 'Materia asignada a la malla.');
    }

    // DELETE /director/malla/{id}
    public function destroyMalla(int $idMalla)
    {
        $entry = DB::table('malla_curricular')->where('id_malla', $idMalla)->first();
        if (!$entry) return redirect()->back()->withErrors(['malla' => 'Entrada no encontrada.']);

        // Bloquear si otra materia en la misma malla la requiere como prerequisito
        $dependiente = DB::table('malla_curricular as mc')
            ->join('materias as m', 'mc.id_materia', '=', 'm.id_materia')
            ->where('mc.id_carrera', $entry->id_carrera)
            ->where('m.id_materia_requisito', $entry->id_materia)
            ->select('m.nombre')
            ->first();

        if ($dependiente) {
            return redirect()->route('director.carreras.materias', $entry->id_carrera)->withErrors([
                'malla' => "No puedes quitar esta materia: \"{$dependiente->nombre}\" la requiere como prerequisito. Quita esa materia primero.",
            ]);
        }

        DB::table('malla_curricular')->where('id_malla', $idMalla)->delete();

        return redirect()->route('director.carreras.materias', $entry->id_carrera)
            ->with('success', 'Materia removida de la malla.');
    }

    // POST /director/carreras/{id}/nueva-materia â€” crea materia Y la asigna al nivel (o curso libre)
    public function storeMateriaNueva(Request $request, int $idCarrera)
    {
        $carrera = Carrera::findOrFail($idCarrera);
        $esCursoLibre = $carrera->tipo === 'curso_libre';

        $rules = [
            'codigo'               => 'required|string|max:20|unique:materias,codigo',
            'nombre'               => 'required|string|max:150',
            'duracion_meses'       => 'required|integer|min:1',
            'costo_mensual'        => 'required|numeric|min:0',
            'creditos'             => 'nullable|integer|min:0',
            'id_materia_requisito' => 'nullable|integer|exists:materias,id_materia',
            'obligatoria'          => 'boolean',
        ];
        if (!$esCursoLibre) {
            $rules['id_nivel'] = 'required|integer|exists:niveles_carrera,id_nivel';
        }
        $request->validate($rules);

        // â”€â”€ Verificar lÃ­mite max_materias antes de crear la materia â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ($esCursoLibre) {
            $periodo = DB::table('periodos_dictado')
                ->where('id_carrera', $idCarrera)
                ->whereNull('id_nivel')
                ->first();
            if ($periodo && $periodo->max_materias) {
                $count = DB::table('malla_curricular')
                    ->where('id_carrera', $idCarrera)
                    ->whereNull('id_nivel')
                    ->count();
                if ($count >= $periodo->max_materias) {
                    return redirect()->back()->withErrors([
                        'materia' => "LÃ­mite alcanzado: el curso solo permite {$periodo->max_materias} materia(s) segÃºn el perÃ­odo definido.",
                    ]);
                }
            }
        } else {
            $maxTotal = (int) DB::table('periodos_dictado')
                ->where('id_nivel', $request->id_nivel)
                ->sum('max_materias');
            if ($maxTotal > 0) {
                $count = DB::table('malla_curricular')
                    ->where('id_nivel', $request->id_nivel)
                    ->count();
                if ($count >= $maxTotal) {
                    return redirect()->back()->withErrors([
                        'materia' => "LÃ­mite alcanzado: este nivel admite {$maxTotal} materia(s) en total segÃºn sus perÃ­odos definidos.",
                    ]);
                }
            }
        }

        $materia = Materia::create([
            'codigo'               => strtoupper($request->codigo),
            'nombre'               => $request->nombre,
            'duracion_meses'       => $request->duracion_meses,
            'costo_mensual'        => $request->costo_mensual,
            'creditos'             => $request->creditos,
            'id_materia_requisito' => $request->id_materia_requisito ?: null,
            'activo'               => true,
        ]);

        if ($esCursoLibre) {
            $orden = (DB::table('malla_curricular')
                ->where('id_carrera', $idCarrera)
                ->whereNull('id_nivel')
                ->max('orden_en_nivel') ?? 0) + 1;
        } else {
            $orden = (DB::table('malla_curricular')
                ->where('id_nivel', $request->id_nivel)
                ->max('orden_en_nivel') ?? 0) + 1;
        }

        DB::table('malla_curricular')->insert([
            'id_carrera'     => $idCarrera,
            'id_nivel'       => $esCursoLibre ? null : $request->id_nivel,
            'id_materia'     => $materia->id_materia,
            'obligatoria'    => $request->boolean('obligatoria', true),
            'orden_en_nivel' => $orden,
        ]);

        return redirect()->back()->with('success', 'Materia creada y asignada a la malla.');
    }
}
