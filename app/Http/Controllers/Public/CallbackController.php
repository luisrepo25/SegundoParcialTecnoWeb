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

        // Actualizar a 'pagado' — el trigger trg_pagofacil_confirmacion hace el resto
        $afectadas = DB::table('pagofacil_transacciones')
            ->where('transaction_id_api', $transactionId)
            ->where('estado', 'pendiente')
            ->update(['estado' => 'pagado']);

        if ($afectadas > 0) {
            // Activar cuenta del estudiante (trigger solo maneja matricula_unica)
            $trans = DB::table('pagofacil_transacciones')
                ->where('transaction_id_api', $transactionId)
                ->first();

            if ($trans?->id_estudiante) {
                $est = DB::table('estudiantes')->where('id_estudiante', $trans->id_estudiante)->first();
                if ($est) {
                    DB::table('usuarios')
                        ->where('id_usuario', $est->id_usuario)
                        ->where('activo', false)
                        ->update(['activo' => true]);
                }
            }
        }

        return response()->json(['ok' => true], 200);
    }
}
