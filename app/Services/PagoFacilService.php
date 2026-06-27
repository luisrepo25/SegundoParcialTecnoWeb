<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PagoFacilService
{
    private const BASE         = 'https://masterqr.pagofacil.com.bo/api/services/v2';
    private const CACHE_TOKEN  = 'pagofacil_token';
    private const TOKEN_TTL    = 49 * 60; // 49 minutos en segundos

    // ── Autenticación con caché de 49 min ─────────────────────────────────────
    public function getToken(): string
    {
        return Cache::remember(self::CACHE_TOKEN, self::TOKEN_TTL, function () {
            $resp = Http::withoutVerifying()   // SSL no disponible en Windows dev
                ->withHeaders([
                    'tcTokenService' => config('services.pagofacil.token_service'),
                    'tcTokenSecret'  => config('services.pagofacil.token_secret'),
                ])->post(self::BASE . '/login');

            $data = $resp->json();

            // La API devuelve: { "values": { "accessToken": "..." } }
            return $data['values']['accessToken']
                ?? $data['token']
                ?? $data['tcToken']
                ?? $data['access_token']
                ?? throw new \RuntimeException('PagoFácil: token no recibido. Respuesta: ' . $resp->body());
        });
    }

    // ── Generar QR ────────────────────────────────────────────────────────────
    public function generarQR(array $params): array
    {
        $amount = 0.01; // valor ficticio para pruebas; monto real queda en pagofacil_transacciones

        $base = [
            'paymentMethod' => 34,
            'documentType'  => 1,
            'currency'      => 2,
            'amount'        => $amount,
            'callbackUrl'   => config('services.pagofacil.callback_url'),
            'orderDetail'   => [[
                'serial'   => 1,
                'product'  => $params['concepto'] ?? 'Matrícula Instituto San Pablo',
                'quantity' => 1,
                'price'    => $amount,
                'discount' => 0,
                'total'    => $amount,
            ]],
        ];

        // Quitar 'concepto' de $params si existe (no es un campo de la API)
        unset($params['concepto']);

        $resp = Http::withoutVerifying()
            ->withToken($this->getToken())
            ->post(self::BASE . '/generate-qr', array_merge($base, $params));

        if (!$resp->successful()) {
            throw new \RuntimeException('PagoFácil generarQR error: ' . $resp->body());
        }

        return $resp->json();
    }

    // ── Consultar estado de transacción ───────────────────────────────────────
    public function consultarTransaccion(int $transactionId): array
    {
        $resp = Http::withoutVerifying()
            ->withToken($this->getToken())
            ->post(self::BASE . '/query-transaction', ['pagofacilTransactionId' => $transactionId]);

        $data = $resp->json() ?? [];
        // La API envuelve los datos en 'values' — igual que login y generate-qr
        return $data['values'] ?? $data;
    }

    // ── Determinar si un resultado es "pagado" ────────────────────────────────
    public static function esPagado(array $result): bool
    {
        $status = $result['paymentStatus'] ?? $result['status'] ?? null;
        $date   = $result['paymentDate']   ?? $result['paymentDateStr'] ?? null;
        return $date !== null || in_array($status, [2, 5], true);
    }
}
