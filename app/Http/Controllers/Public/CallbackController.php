<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallbackController extends Controller
{
    public function handle(Request $request)
    {
        $transactionId = $request->input('transactionId');

        if (!$transactionId) {
            return response()->json(['error' => 'transactionId required'], 400);
        }

        // Un solo UPDATE dispara el trigger fn_confirmar_pago_qr que maneja toda la lógica:
        // - concepto='matricula' → inserta matricula_unica
        // - concepto='carrera'   → inserta pago_carrera_completa (contado o credito según monto)
        // - concepto='materia'   → inserta pago_materia_suelta + activa inscripción
        // - concepto='cuota'     → marca cuota como pagada
        $afectadas = DB::table('pagofacil_transacciones')
            ->where('transaction_id_api', $transactionId)
            ->where('estado', 'pendiente')
            ->update(['estado' => 'pagado']);

        if ($afectadas > 0) {
            $trans = DB::table('pagofacil_transacciones')
                ->where('transaction_id_api', $transactionId)
                ->first();

            if ($trans?->id_estudiante) {
                $est = DB::table('estudiantes')->where('id_estudiante', $trans->id_estudiante)->first();

                if ($est) {
                    // Activar cuenta en pago de matrícula pública
                    if ($trans->concepto === 'matricula') {
                        DB::table('usuarios')
                            ->where('id_usuario', $est->id_usuario)
                            ->where('activo', false)
                            ->update(['activo' => true]);
                    }

                    // Crear afiliación tras pago de carrera (lo único que el trigger no hace)
                    if ($trans->concepto === 'carrera' && $trans->codigo_grupo) {
                        $idCarrera = DB::table('carreras')->where('codigo', $trans->codigo_grupo)->value('id_carrera');
                        if ($idCarrera && !DB::table('afiliaciones_estudiante')
                            ->where('id_estudiante', $trans->id_estudiante)
                            ->where('estado', 'activo')
                            ->exists()) {
                            DB::table('afiliaciones_estudiante')->insert([
                                'id_estudiante' => $trans->id_estudiante,
                                'id_carrera'    => $idCarrera,
                                'tipo_programa' => 'carrera',
                                'fecha_inicio'  => now()->toDateString(),
                                'estado'        => 'activo',
                            ]);
                        }
                    }

                    // Actualizar vacantes tras pago de materia (el trigger activa la inscripción,
                    // pero el conteo de vacantes_ocupadas lo manejamos aquí)
                    if ($trans->concepto === 'materia' && $trans->id_inscripcion) {
                        $insc = DB::table('inscripciones')->where('id_inscripcion', $trans->id_inscripcion)->first();
                        if ($insc) {
                            DB::table('grupos')->where('id_oferta', $insc->id_oferta)->increment('vacantes_ocupadas');
                        }
                    }
                }
            }
        }

        return response()->json(['ok' => true], 200);
    }
}
