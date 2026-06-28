<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use App\Services\PagoFacilService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PanelController extends Controller
{
    // ── Panel principal ────────────────────────────────────────────────────────
    public function index()
    {
        $userId = auth()->id();
        $est    = DB::table('estudiantes')->where('id_usuario', $userId)->first();

        if (!$est) {
            return Inertia::render('Dashboard/Estudiante', [
                'estudiante' => null, 'inscripciones' => [],
                'gruposDisponibles' => [], 'afiliacion' => null, 'pagoCarrera' => null, 'planOpciones' => [],
            ]);
        }

        $carrera  = $est->id_carrera_actual
            ? DB::table('carreras')->where('id_carrera', $est->id_carrera_actual)->first()
            : null;

        $matricula = DB::table('matricula_unica')->where('id_estudiante', $est->id_estudiante)->first();

        // Afiliación activa — necesaria para inscribirse
        $afiliacion = DB::table('afiliaciones_estudiante')
            ->where('id_estudiante', $est->id_estudiante)
            ->where('estado', 'activo')
            ->orderByDesc('fecha_inicio')
            ->first();

        // Plan de pago de carrera (creado por trigger al pagar)
        $pagoCarrera = DB::table('pago_carrera_completa')
            ->where('id_estudiante', $est->id_estudiante)
            ->whereIn('estado', ['pagado', 'parcial'])
            ->orderByDesc('fecha_contrato')
            ->first();

        // Opciones de plan — valores válidos según constraints del trigger
        $planOpciones = [];
        if ($carrera) {
            $costoTotal    = (float) ($carrera->costo_carrera_completa ?? 0);
            $totalMaterias = max(DB::table('malla_curricular')->where('id_carrera', $est->id_carrera_actual)->count(), 1);

            $planOpciones = [
                'contado' => [
                    'tipo'           => 'contado',
                    'titulo'         => 'Pago Total',
                    'descripcion'    => '20% de descuento. Cubre todas las materias.',
                    'monto_inicial'  => round($costoTotal * 0.80, 2),
                    'monto_original' => $costoTotal,
                    'ahorro'         => round($costoTotal * 0.20, 2),
                    'por_materia'    => 0,
                ],
                'credito' => [
                    'tipo'           => 'credito',
                    'titulo'         => 'Enganche + Materias',
                    'descripcion'    => 'Paga 30% ahora y el resto al inscribirte en cada materia.',
                    'monto_inicial'  => round($costoTotal * 0.30, 2),
                    'monto_original' => $costoTotal,
                    'ahorro'         => 0,
                    'por_materia'    => round($costoTotal * 0.70 / $totalMaterias, 2),
                ],
                'materia' => [
                    'tipo'           => 'materia',
                    'titulo'         => 'Pago por Materia',
                    'descripcion'    => 'Sin enganche. Pagas solo cuando te inscribes en cada materia.',
                    'monto_inicial'  => 0,
                    'monto_original' => $costoTotal,
                    'ahorro'         => 0,
                    'por_materia'    => round($costoTotal / $totalMaterias, 2),
                ],
            ];
        }

        // Mis inscripciones (excluir las que están en pendiente de pago)
        $inscripciones = DB::table('inscripciones as i')
            ->join('grupos as g',           'i.id_oferta',   '=', 'g.id_oferta')
            ->join('materias as m',          'g.id_materia',  '=', 'm.id_materia')
            ->join('periodos_dictado as pd', 'g.id_periodo',  '=', 'pd.id_periodo')
            ->join('horarios as h',          'g.id_horario',  '=', 'h.id_horario')
            ->join('aulas as a',             'g.id_aula',     '=', 'a.id_aula')
            ->join('profesores as p',        'g.id_profesor', '=', 'p.id_profesor')
            ->join('usuarios as u',          'p.id_usuario',  '=', 'u.id_usuario')
            ->where('i.id_estudiante', $est->id_estudiante)
            ->where('i.estado', '!=', 'pendiente_matricula')
            ->select(
                'i.id_inscripcion', 'i.estado', 'i.calificacion_final', 'i.aprobado', 'i.fecha_inscripcion',
                'g.id_oferta', 'g.codigo_grupo',
                'm.nombre as materia_nombre', 'm.codigo as materia_codigo',
                'pd.nombre as periodo_nombre', 'pd.fecha_inicio as periodo_inicio', 'pd.fecha_fin as periodo_fin',
                'h.dia_semana', 'h.hora_inicio', 'h.hora_fin',
                'a.nombre as aula_nombre',
                DB::raw("u.nombre || ' ' || u.apellido as profesor_nombre"),
                DB::raw("p.archivo_cv /* v2 */ as profesor_cv")
            )
            ->orderBy('pd.fecha_inicio', 'desc')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        $gruposInscritos = array_column($inscripciones, 'id_oferta');

        // Grupos disponibles en períodos activos de su carrera
        $gruposDisponibles = [];
        if ($carrera) {
            $idsPeriodo = DB::table('periodos_dictado as pd')
                ->leftJoin('niveles_carrera as n', 'pd.id_nivel', '=', 'n.id_nivel')
                ->whereRaw('pd.activo IS TRUE')
                ->whereRaw("CURRENT_DATE BETWEEN pd.fecha_inicio AND pd.fecha_fin")
                ->where(function ($q) use ($est) {
                    $q->where('n.id_carrera', $est->id_carrera_actual)
                      ->orWhere('pd.id_carrera', $est->id_carrera_actual);
                })
                ->distinct()
                ->pluck('pd.id_periodo');

            if ($idsPeriodo->isNotEmpty()) {
                $query = DB::table('grupos as g')
                    ->join('materias as m',          'g.id_materia',  '=', 'm.id_materia')
                    ->join('periodos_dictado as pd', 'g.id_periodo',  '=', 'pd.id_periodo')
                    ->join('horarios as h',          'g.id_horario',  '=', 'h.id_horario')
                    ->join('aulas as a',             'g.id_aula',     '=', 'a.id_aula')
                    ->join('profesores as p',        'g.id_profesor', '=', 'p.id_profesor')
                    ->join('usuarios as u',          'p.id_usuario',  '=', 'u.id_usuario')
                    ->whereIn('g.id_periodo', $idsPeriodo)
                    ->whereRaw('g.activo IS TRUE')
                    ->whereRaw('COALESCE(g.vacantes_ocupadas, 0) < g.vacantes_max')
                    ->whereExists(function ($q) use ($est) {
                        $q->select(DB::raw(1))
                          ->from('malla_curricular')
                          ->where('malla_curricular.id_carrera', $est->id_carrera_actual)
                          ->whereColumn('malla_curricular.id_materia', 'g.id_materia');
                    });

                if (!empty($gruposInscritos)) {
                    $query->whereNotIn('g.id_oferta', $gruposInscritos);
                }

                $gruposDisponibles = $query
                    ->select(
                        'g.id_oferta', 'g.codigo_grupo', 'g.vacantes_max', 'g.vacantes_ocupadas',
                        'm.nombre as materia_nombre', 'm.codigo as materia_codigo',
                        'pd.id_periodo', 'pd.nombre as periodo_nombre',
                        'pd.fecha_inicio as periodo_inicio', 'pd.fecha_fin as periodo_fin',
                        'h.dia_semana', 'h.hora_inicio', 'h.hora_fin',
                        'a.nombre as aula_nombre',
                        DB::raw("u.nombre || ' ' || u.apellido as profesor_nombre"),
                        DB::raw("p.archivo_cv /* v2 */ as profesor_cv")
                    )
                    ->orderBy('pd.fecha_inicio', 'desc')
                    ->orderBy('m.nombre')
                    ->get()
                    ->map(fn($r) => (array) $r)
                    ->toArray();
            }
        }

        return Inertia::render('Dashboard/Estudiante', [
            'estudiante' => [
                'id_estudiante'   => $est->id_estudiante,
                'legajo'          => $est->legajo,
                'tiene_matricula' => $matricula !== null,
                'matricula'       => $matricula ? [
                    'fecha_pago'   => $matricula->fecha_pago,
                    'monto_pagado' => (float) $matricula->monto_pagado,
                ] : null,
                'carrera' => $carrera ? [
                    'id_carrera'             => $carrera->id_carrera,
                    'nombre'                 => $carrera->nombre,
                    'tipo'                   => $carrera->tipo,
                    'codigo'                 => $carrera->codigo,
                    'costo_carrera_completa' => (float) $carrera->costo_carrera_completa,
                ] : null,
            ],
            'afiliacion'  => $afiliacion ? [
                'tipo_programa' => $afiliacion->tipo_programa,
                'fecha_inicio'  => $afiliacion->fecha_inicio,
                'estado'        => $afiliacion->estado,
            ] : null,
            'pagoCarrera' => $pagoCarrera ? [
                'forma_pago'   => $pagoCarrera->forma_pago,
                'monto_total'  => (float) $pagoCarrera->monto_total,
                'monto_pagado' => (float) ($pagoCarrera->monto_pagado ?? 0),
                'estado'       => $pagoCarrera->estado,
                'fecha_contrato' => $pagoCarrera->fecha_contrato,
            ] : null,
            'planOpciones'      => $planOpciones,
            'inscripciones'     => $inscripciones,
            'gruposDisponibles' => $gruposDisponibles,
        ]);
    }

    // ── Elegir plan de pago de carrera ────────────────────────────────────────
    // tipos: 'contado' (20% desc) | 'credito' (30% enganche) | 'materia' (sin enganche)
    public function elegirPlan(Request $request, string $tipo)
    {
        if (!in_array($tipo, ['contado', 'credito', 'materia'])) abort(404);

        $userId = auth()->id();
        $est    = DB::table('estudiantes')->where('id_usuario', $userId)->first();
        if (!$est || !$est->id_carrera_actual) abort(403);

        if (DB::table('afiliaciones_estudiante')->where('id_estudiante', $est->id_estudiante)->where('estado', 'activo')->exists()) {
            return back()->withErrors(['plan' => 'Ya tienes un plan de carrera activo.']);
        }

        $carrera    = DB::table('carreras')->where('id_carrera', $est->id_carrera_actual)->firstOrFail();
        $costoTotal = (float) $carrera->costo_carrera_completa;

        // Expirar cualquier QR de carrera anterior pendiente (permite reintentar sin bloqueo)
        DB::table('pagofacil_transacciones')
            ->where('id_estudiante', $est->id_estudiante)
            ->where('concepto', 'carrera')
            ->where('estado', 'pendiente')
            ->update(['estado' => 'expirado']);

        // Plan POR MATERIA: sin QR, crear afiliación directamente (sin pago_carrera_completa)
        if ($tipo === 'materia') {
            DB::table('afiliaciones_estudiante')->insert([
                'id_estudiante' => $est->id_estudiante,
                'id_carrera'    => $est->id_carrera_actual,
                'tipo_programa' => 'carrera',
                'fecha_inicio'  => now()->toDateString(),
                'estado'        => 'activo',
            ]);
            return redirect()->route('estudiante.panel')->with('success', '¡Plan activado! Ya puedes inscribirte en materias. Pagas cada materia al inscribirte.');
        }

        // CONTADO (≥80% = con 20% descuento) o CRÉDITO (≥30% = enganche)
        // El trigger detecta contado vs credito según monto vs costo*0.80
        $monto = match ($tipo) {
            'contado' => round($costoTotal * 0.80, 2),
            'credito' => round($costoTotal * 0.30, 2),
        };

        $user          = DB::table('usuarios')->where('id_usuario', $userId)->first();
        $paymentNumber = 'CARRERA-' . $est->id_estudiante . '-' . now()->timestamp;

        // Generar QR
        try {
            $pf       = app(PagoFacilService::class);
            $qrResult = $pf->generarQR([
                'clientName'    => $user->nombre . ' ' . $user->apellido,
                'documentId'    => $user->dni ?? '00000000',
                'phoneNumber'   => $user->telefono ?: '70000000',
                'email'         => $user->email,
                'paymentNumber' => $paymentNumber,
                'clientCode'    => (string) $est->id_estudiante,
                'concepto'      => 'Carrera: ' . $carrera->nombre,
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['plan' => 'Error al conectar con el sistema de pago: ' . $e->getMessage()]);
        }

        $apiTransId = $qrResult['values']['transactionId'] ?? $qrResult['transactionId'] ?? null;
        $qrRaw      = $qrResult['values']['qrBase64'] ?? $qrResult['values']['qrImage'] ?? null;
        $qrImage    = $qrRaw ? (str_starts_with($qrRaw, 'data:') ? $qrRaw : 'data:image/png;base64,' . $qrRaw) : null;

        if (!$apiTransId) {
            return back()->withErrors(['plan' => 'PagoFácil no devolvió ID de transacción.']);
        }

        // El trigger fn_confirmar_pago_qr (concepto='carrera') crea pago_carrera_completa automáticamente.
        // Solo insertamos en pagofacil_transacciones. codigo_grupo = código de la carrera.
        $transId = DB::table('pagofacil_transacciones')->insertGetId([
            'transaction_id_api' => $apiTransId,
            'payment_number'     => $paymentNumber,
            'id_estudiante'      => $est->id_estudiante,
            'monto'              => $monto,
            'concepto'           => 'carrera',
            'codigo_grupo'       => $carrera->codigo,
            'estado'             => 'pendiente',
        ], 'id_transaccion_pf');

        session(['pf_qr_pc_' . $transId => $qrImage]);

        return redirect()->route('estudiante.pago.carrera', $transId);
    }

    // ── Página de pago del plan de carrera ────────────────────────────────────
    public function pagoCarrera(int $transId)
    {
        $trans = DB::table('pagofacil_transacciones')->where('id_transaccion_pf', $transId)->first();
        if (!$trans) abort(404);

        $userId = auth()->id();
        $est    = DB::table('estudiantes')->where('id_usuario', $userId)->first();
        if (!$est || (int) $trans->id_estudiante !== (int) $est->id_estudiante) abort(403);

        $costoTotal  = (float) DB::table('carreras')->where('codigo', $trans->codigo_grupo)->value('costo_carrera_completa');
        $esContado   = $costoTotal > 0 && $trans->monto >= round($costoTotal * 0.80, 2);
        $planNombre  = $esContado ? 'Pago Total (20% descuento)' : 'Enganche 30% + materias';

        return Inertia::render('Estudiante/PagoCarrera', [
            'transId'    => $transId,
            'qrImage'    => session('pf_qr_pc_' . $transId),
            'estado'     => $trans->estado,
            'planNombre' => $planNombre,
            'monto'      => (float) $trans->monto,
        ]);
    }

    // ── Polling estado del plan de carrera ────────────────────────────────────
    public function estadoPlan(int $transId)
    {
        $trans = DB::table('pagofacil_transacciones')->where('id_transaccion_pf', $transId)->first();
        if (!$trans) return response()->json(['estado' => 'error'], 404);

        if ($trans->estado === 'pagado') return response()->json(['estado' => 'pagado']);

        if ($trans->fecha_generacion && now()->diffInMinutes($trans->fecha_generacion) >= 15) {
            DB::table('pagofacil_transacciones')
                ->where('id_transaccion_pf', $transId)->where('estado', 'pendiente')
                ->update(['estado' => 'expirado']);
            return response()->json(['estado' => 'expirado']);
        }

        if ($trans->transaction_id_api) {
            try {
                $result = app(PagoFacilService::class)->consultarTransaccion((int) $trans->transaction_id_api);
                if (PagoFacilService::esPagado($result)) {
                    // El UPDATE dispara el trigger que crea pago_carrera_completa
                    DB::table('pagofacil_transacciones')
                        ->where('id_transaccion_pf', $transId)->where('estado', 'pendiente')
                        ->update(['estado' => 'pagado']);
                    $this->crearAfiliacion($trans);
                    return response()->json(['estado' => 'pagado']);
                }
            } catch (\Throwable) {}
        }

        return response()->json(['estado' => $trans->estado ?? 'pendiente']);
    }

    // ── Inscribirse en un grupo ────────────────────────────────────────────────
    public function inscribir(Request $request, int $idOferta)
    {
        $userId = auth()->id();
        $est    = DB::table('estudiantes')->where('id_usuario', $userId)->first();
        if (!$est) abort(403);

        if (!DB::table('matricula_unica')->where('id_estudiante', $est->id_estudiante)->exists()) {
            return back()->withErrors(['general' => 'Debes pagar la matrícula antes de inscribirte.']);
        }

        if (!DB::table('afiliaciones_estudiante')->where('id_estudiante', $est->id_estudiante)->where('estado', 'activo')->exists()) {
            return back()->withErrors(['general' => 'Debes elegir un plan de pago de carrera antes de inscribirte en materias.']);
        }

        $grupo = DB::table('grupos as g')
            ->join('materias as m',          'g.id_materia', '=', 'm.id_materia')
            ->join('periodos_dictado as pd', 'g.id_periodo', '=', 'pd.id_periodo')
            ->where('g.id_oferta', $idOferta)
            ->whereRaw('g.activo IS TRUE')
            ->select('g.*', 'm.nombre as materia_nombre', 'pd.nombre as periodo_nombre')
            ->first();

        if (!$grupo) abort(404);

        if (($grupo->vacantes_max - ($grupo->vacantes_ocupadas ?? 0)) <= 0) {
            return back()->withErrors(['general' => 'No hay vacantes disponibles en este grupo.']);
        }

        // Caso 1: inscripción en pendiente_matricula pero ya tiene pago_materia_suelta
        // (trigger corrió pero no actualizó el estado — activar manualmente)
        $inscConPagoExistente = DB::table('inscripciones as i')
            ->join('pago_materia_suelta as pms', 'i.id_inscripcion', '=', 'pms.id_inscripcion')
            ->where('i.id_estudiante', $est->id_estudiante)
            ->where('i.id_oferta', $idOferta)
            ->where('i.estado', 'pendiente_matricula')
            ->value('i.id_inscripcion');

        if ($inscConPagoExistente) {
            DB::table('inscripciones')
                ->where('id_inscripcion', $inscConPagoExistente)
                ->update(['estado' => 'activo']);
            return redirect()->route('estudiante.panel')
                ->with('success', 'Tu pago ya estaba registrado. ¡Inscripción activada correctamente!');
        }

        // Caso 2: inscripción en pendiente_matricula con QR expirado y sin pago → limpiar
        $idsPendientesExpiradas = DB::table('inscripciones as i')
            ->join('pagofacil_transacciones as t', 'i.id_inscripcion', '=', 't.id_inscripcion')
            ->where('i.id_estudiante', $est->id_estudiante)
            ->where('i.id_oferta', $idOferta)
            ->whereIn('i.estado', ['pendiente_matricula', 'pendiente_pago'])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('pago_materia_suelta')
                  ->whereColumn('pago_materia_suelta.id_inscripcion', 'i.id_inscripcion');
            })
            ->where(function ($q) {
                $q->where('t.estado', 'expirado')
                  ->orWhere(function ($q2) {
                      $q2->where('t.estado', 'pendiente')
                         ->where('t.fecha_generacion', '<', now()->subMinutes(15));
                  });
            })
            ->pluck('i.id_inscripcion');

        if ($idsPendientesExpiradas->isNotEmpty()) {
            DB::table('pagofacil_transacciones')
                ->whereIn('id_inscripcion', $idsPendientesExpiradas)
                ->where('estado', 'pendiente')
                ->update(['estado' => 'expirado']);
            DB::table('inscripciones')->whereIn('id_inscripcion', $idsPendientesExpiradas)->delete();
        }

        // Bloquear solo si hay inscripción activa o QR genuinamente en curso (<15 min)
        if (DB::table('inscripciones')
            ->where('id_estudiante', $est->id_estudiante)
            ->where('id_oferta', $idOferta)
            ->whereIn('estado', ['pendiente_matricula', 'pendiente_pago', 'activo'])
            ->exists()) {
            return back()->withErrors(['general' => 'Ya tienes una inscripción activa o en proceso para este grupo.']);
        }

        // Verificar si tiene plan CONTADO → inscripción directa sin QR
        $tieneContado = DB::table('pago_carrera_completa')
            ->where('id_estudiante', $est->id_estudiante)
            ->where('forma_pago', 'contado')
            ->where('estado', 'pagado')
            ->exists();

        if ($tieneContado) {
            try {
                DB::table('inscripciones')->insert([
                    'id_estudiante'    => $est->id_estudiante,
                    'id_oferta'        => $idOferta,
                    'estado'           => 'activo',
                    'fecha_inscripcion' => now(),
                ]);
                DB::table('grupos')->where('id_oferta', $idOferta)->increment('vacantes_ocupadas');
                return redirect()->route('estudiante.panel')->with('success', '¡Inscripción exitosa en ' . $grupo->materia_nombre . '!');
            } catch (\Throwable $e) {
                return back()->withErrors(['general' => 'Error al inscribirse: ' . $e->getMessage()]);
            }
        }

        // Plan CRÉDITO o MATERIA → requiere QR por materia
        $carrera    = DB::table('carreras')->where('id_carrera', $est->id_carrera_actual)->first();
        $costoTotal = (float) ($carrera?->costo_carrera_completa ?? 0);
        $totalMaterias = max(DB::table('malla_curricular')->where('id_carrera', $est->id_carrera_actual)->count(), 1);

        // Para crédito: cobrar el restante distribuido; para materia: precio estándar por materia
        $pagoC = DB::table('pago_carrera_completa')
            ->where('id_estudiante', $est->id_estudiante)
            ->whereIn('estado', ['parcial', 'pagado'])
            ->first();

        $montoMateria = $pagoC && $pagoC->forma_pago === 'credito'
            ? round(((float) $pagoC->monto_total - (float) $pagoC->monto_pagado) / $totalMaterias, 2)
            : round($costoTotal / $totalMaterias, 2);

        $montoMateria = max($montoMateria, 0.01);

        $user          = DB::table('usuarios')->where('id_usuario', $userId)->first();
        $paymentNumber = 'MATERIA-' . $est->id_estudiante . '-' . now()->timestamp;

        // Pre-crear inscripción en estado pendiente_matricula
        // El trigger actualiza a 'activo' al confirmar pago (concepto='materia')
        $idInscripcion = DB::table('inscripciones')->insertGetId([
            'id_estudiante'    => $est->id_estudiante,
            'id_oferta'        => $idOferta,
            'estado'           => 'pendiente_matricula',
            'fecha_inscripcion' => now(),
        ], 'id_inscripcion');

        // Generar QR
        try {
            $pf       = app(PagoFacilService::class);
            $qrResult = $pf->generarQR([
                'clientName'    => $user->nombre . ' ' . $user->apellido,
                'documentId'    => $user->dni ?? '00000000',
                'phoneNumber'   => $user->telefono ?: '70000000',
                'email'         => $user->email,
                'paymentNumber' => $paymentNumber,
                'clientCode'    => (string) $est->id_estudiante,
                'concepto'      => 'Materia: ' . $grupo->materia_nombre,
            ]);
        } catch (\Throwable $e) {
            DB::table('inscripciones')->where('id_inscripcion', $idInscripcion)->delete();
            return back()->withErrors(['general' => 'Error al conectar con el sistema de pago: ' . $e->getMessage()]);
        }

        $apiTransId = $qrResult['values']['transactionId'] ?? $qrResult['transactionId'] ?? null;
        $qrRaw      = $qrResult['values']['qrBase64'] ?? $qrResult['values']['qrImage'] ?? null;
        $qrImage    = $qrRaw ? (str_starts_with($qrRaw, 'data:') ? $qrRaw : 'data:image/png;base64,' . $qrRaw) : null;

        if (!$apiTransId) {
            DB::table('inscripciones')->where('id_inscripcion', $idInscripcion)->delete();
            return back()->withErrors(['general' => 'PagoFácil no devolvió ID de transacción.']);
        }

        // concepto='materia', codigo_grupo=código del grupo, id_inscripcion=FK
        // El trigger fn_confirmar_pago_qr activa la inscripción y crea pago_materia_suelta automáticamente
        $transId = DB::table('pagofacil_transacciones')->insertGetId([
            'transaction_id_api' => $apiTransId,
            'payment_number'     => $paymentNumber,
            'id_estudiante'      => $est->id_estudiante,
            'monto'              => $montoMateria,
            'concepto'           => 'materia',
            'codigo_grupo'       => $grupo->codigo_grupo,
            'id_inscripcion'     => $idInscripcion,
            'estado'             => 'pendiente',
        ], 'id_transaccion_pf');

        session(['pf_qr_ins_' . $transId => $qrImage]);

        return redirect()->route('estudiante.pago', $transId);
    }

    // ── Página de pago de materia ─────────────────────────────────────────────
    public function pagoInscripcion(int $transId)
    {
        $trans = DB::table('pagofacil_transacciones')->where('id_transaccion_pf', $transId)->first();
        if (!$trans) abort(404);

        $userId = auth()->id();
        $est    = DB::table('estudiantes')->where('id_usuario', $userId)->first();
        if (!$est || (int) $trans->id_estudiante !== (int) $est->id_estudiante) abort(403);

        $concepto = '';
        if ($trans->id_inscripcion) {
            $row = DB::table('inscripciones as i')
                ->join('grupos as g',           'i.id_oferta',   '=', 'g.id_oferta')
                ->join('materias as m',          'g.id_materia',  '=', 'm.id_materia')
                ->join('periodos_dictado as pd', 'g.id_periodo',  '=', 'pd.id_periodo')
                ->where('i.id_inscripcion', $trans->id_inscripcion)
                ->select('m.nombre as materia_nombre', 'pd.nombre as periodo_nombre')
                ->first();
            if ($row) $concepto = $row->materia_nombre . ' — ' . $row->periodo_nombre;
        }

        return Inertia::render('Estudiante/PagoInscripcion', [
            'transId'  => $transId,
            'qrImage'  => session('pf_qr_ins_' . $transId),
            'estado'   => $trans->estado,
            'concepto' => $concepto,
            'monto'    => (float) $trans->monto,
        ]);
    }

    // ── Polling estado de pago de materia ─────────────────────────────────────
    public function estadoInscripcion(int $transId)
    {
        $trans = DB::table('pagofacil_transacciones')->where('id_transaccion_pf', $transId)->first();
        if (!$trans) return response()->json(['estado' => 'error'], 404);

        if ($trans->estado === 'pagado') return response()->json(['estado' => 'pagado']);

        if ($trans->fecha_generacion && now()->diffInMinutes($trans->fecha_generacion) >= 15) {
            DB::table('pagofacil_transacciones')
                ->where('id_transaccion_pf', $transId)->where('estado', 'pendiente')
                ->update(['estado' => 'expirado']);
            // Limpiar inscripción pendiente
            if ($trans->id_inscripcion) {
                DB::table('inscripciones')
                    ->where('id_inscripcion', $trans->id_inscripcion)
                    ->where('estado', 'pendiente_matricula')
                    ->delete();
            }
            return response()->json(['estado' => 'expirado']);
        }

        if ($trans->transaction_id_api) {
            try {
                $result = app(PagoFacilService::class)->consultarTransaccion((int) $trans->transaction_id_api);
                if (PagoFacilService::esPagado($result)) {
                    // UPDATE dispara trigger fn_confirmar_pago_qr (concepto='materia')
                    // El trigger activa la inscripción y crea pago_materia_suelta automáticamente
                    DB::table('pagofacil_transacciones')
                        ->where('id_transaccion_pf', $transId)->where('estado', 'pendiente')
                        ->update(['estado' => 'pagado']);
                    // Actualizar vacantes manualmente (el trigger no lo hace)
                    if ($trans->id_inscripcion) {
                        $insc = DB::table('inscripciones')->where('id_inscripcion', $trans->id_inscripcion)->first();
                        if ($insc) DB::table('grupos')->where('id_oferta', $insc->id_oferta)->increment('vacantes_ocupadas');
                    }
                    return response()->json(['estado' => 'pagado']);
                }
            } catch (\Throwable) {}
        }

        return response()->json(['estado' => $trans->estado ?? 'pendiente']);
    }

    // ── Helper: crear afiliación tras confirmar pago de carrera ───────────────
    private function crearAfiliacion(object $trans): void
    {
        if (DB::table('afiliaciones_estudiante')->where('id_estudiante', $trans->id_estudiante)->where('estado', 'activo')->exists()) {
            return;
        }
        // codigo_grupo contiene el código de la carrera
        $idCarrera = DB::table('carreras')->where('codigo', $trans->codigo_grupo)->value('id_carrera');
        if (!$idCarrera) return;

        DB::table('afiliaciones_estudiante')->insert([
            'id_estudiante' => $trans->id_estudiante,
            'id_carrera'    => $idCarrera,
            'tipo_programa' => 'carrera',
            'fecha_inicio'  => now()->toDateString(),
            'estado'        => 'activo',
        ]);
    }
}
