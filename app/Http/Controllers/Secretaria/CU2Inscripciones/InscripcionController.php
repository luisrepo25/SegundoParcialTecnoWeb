<?php

namespace App\Http\Controllers\Secretaria\CU2Inscripciones;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Carrera;
use App\Models\Usuario;
use App\Models\Estudiante;

class InscripcionController extends Controller
{
    public function index()
    {
        $carreras = Carrera::whereRaw('activo IS TRUE')->get();
        return Inertia::render('Secretaria/CU2Inscripciones/Index', [
            'carreras' => $carreras
        ]);
    }

    public function storeManual(Request $request)
    {
        $request->validate([
            'nombre'     => 'required|string|max:100',
            'apellido'   => 'required|string|max:100',
            'dni'        => 'required|string|max:20',
            'email'      => 'required|email|max:150|unique:usuarios,email',
            'telefono'   => 'nullable|string|max:20',
            'id_carrera' => 'required|exists:carreras,id_carrera',
        ], [
            'email.unique' => 'Ya existe una cuenta con este correo.',
        ]);

        $carrera = Carrera::findOrFail($request->id_carrera);

        // Usamos el DNI como contraseña por defecto
        $password = $request->dni;
        $usuario = null;
        $estudiante = null;

        DB::beginTransaction();
        try {
            // 1. Crear Usuario
            $usuario = Usuario::create([
                'nombre'        => $request->nombre,
                'apellido'      => $request->apellido,
                'email'         => $request->email,
                'password_hash' => Hash::make($password), // Asegurarse de que Model use 'password_hash'
                'dni'           => $request->dni,
                'telefono'      => $request->telefono,
                'id_rol'        => 5, // Rol Estudiante
                'activo'        => true, // Como paga en caja, lo activamos directo
            ]);

            // 2. Crear Estudiante
            $legajo = 'LEG-' . now()->year . '-' . str_pad($usuario->id_usuario, 5, '0', STR_PAD_LEFT);
            $estudiante = Estudiante::create([
                'id_usuario'                => $usuario->id_usuario,
                'legajo'                    => $legajo,
                'fecha_inscripcion_inicial' => now()->toDateString(),
                'id_carrera_actual'         => $request->id_carrera,
            ]);

            // 3. Crear Afiliación a Carrera
            DB::table('afiliaciones_estudiante')->insert([
                'id_estudiante' => $estudiante->id_estudiante,
                'id_carrera'    => $request->id_carrera,
                'tipo_programa' => 'carrera',
                'fecha_inicio'  => now()->toDateString(),
                'estado'        => 'activo'
            ]);

            // 4. Pago de Matrícula en Efectivo (Directo)
            DB::table('matricula_unica')->insert([
                'id_estudiante' => $estudiante->id_estudiante,
                'fecha_pago'    => now()->toDateString(),
                'monto_pagado'  => 500, // Costo de matrícula (fijo 500)
                'comprobante'   => 'Pago en Efectivo - Caja',
                'estado'        => 'pagado'
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['general' => 'Error en el sistema: ' . $e->getMessage()]);
        }

        return redirect()->back()->with('credenciales', [
            'email'    => $usuario->email,
            'password' => $password,
            'legajo'   => $estudiante->legajo,
            'nombre'   => $usuario->nombre . ' ' . $usuario->apellido
        ]);
    }
}
