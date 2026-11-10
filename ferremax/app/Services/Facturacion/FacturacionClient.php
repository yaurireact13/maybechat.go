<?php

namespace App\Services\Facturacion;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Cliente del ERP hacia el MICROSERVICIO de facturacion electronica.
 *
 * El ERP NO conoce SUNAT/DIAN/SII: solo habla un contrato REST neutral con el
 * servicio de facturacion. Cambiar de pais o de proveedor no afecta al ERP.
 *
 * Recomendado: el ERP escribe primero en su propia tabla `outbox` y un job
 * llama a este cliente con reintentos, para no bloquear la venta si el servicio
 * de facturacion esta temporalmente caido (desacople por mensajeria/cola).
 */
class FacturacionClient
{
    public function __construct(
        private readonly ?string $baseUrl = null,
        private readonly ?string $token = null,
    ) {}

    private function http()
    {
        return Http::baseUrl($this->baseUrl ?? config('facturacion.base_url'))
            ->withToken($this->token ?? config('facturacion.token'))
            ->acceptJson()
            ->timeout(config('facturacion.timeout', 30))
            ->retry(3, 500); // reintentos ante fallos de red
    }

    /** Emite una factura/boleta. La clave de idempotencia evita duplicados. */
    public function emitirFactura(array $payload, ?string $claveIdempotencia = null): array
    {
        $resp = $this->http()
            ->withHeaders(['Idempotency-Key' => $claveIdempotencia ?? (string) Str::uuid()])
            ->post('/api/v1/facturas', $payload);

        return $resp->json();
    }

    public function emitirNotaCredito(array $payload): array
    {
        return $this->http()->post('/api/v1/notas-credito', $payload)->json();
    }

    public function anular(string $comprobanteId, string $motivo): array
    {
        return $this->http()->post("/api/v1/comprobantes/{$comprobanteId}/anular", compact('motivo'))->json();
    }

    public function consultarEstado(string $comprobanteId): array
    {
        return $this->http()->get("/api/v1/comprobantes/{$comprobanteId}/estado")->json();
    }
}
