<?php

namespace App\Services\Facturacion;

use App\Models\ComprobanteElectronico;
use App\Models\Venta;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Orquesta la emision del comprobante electronico de una venta.
 *
 * - Construye el payload NEUTRAL (agnostico de pais) a partir de la venta.
 * - En modo "simulado" acepta el comprobante localmente (sin red).
 * - En modo "real" llama al microservicio via FacturacionClient con idempotencia.
 * - Guarda el estado en la tabla comprobantes_electronicos.
 */
class EmisorComprobante
{
    public function __construct(private readonly FacturacionClient $client) {}

    public function emitir(Venta $venta): ComprobanteElectronico
    {
        $venta->loadMissing(['detalles.producto', 'cliente', 'empresa']);
        $empresa = $venta->empresa;

        // Ajustes por empresa (con fallback a la config global del ERP).
        $serieFactura = $empresa->factura_serie_factura ?? config('facturacion.series.factura', 'F001');
        $serieBoleta  = $empresa->factura_serie_boleta ?? config('facturacion.series.boleta', 'B001');

        // Registro de seguimiento (idempotente: uno por venta)
        $ce = ComprobanteElectronico::firstOrNew(['venta_id' => $venta->id]);
        $ce->empresa_id = $venta->empresa_id;
        $ce->pais  = $empresa->factura_pais ?? config('facturacion.pais', 'PE');
        $ce->tipo  = $venta->tipo_comprobante;
        $ce->serie = $ce->serie ?: ($ce->tipo === 'factura' ? $serieFactura : $serieBoleta);
        $ce->numero = $ce->numero ?: $this->correlativo($venta, $ce->serie);
        $ce->modo   = $empresa->factura_modo ?? config('facturacion.modo', 'simulado');
        $ce->intentos = (int) $ce->intentos + 1;
        $ce->save();

        // Si ya fue aceptado, no reintentar (idempotencia de negocio).
        if (in_array($ce->estado, ['aceptado', 'observado'], true)) {
            return $ce;
        }

        try {
            if ($ce->modo === 'simulado') {
                $this->aplicarSimulado($ce);
            } else {
                $this->aplicarReal($ce, $venta, $empresa);
            }
        } catch (Throwable $e) {
            $ce->estado = 'error';
            $ce->mensaje = 'Fallo al emitir: ' . $e->getMessage();
        }

        $ce->save();

        return $ce;
    }

    private function aplicarSimulado(ComprobanteElectronico $ce): void
    {
        // Emite un comprobante aceptado "de mentira" para probar el flujo end-to-end.
        $ce->estado = 'aceptado';
        $ce->codigo_unico = strtoupper(Str::random(8)) . '-SIM';
        $ce->comprobante_id_externo = (string) Str::uuid();
        $ce->mensaje = 'Aceptado (modo simulado, sin envio al organismo).';
        $ce->respuesta = ['simulado' => true, 'emitido_en' => now()->toIso8601String()];
    }

    private function aplicarReal(ComprobanteElectronico $ce, Venta $venta, $empresa): void
    {
        $payload = $this->construirPayload($ce, $venta, $empresa);

        // Clave de idempotencia estable por venta: evita duplicados ante reintentos.
        $resp = $this->client->emitirFactura($payload, 'venta-' . $venta->id);

        $estado = $resp['estado'] ?? 'error';
        $ce->estado = in_array($estado, ['aceptado', 'observado', 'rechazado'], true) ? $estado : 'error';
        $ce->codigo_unico = $resp['codigoUnico'] ?? null;
        $ce->comprobante_id_externo = $resp['comprobanteId'] ?? null;
        $ce->xml_url = $resp['xmlUrl'] ?? null;
        $ce->pdf_url = $resp['pdfUrl'] ?? null;
        $ce->mensaje = $resp['mensaje'] ?? null;
        $ce->respuesta = $resp;
    }

    private function construirPayload(ComprobanteElectronico $ce, Venta $venta, $empresa): array
    {
        $cliente = $venta->cliente;
        $cfg = $empresa->facturacionConfig;   // config del emisor (puede ser null)

        $payload = [
            'pais'     => $ce->pais,
            'ambiente' => $cfg->ambiente ?? 'beta',
            'tipo'     => $venta->tipo_comprobante,
            'serie'    => $ce->serie,
            'numero'   => $ce->numero,
            'moneda'   => $empresa->moneda === 'S/' ? 'PEN' : ($empresa->moneda ?? 'PEN'),
            'fechaEmision' => $venta->fecha->toIso8601String(),
            'emisor' => [
                'identificadorFiscal' => $cfg->ruc ?? $empresa->ruc ?? '00000000000',
                'razonSocial'         => $cfg->razon_social ?? $empresa->nombre,
                'nombreComercial'     => $cfg->nombre_comercial ?? null,
                'direccion'           => $cfg->direccion_fiscal ?? $empresa->direccion,
                'ubigeo'              => $cfg->ubigeo ?? null,
            ],
            'receptor' => [
                'tipoDocumento'   => $cliente->tipo_documento ?? 'DNI',
                'numeroDocumento' => $cliente->numero_documento ?? '00000000',
                'razonSocial'     => $cliente->nombre ?? 'Cliente Varios',
                'direccion'       => $cliente->direccion ?? null,
                'email'           => $cliente->email ?? null,
            ],
            'lineas' => $venta->detalles->map(fn ($d) => [
                'descripcion'   => $d->producto->nombre ?? 'Producto',
                'cantidad'      => (float) $d->cantidad,
                'precioUnitario' => (float) $d->precio,
                'tasaImpuesto'  => (float) ($empresa->impuesto ?? 18),
                'codigo'        => $d->producto->codigo ?? null,
            ])->all(),
        ];

        // Credenciales y certificado del emisor: el microservicio los necesita para
        // firmar y autenticarse ante el organismo (SUNAT). Solo se envian en modo real.
        if ($cfg) {
            $payload['credenciales'] = [
                'usuarioSol' => $cfg->usuario_sol,
                'claveSol'   => $cfg->clave_sol,   // se descifra al acceder al atributo
            ];

            if ($cfg->tieneCertificado() && Storage::disk('local')->exists($cfg->certificado_path)) {
                $payload['certificado'] = [
                    'nombre'          => $cfg->certificado_nombre,
                    'contenidoBase64' => base64_encode(Storage::disk('local')->get($cfg->certificado_path)),
                    'clave'           => $cfg->clave_certificado,
                    'vence'           => optional($cfg->certificado_vence)->toDateString(),
                ];
            }
        }

        return $payload;
    }

    private function correlativo(Venta $venta, string $serie): string
    {
        $ultimo = ComprobanteElectronico::where('empresa_id', $venta->empresa_id)
            ->where('serie', $serie)->max('numero');

        return str_pad((string) (((int) $ultimo) + 1), 8, '0', STR_PAD_LEFT);
    }
}
