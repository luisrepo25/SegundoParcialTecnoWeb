<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Usuario;
use App\Services\PagoFacilService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OfertaController extends Controller
{
    // ── Listado de carreras activas ────────────────────────────────────────────
    public function index()
    {
        $carreras = Carrera::whereRaw('activo IS TRUE')
            ->orderBy('nombre')
            ->get()
            ->map(fn($c) => [
                'id_carrera'             => $c->id_carrera,
                'codigo'                 => $c->codigo,
                'nombre'                 => $c->nombre,
                'descripcion'            => $c->descripcion,
                'tipo'                   => $c->tipo,
                'duracion_niveles'       => $c->duracion_niveles,
                'costo_carrera_completa' => (float) $c->costo_carrera_completa,
            ]);

        return Inertia::render('Public/Oferta/Index', compact('carreras'));
    }

    // ── Detalle de carrera + malla curricular ─────────────────────────────────
    public function show(int $id)
    {
        $carrera = Carrera::whereRaw('activo IS TRUE')->where('id_carrera', $id)->firstOrFail();

        $malla = [];
        if (Schema::hasTable('niveles_carrera') && Schema::hasTable('malla_curricular')) {
            $niveles = DB::table('niveles_carrera')
                ->where('id_carrera', $id)
                ->orderBy('numero_nivel')
                ->get();

            foreach ($niveles as $nivel) {
                $materias = DB::table('malla_curricular as mc')
                    ->join('materias as m', 'mc.id_materia', '=', 'm.id_materia')
                    ->where('mc.id_carrera', $id)
                    ->where('mc.id_nivel', $nivel->id_nivel)
                    ->orderBy('mc.orden_en_nivel')
                    ->select('m.nombre', 'm.codigo', 'mc.obligatoria', 'mc.orden_en_nivel')
                    ->get()
                    ->map(fn($m) => (array) $m)
                    ->toArray();

                $malla[] = [
                    'id_nivel'     => $nivel->id_nivel,
                    'numero_nivel' => $nivel->numero_nivel,
                    'nombre'       => $nivel->nombre,
                    'materias'     => $materias,
                ];
            }
        }

        return Inertia::render('Public/Oferta/Show', [
            'carrera' => [
                'id_carrera'             => $carrera->id_carrera,
                'codigo'                 => $carrera->codigo,
                'nombre'                 => $carrera->nombre,
                'descripcion'            => $carrera->descripcion,
                'tipo'                   => $carrera->tipo,
                'duracion_niveles'       => $carrera->duracion_niveles,
                'costo_carrera_completa' => (float) $carrera->costo_carrera_completa,
            ],
            'malla'      => $malla,
            'tieneMalla' => count($malla) > 0,
        ]);
    }

    // ── Formulario de inscripción ──────────────────────────────────────────────
    public function formulario(int $id)
    {
        $carrera = Carrera::whereRaw('activo IS TRUE')->where('id_carrera', $id)->firstOrFail();

        return Inertia::render('Public/Oferta/Formulario', [
            'carrera' => [
                'id_carrera'             => $carrera->id_carrera,
                'nombre'                 => $carrera->nombre,
                'tipo'                   => $carrera->tipo,
                'duracion_niveles'       => $carrera->duracion_niveles,
                'costo_carrera_completa' => (float) $carrera->costo_carrera_completa,
            ],
        ]);
    }

    // ── Registrar estudiante y generar QR ─────────────────────────────────────
    public function registrar(Request $request, int $idCarrera)
    {
        $request->validate([
            'nombre'   => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'dni'      => 'required|string|max:20',
            'email'    => 'required|email|max:150',
            'telefono' => 'nullable|string|max:20',
        ]);

        $carrera = Carrera::whereRaw('activo IS TRUE')->where('id_carrera', $idCarrera)->firstOrFail();

        // 1. Crear o reutilizar usuario inactivo (permite reintentar si el QR anterior expiró)
        $tempPassword = Str::random(10);
        $usuario      = null;
        $estudiante   = null;
        $esReintento  = false;

        $usuarioExistente = DB::table('usuarios')->where('email', $request->email)->first();

        if ($usuarioExistente) {
            $estExistente = DB::table('estudiantes')->where('id_usuario', $usuarioExistente->id_usuario)->first();

            // Bloquear si la cuenta ya está activa o no es un estudiante
            if ($usuarioExistente->activo || !$estExistente) {
                return back()->withErrors(['email' => 'Ya existe una cuenta activa con ese correo. Ingresa desde el Login.']);
            }
            // Bloquear si ya tiene matrícula pagada
            if (DB::table('matricula_unica')->where('id_estudiante', $estExistente->id_estudiante)->exists()) {
                return back()->withErrors(['email' => 'Ya tienes una matrícula registrada. Ingresa desde el Login.']);
            }

            // Cuenta inactiva sin matrícula → es un reintento (QR expiró antes)
            // Actualizar contraseña temporal y expirar transacciones viejas
            DB::table('usuarios')->where('id_usuario', $usuarioExistente->id_usuario)->update([
                'password_hash' => Hash::make($tempPassword),
            ]);
            DB::table('pagofacil_transacciones')
                ->where('id_estudiante', $estExistente->id_estudiante)
                ->where('concepto', 'matricula')
                ->where('estado', 'pendiente')
                ->update(['estado' => 'expirado']);

            $usuario     = $usuarioExistente;
            $estudiante  = $estExistente;
            $esReintento = true;
        } else {
            // Nuevo registro
            DB::beginTransaction();
            try {
                $usuarioCreado = Usuario::create([
                    'nombre'        => $request->nombre,
                    'apellido'      => $request->apellido,
                    'email'         => $request->email,
                    'password_hash' => Hash::make($tempPassword),
                    'dni'           => $request->dni,
                    'telefono'      => $request->telefono,
                    'id_rol'        => 5,
                    'activo'        => false,
                ]);

                $legajo = 'LEG-' . now()->year . '-' . str_pad($usuarioCreado->id_usuario, 5, '0', STR_PAD_LEFT);
                $estCreado = Estudiante::create([
                    'id_usuario'                => $usuarioCreado->id_usuario,
                    'legajo'                    => $legajo,
                    'fecha_inscripcion_inicial' => now()->toDateString(),
                    'id_carrera_actual'         => $idCarrera,
                ]);

                DB::commit();
                $usuario    = $usuarioCreado;
                $estudiante = $estCreado;
            } catch (\Throwable $e) {
                DB::rollBack();
                return back()->withErrors(['general' => 'Error al registrar datos: ' . $e->getMessage()]);
            }
        }

        // 2. Generar QR con PagoFácil
        $paymentNumber = 'MAT-' . $estudiante->id_estudiante . '-' . now()->timestamp;

        try {
            $pf = app(PagoFacilService::class);
            $qrResult = $pf->generarQR([
                'clientName'    => $request->nombre . ' ' . $request->apellido,
                'documentId'    => $request->dni,
                'phoneNumber'   => $request->telefono ?: '70000000',
                'email'         => $request->email,
                'paymentNumber' => $paymentNumber,
                'clientCode'    => (string) $estudiante->id_estudiante,
                'concepto'      => 'Matrícula — ' . $carrera->nombre,
            ]);
        } catch (\Throwable $e) {
            // Si es registro nuevo, limpiar lo creado; si es reintento, dejar cuenta intacta
            if (!$esReintento) {
                $estudiante->delete();
                $usuario->delete();
            }
            return back()->withErrors(['general' => 'No se pudo conectar con el sistema de pago: ' . $e->getMessage()]);
        }

        // Estructura real: { "values": { "transactionId": ..., "qrBase64": "..." } }
        $apiTransId = $qrResult['values']['transactionId']
            ?? $qrResult['transactionId']
            ?? null;

        $qrRaw = $qrResult['values']['qrBase64']
            ?? $qrResult['values']['qrImage']
            ?? $qrResult['qrImage']
            ?? null;

        // Agregar prefijo data URI si no lo tiene
        $qrImage = $qrRaw
            ? (str_starts_with($qrRaw, 'data:') ? $qrRaw : 'data:image/png;base64,' . $qrRaw)
            : null;

        if (!$apiTransId) {
            if (!$esReintento) {
                $estudiante->delete();
                $usuario->delete();
            }
            return back()->withErrors(['general' => 'PagoFácil no devolvió ID de transacción. Respuesta: ' . json_encode($qrResult)]);
        }

        // 3. Insertar transacción con transaction_id_api ya disponible
        $transId = DB::table('pagofacil_transacciones')->insertGetId([
            'transaction_id_api' => $apiTransId,
            'payment_number'     => $paymentNumber,
            'id_estudiante'      => $estudiante->id_estudiante,
            'monto'              => 500.00,
            'concepto'           => 'matricula',
            'estado'             => 'pendiente',
        ], 'id_transaccion_pf');

        // 4. Guardar QR y contraseña temporal en sesión
        session([
            'pf_qr_' . $transId => $qrImage,
            'pf_pw_' . $transId => $tempPassword,
        ]);

        return redirect()->route('oferta.pago', $transId);
    }

    // ── Página de pago (muestra QR) ───────────────────────────────────────────
    public function pago(int $transId)
    {
        $trans = DB::table('pagofacil_transacciones')
            ->where('id_transaccion_pf', $transId)
            ->first();

        if (!$trans) abort(404);

        $usuario = null;
        if ($trans->id_estudiante) {
            $est     = DB::table('estudiantes')->where('id_estudiante', $trans->id_estudiante)->first();
            $usuario = $est ? DB::table('usuarios')->where('id_usuario', $est->id_usuario)->first() : null;
        }

        return Inertia::render('Public/Oferta/Pago', [
            'transId' => $transId,
            'qrImage' => session('pf_qr_' . $transId),
            'estado'  => $trans->estado,
            'monto'   => (float) ($trans->monto ?? 500),
            'email'   => $usuario?->email ?? '',
        ]);
    }

    // ── Endpoint de polling (JSON) ────────────────────────────────────────────
    public function estado(int $transId)
    {
        $trans = DB::table('pagofacil_transacciones')
            ->where('id_transaccion_pf', $transId)
            ->first();

        if (!$trans) return response()->json(['estado' => 'error'], 404);

        if ($trans->estado === 'pagado') {
            return $this->respuestaPagado((int) $trans->id_estudiante, $transId);
        }

        // Expirar si han pasado más de 15 minutos
        if ($trans->fecha_generacion && now()->diffInMinutes($trans->fecha_generacion) >= 15) {
            DB::table('pagofacil_transacciones')
                ->where('id_transaccion_pf', $transId)
                ->where('estado', 'pendiente')
                ->update(['estado' => 'expirado']);
            return response()->json(['estado' => 'expirado']);
        }

        // Consultar estado en PagoFácil como respaldo al callback
        if ($trans->transaction_id_api) {
            try {
                $result = app(PagoFacilService::class)->consultarTransaccion((int) $trans->transaction_id_api);
                if (PagoFacilService::esPagado($result)) {
                    DB::table('pagofacil_transacciones')
                        ->where('id_transaccion_pf', $transId)
                        ->where('estado', 'pendiente')
                        ->update(['estado' => 'pagado']);
                    $this->activarEstudiante((int) $trans->id_estudiante);
                    return $this->respuestaPagado((int) $trans->id_estudiante, $transId);
                }
            } catch (\Throwable) {
                // PagoFácil no responde — no falla, el callback es la vía principal
            }
        }

        return response()->json(['estado' => $trans->estado ?? 'pendiente']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private function activarEstudiante(int $idEstudiante): void
    {
        $est = DB::table('estudiantes')->where('id_estudiante', $idEstudiante)->first();
        if ($est) {
            DB::table('usuarios')
                ->where('id_usuario', $est->id_usuario)
                ->where('activo', false)
                ->update(['activo' => true]);
        }
    }

    private function respuestaPagado(int $idEstudiante, int $transId): \Illuminate\Http\JsonResponse
    {
        $this->activarEstudiante($idEstudiante);

        $est     = DB::table('estudiantes')->where('id_estudiante', $idEstudiante)->first();
        $usuario = $est ? DB::table('usuarios')->where('id_usuario', $est->id_usuario)->first() : null;

        return response()->json([
            'estado'   => 'pagado',
            'email'    => $usuario?->email ?? '',
            'password' => session('pf_pw_' . $transId),
            'legajo'   => $est?->legajo ?? '',
        ]);
    }
}
