<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Services\Facturacion\EmisorComprobante;
use App\Services\Facturacion\FacturacionClient;

class ComprobanteElectronicoController extends Controller
{
    /** Reintenta la emision del comprobante de una venta (manual). */
    public function reintentar(Venta $venta, EmisorComprobante $emisor)
    {
        if ($venta->estado === 'anulada') {
            return back()->with('error', 'No se puede emitir el comprobante de una venta anulada.');
        }
        if (! in_array($venta->tipo_comprobante, ['boleta', 'factura'], true)) {
            return back()->with('error', 'Solo las boletas y facturas requieren comprobante electrónico.');
        }

        $ce = $emisor->emitir($venta);

        return back()->with(
            $ce->estado === 'aceptado' ? 'ok' : 'error',
            $ce->estado === 'aceptado'
                ? "Comprobante {$ce->serie}-{$ce->numero} aceptado. Código: {$ce->codigo_unico}"
                : "No se pudo emitir: {$ce->mensaje}"
        );
    }

    /** Consulta el estado del comprobante ante el organismo (modo real). */
    public function consultar(Venta $venta, FacturacionClient $client)
    {
        $ce = $venta->comprobanteElectronico;
        if (! $ce || ! $ce->comprobante_id_externo) {
            return back()->with('error', 'Este comprobante aún no ha sido enviado.');
        }
        if ($ce->modo === 'simulado') {
            return back()->with('ok', 'Comprobante en modo simulado: estado ' . $ce->estado . '.');
        }

        $resp = $client->consultarEstado($ce->comprobante_id_externo);
        $ce->update(['estado' => $resp['estadoRemoto'] ?? $ce->estado, 'respuesta' => $resp]);

        return back()->with('ok', 'Estado actualizado: ' . ($resp['estadoRemoto'] ?? $ce->estado) . '.');
    }
}
