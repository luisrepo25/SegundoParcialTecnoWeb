<?php

namespace App\Http\Controllers\Director\CU6Malla;

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

        $maxNivel = DB::table('niveles_carrera')
            ->where('id_carrera', $idCarrera)
            ->max('numero_nivel') ?? 0;

        $numero = $maxNivel + 1;

        DB::table('niveles_carrera')->insert([
            'id_carrera'   => $idCarrera,
            'numero_nivel' => $numero,
            'nombre'       => $request->nombre ?: "Nivel $numero",
            'descripcion'  => $request->descripcion,
        ]);

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

    // POST /director/malla  — asigna materia existente a un nivel
    public function storeMalla(Request $request)
    {
        $request->validate([
            'id_carrera'     => 'required|integer|exists:carreras,id_carrera',
            'id_nivel'       => 'required|integer|exists:niveles_carrera,id_nivel',
            'id_materia'     => 'required|integer|exists:materias,id_materia',
            'obligatoria'    => 'boolean',
            'orden_en_nivel' => 'nullable|integer|min:1',
        ]);

        $existe = DB::table('malla_curricular')
            ->where('id_carrera', $request->id_carrera)
            ->where('id_materia', $request->id_materia)
            ->exists();

        if ($existe) {
            return redirect()->back()->withErrors(['materia' => 'Esta materia ya está en la malla de esta carrera.']);
        }

        // Validar que el prerequisito ya esté en la malla antes de agregar esta materia
        $materia = DB::table('materias')->where('id_materia', $request->id_materia)->first();

        // Si es materia base (sin prereq), verificar que no queden cadenas pendientes en la carrera
        if (!$materia->id_materia_requisito) {
            $cadenaPendiente = DB::table('materias as m')
                ->whereNotNull('m.id_materia_requisito')
                ->where('m.activo', true)
                ->whereExists(function ($q) use ($request) {
                    // Su prereq está en la malla de esta carrera
                    $q->select(DB::raw(1))
                      ->from('malla_curricular as mc_pre')
                      ->where('mc_pre.id_carrera', $request->id_carrera)
                      ->whereColumn('mc_pre.id_materia', 'm.id_materia_requisito');
                })
                ->whereNotExists(function ($q) use ($request) {
                    // Pero ella misma aún no está en la malla
                    $q->select(DB::raw(1))
                      ->from('malla_curricular as mc_self')
                      ->where('mc_self.id_carrera', $request->id_carrera)
                      ->whereColumn('mc_self.id_materia', 'm.id_materia');
                })
                ->exists();

            if ($cadenaPendiente) {
                return redirect()->route('director.carreras.materias', $request->id_carrera)->withErrors([
                    'materia' => 'Aún hay materias que continúan cadenas existentes sin agregar. Completa las cadenas antes de iniciar una nueva rama.',
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
                    'materia' => "No se puede agregar \"{$materia->nombre}\": su prerequisito \"{$prereq->nombre}\" aún no está en la malla. Agrégalo primero.",
                ]);
            }
        }

        $orden = $request->orden_en_nivel ??
            (DB::table('malla_curricular')->where('id_nivel', $request->id_nivel)->max('orden_en_nivel') ?? 0) + 1;

        DB::table('malla_curricular')->insert([
            'id_carrera'     => $request->id_carrera,
            'id_nivel'       => $request->id_nivel,
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

    // POST /director/carreras/{id}/nueva-materia — crea materia Y la asigna al nivel
    public function storeMateriaNueva(Request $request, int $idCarrera)
    {
        Carrera::findOrFail($idCarrera);

        $request->validate([
            'codigo'               => 'required|string|max:20|unique:materias,codigo',
            'nombre'               => 'required|string|max:150',
            'duracion_meses'       => 'required|integer|min:1',
            'costo_mensual'        => 'required|numeric|min:0',
            'creditos'             => 'nullable|integer|min:0',
            'id_materia_requisito' => 'nullable|integer|exists:materias,id_materia',
            'id_nivel'             => 'required|integer|exists:niveles_carrera,id_nivel',
            'obligatoria'          => 'boolean',
        ]);

        $materia = Materia::create([
            'codigo'               => strtoupper($request->codigo),
            'nombre'               => $request->nombre,
            'duracion_meses'       => $request->duracion_meses,
            'costo_mensual'        => $request->costo_mensual,
            'creditos'             => $request->creditos,
            'id_materia_requisito' => $request->id_materia_requisito ?: null,
            'activo'               => true,
        ]);

        $orden = (DB::table('malla_curricular')
            ->where('id_nivel', $request->id_nivel)
            ->max('orden_en_nivel') ?? 0) + 1;

        DB::table('malla_curricular')->insert([
            'id_carrera'     => $idCarrera,
            'id_nivel'       => $request->id_nivel,
            'id_materia'     => $materia->id_materia,
            'obligatoria'    => $request->boolean('obligatoria', true),
            'orden_en_nivel' => $orden,
        ]);

        return redirect()->back()->with('success', 'Materia creada y asignada a la malla.');
    }
}
