<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BusquedaController extends Controller
{
    public function buscar(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $like = '%' . strtolower($q) . '%';
        $role = Auth::user()?->role ?? '';
        $resultados = [];

        // Carreras — todos los roles pueden ver
        $carreras = DB::table('carreras')
            ->whereRaw('activo IS TRUE')
            ->where(function ($query) use ($like) {
                $query->whereRaw('LOWER(nombre) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(codigo) LIKE ?', [$like]);
            })
            ->select('id_carrera', 'nombre', 'codigo', 'tipo')
            ->limit(5)
            ->get();

        foreach ($carreras as $c) {
            $resultados[] = [
                'tipo'     => 'Carrera',
                'texto'    => $c->nombre,
                'subtexto' => $c->codigo . ' · ' . ucfirst($c->tipo),
                'url'      => route('director.carreras.index'),
            ];
        }

        // Materias — todos los roles pueden ver
        $materias = DB::table('materias')
            ->whereRaw('activo IS TRUE')
            ->where(function ($query) use ($like) {
                $query->whereRaw('LOWER(nombre) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(codigo) LIKE ?', [$like]);
            })
            ->select('id_materia', 'nombre', 'codigo')
            ->limit(5)
            ->get();

        foreach ($materias as $m) {
            $resultados[] = [
                'tipo'     => 'Materia',
                'texto'    => $m->nombre,
                'subtexto' => $m->codigo,
                'url'      => route('director.materias.index'),
            ];
        }

        // Estudiantes — propietario, director, secretaria
        if (in_array($role, ['propietario', 'director', 'secretaria'])) {
            $estudiantes = DB::table('usuarios')
                ->join('estudiantes', 'usuarios.id_usuario', '=', 'estudiantes.id_usuario')
                ->where(function ($query) use ($like) {
                    $query->whereRaw('LOWER(usuarios.nombre) LIKE ?', [$like])
                          ->orWhereRaw('LOWER(usuarios.apellido) LIKE ?', [$like])
                          ->orWhereRaw('LOWER(CONCAT(usuarios.nombre, \' \', usuarios.apellido)) LIKE ?', [$like])
                          ->orWhereRaw('LOWER(estudiantes.legajo) LIKE ?', [$like]);
                })
                ->select('usuarios.nombre', 'usuarios.apellido', 'estudiantes.legajo')
                ->limit(5)
                ->get();

            foreach ($estudiantes as $e) {
                $resultados[] = [
                    'tipo'     => 'Estudiante',
                    'texto'    => $e->apellido . ', ' . $e->nombre,
                    'subtexto' => $e->legajo,
                    'url'      => route('propietario.usuarios.index'),
                ];
            }
        }

        // Profesores — propietario, director, secretaria
        if (in_array($role, ['propietario', 'director', 'secretaria'])) {
            $profesores = DB::table('usuarios')
                ->join('profesores', 'usuarios.id_usuario', '=', 'profesores.id_usuario')
                ->where(function ($query) use ($like) {
                    $query->whereRaw('LOWER(usuarios.nombre) LIKE ?', [$like])
                          ->orWhereRaw('LOWER(usuarios.apellido) LIKE ?', [$like])
                          ->orWhereRaw('LOWER(CONCAT(usuarios.nombre, \' \', usuarios.apellido)) LIKE ?', [$like]);
                })
                ->select('usuarios.nombre', 'usuarios.apellido', 'usuarios.email')
                ->limit(4)
                ->get();

            foreach ($profesores as $p) {
                $resultados[] = [
                    'tipo'     => 'Profesor',
                    'texto'    => $p->apellido . ', ' . $p->nombre,
                    'subtexto' => $p->email,
                    'url'      => route('propietario.usuarios.index'),
                ];
            }
        }

        return response()->json($resultados);
    }
}
