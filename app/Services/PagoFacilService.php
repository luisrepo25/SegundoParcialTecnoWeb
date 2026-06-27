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
            $resp = Http::withHeaders([
                'tcTokenService' => config('services.pagofacil.token_service'),
                'tcTokenSecret'  => config('services.pagofacil.token_secret'),
            ])->post(self::BASE . '/login');

            $data = $resp->json();

            // PagoFácil puede devolver el token en distintas claves
            return $data['token'] ?? $data['tcToken'] ?? $data['access_token'] ?? throw new \RuntimeException('PagoFácil: token no recibido. Respuesta: ' . $resp->body());
        });
    }

    // ── Generar QR ────────────────────────────────────────────────────────────
    public function generarQR(array $params): array
    {
        $resp = Http::withToken($this->getToken())
            ->post(self::BASE . '/generate-qr', array_merge([
                'paymentMethod' => 34,
                'documentType'  => 1,
                'currency'      => 2,
                'amount'        => 0.01, // valor ficticio para pruebas; el monto real queda en DB
                'callbackUrl'   => config('services.pagofacil.callback_url'),
            ], $params));

        if (!$resp->successful()) {
            throw new \RuntimeException('PagoFácil generarQR error: ' . $resp->body());
        }

        return $resp->json();
    }

    // ── Consultar estado de transacción ───────────────────────────────────────
    public function consultarTransaccion(int $transactionId): array
    {
        $resp = Http::withToken($this->getToken())
            ->post(self::BASE . '/query-transaction', ['transactionId' => $transactionId]);

        return $resp->json() ?? [];
    }

    // ── Determinar si un resultado es "pagado" ────────────────────────────────
    public static function esPagado(array $result): bool
    {
        $status = $result['paymentStatus'] ?? $result['status'] ?? null;
        $date   = $result['paymentDate']   ?? null;
        return $date !== null || in_array($status, [2, 5], true);
    }
}
