<?php

namespace App\Http\Controllers\Secretaria\CU3Pagos;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class PagoController extends Controller
{
    // ── CU7.index — Estudiantes con resumen de estado de pagos ──────────────
    public function index(Request $request)
    {
        $buscar = $request->get('buscar', '');

        $tieneMatricula = Schema::hasTable('matricula_unica');
        $tieneCarrera   = Schema::hasTable('pago_carrera_completa');

        $query = Usuario::where('id_rol', 5)
            ->with('estudiante')
            ->orderBy('apellido')
            ->orderBy('nombre');

        if ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre',    'ilike', "%{$buscar}%")
                  ->orWhere('apellido', 'ilike', "%{$buscar}%")
                  ->orWhere('dni',      'ilike', "%{$buscar}%")
                  ->orWhere('email',    'ilike', "%{$buscar}%");
            });
        }

        $usuarios = $query->get();

        // Cargar matrículas y planes en batch para evitar N+1
        $matriculas  = collect();
        $planesCarrera = collect();

        if ($usuarios->count()) {
            $idsEst = $usuarios->map(fn($u) => $u->estudiante?->id_estudiante)->filter()->values();

            if ($tieneMatricula && $idsEst->count()) {
                $matriculas = DB::table('matricula_unica')
                    ->whereIn('id_estudiante', $idsEst)
                    ->get()->keyBy('id_estudiante');
            }
            if ($tieneCarrera && $idsEst->count()) {
                $planesCarrera = DB::table('pago_carrera_completa')
                    ->whereIn('id_estudiante', $idsEst)
                    ->orderByDesc('fecha_contrato')
                    ->get()->groupBy('id_estudiante');
            }
        }

        $estudiantes = $usuarios->map(function ($u) use ($matriculas, $planesCarrera) {
            $idEst = $u->estudiante?->id_estudiante;
            $mat   = $idEst ? $matriculas->get($idEst) : null;
            $plan  = $idEst ? $planesCarrera->get($idEst)?->first() : null;

            return [
                'id_usuario'      => $u->id_usuario,
                'nombre'          => $u->nombre,
                'apellido'        => $u->apellido,
                'email'           => $u->email,
                'dni'             => $u->dni,
                'activo'          => $u->activo,
                'legajo'          => $u->estudiante?->legajo,
                'tiene_matricula' => $mat !== null,
                'matricula_estado'=> $mat?->estado,
                'tiene_carrera'   => $plan !== null,
                'carrera_estado'  => $plan?->estado,
                'carrera_forma'   => $plan?->forma_pago,
            ];
        });

        return Inertia::render('Secretaria/CU3Pagos/Index', [
            'estudiantes' => $estudiantes,
            'filtros'     => ['buscar' => $buscar],
        ]);
    }

    // ── CU7.show — Detalle completo de pagos de un estudiante ───────────────
    public function show(int $id)
    {
        $usuario    = Usuario::where('id_usuario', $id)->where('id_rol', 5)->firstOrFail();
        $estudiante = Estudiante::where('id_usuario', $id)->first();
        $idEst      = $estudiante?->id_estudiante;

        // Carrera actual del estudiante
        $carreraActual = null;
        if ($estudiante?->id_carrera_actual) {
            $c = Carrera::find($estudiante->id_carrera_actual);
            if ($c) {
                $carreraActual = [
                    'id_carrera'             => $c->id_carrera,
                    'codigo'                 => $c->codigo,
                    'nombre'                 => $c->nombre,
                    'costo_carrera_completa' => (float) $c->costo_carrera_completa,
                ];
            }
        }

        // Matrícula
        $matricula = null;
        if ($idEst && Schema::hasTable('matricula_unica')) {
            $m = DB::table('matricula_unica')->where('id_estudiante', $idEst)->first();
            $matricula = $m ? (array) $m : null;
        }

        // Plan de carrera + cuotas
        $planCarrera = null;
        $cuotas      = [];
        if ($idEst && Schema::hasTable('pago_carrera_completa')) {
            $plan = DB::table('pago_carrera_completa')
                ->where('id_estudiante', $idEst)
                ->orderByDesc('fecha_contrato')
                ->first();

            if ($plan) {
                $planCarrera = (array) $plan;

                // Nombre de la carrera del plan
                if (isset($plan->id_carrera)) {
                    $cPlan = Carrera::find($plan->id_carrera);
                    $planCarrera['carrera_nombre'] = $cPlan?->nombre;
                    $planCarrera['carrera_codigo'] = $cPlan?->codigo;
                }

                if (Schema::hasTable('cuotas_carrera') && isset($plan->id_pago_carrera)) {
                    $cuotas = DB::table('cuotas_carrera')
                        ->where('id_pago_carrera', $plan->id_pago_carrera)
                        ->orderBy('numero_cuota')
                        ->get()
                        ->map(fn($c) => (array) $c)
                        ->toArray();
                }
            }
        }

        // Materias sueltas (disponible cuando CU6 esté implementado)
        $materiasSueltas = [];

        // Carreras activas para el select del modal
        $carrerasDisponibles = Carrera::whereRaw('activo IS TRUE')
            ->orderBy('nombre')
            ->get()
            ->map(fn($c) => [
                'id_carrera'             => $c->id_carrera,
                'codigo'                 => $c->codigo,
                'nombre'                 => $c->nombre,
                'costo_carrera_completa' => (float) $c->costo_carrera_completa,
                'minimo_30'              => round((float) $c->costo_carrera_completa * 0.30, 2),
                'precio_contado'         => round((float) $c->costo_carrera_completa * 0.80, 2),
            ]);

        return Inertia::render('Secretaria/CU3Pagos/Show', [
            'estudiante' => [
                'id_usuario'    => $usuario->id_usuario,
                'id_estudiante' => $idEst,
                'nombre'        => $usuario->nombre,
                'apellido'      => $usuario->apellido,
                'email'         => $usuario->email,
                'dni'           => $usuario->dni,
                'activo'        => $usuario->activo,
                'legajo'        => $estudiante?->legajo,
            ],
            'carreraActual'       => $carreraActual,
            'matricula'           => $matricula,
            'planCarrera'         => $planCarrera,
            'cuotas'              => $cuotas,
            'materiasSueltas'     => $materiasSueltas,
            'carrerasDisponibles' => $carrerasDisponibles,
            'pendiente' => [
                'matricula' => !Schema::hasTable('matricula_unica'),
                'carrera'   => !Schema::hasTable('pago_carrera_completa'),
                'materias'  => !Schema::hasTable('inscripciones'),
            ],
        ]);
    }

    // ── CU7.registrarMatricula — Registro admin directo (sin QR) ────────────
    public function registrarMatricula(Request $request, int $id)
    {
        $request->validate([
            'monto'       => 'required|numeric|min:1',
            'comprobante' => 'nullable|string|max:120',
        ]);

        $estudiante = Estudiante::where('id_usuario', $id)->firstOrFail();

        if (DB::table('matricula_unica')->where('id_estudiante', $estudiante->id_estudiante)->exists()) {
            return back()->withErrors(['monto' => 'El estudiante ya tiene matrícula registrada.']);
        }

        $comprobante = $request->comprobante
            ?: ('ADM-MAT-' . $estudiante->id_estudiante . '-' . now()->timestamp);

        DB::table('matricula_unica')->insert([
            'id_estudiante' => $estudiante->id_estudiante,
            'monto_pagado'  => $request->monto,
            'comprobante'   => $comprobante,
            'estado'        => 'pagado',
        ]);

        return back()->with('success', 'Matrícula registrada correctamente.');
    }

    // ── CU7.registrarCarrera — Registro admin directo (sin QR) ─────────────
    public function registrarCarrera(Request $request, int $id)
    {
        $request->validate([
            'id_carrera' => 'required|integer|exists:carreras,id_carrera',
            'monto'      => 'required|numeric|min:0.01',
        ]);

        $estudiante = Estudiante::where('id_usuario', $id)->firstOrFail();
        $carrera    = Carrera::findOrFail($request->id_carrera);
        $costo      = (float) $carrera->costo_carrera_completa;
        $monto      = (float) $request->monto;

        $minimo30 = round($costo * 0.30, 2);
        if ($monto < $minimo30) {
            return back()->withErrors([
                'monto' => "El monto mínimo es el 30% del costo: Bs. {$minimo30}.",
            ]);
        }

        if (DB::table('pago_carrera_completa')
            ->where('id_estudiante', $estudiante->id_estudiante)
            ->where('id_carrera', $carrera->id_carrera)
            ->exists()) {
            return back()->withErrors(['id_carrera' => 'El estudiante ya tiene un plan para esta carrera.']);
        }

        $precioContado = round($costo * 0.80, 2);
        $formaPago     = $monto >= $precioContado ? 'contado' : 'credito';
        $estado        = $formaPago === 'contado'  ? 'pagado'  : 'parcial';

        // El trigger 'crear_cuotas_credito' genera las cuotas automáticamente al insertar
        DB::table('pago_carrera_completa')->insert([
            'id_estudiante'  => $estudiante->id_estudiante,
            'id_carrera'     => $carrera->id_carrera,
            'monto_total'    => $costo,
            'monto_pagado'   => $monto,
            'forma_pago'     => $formaPago,
            'estado'         => $estado,
            'fecha_contrato' => now()->toDateString(),
        ]);

        $estudiante->update(['id_carrera_actual' => $carrera->id_carrera]);

        $label = $formaPago === 'contado' ? 'contado (con descuento del 20%)' : 'crédito';
        return back()->with('success', "Plan de carrera registrado a {$label}.");
    }

    // ── CU7.pagarCuota — Admin directo ──────────────────────────────────────
    public function pagarCuota(int $idPago, int $numCuota)
    {
        $afectadas = DB::table('cuotas_carrera')
            ->where('id_pago_carrera', $idPago)
            ->where('numero_cuota', $numCuota)
            ->where('estado', 'pendiente')
            ->update([
                'estado'     => 'pagado',
                'fecha_pago' => now()->toDateString(),
            ]);

        if (!$afectadas) {
            return back()->withErrors(['cuota' => 'La cuota no existe o ya fue pagada.']);
        }

        // Revisar si quedan cuotas pendientes
        $restantes = DB::table('cuotas_carrera')
            ->where('id_pago_carrera', $idPago)
            ->where('estado', 'pendiente')
            ->count();

        DB::table('pago_carrera_completa')
            ->where('id_pago_carrera', $idPago)
            ->update(['estado' => $restantes === 0 ? 'pagado' : 'parcial']);

        return back()->with('success', "Cuota #{$numCuota} registrada como pagada.");
    }
}
