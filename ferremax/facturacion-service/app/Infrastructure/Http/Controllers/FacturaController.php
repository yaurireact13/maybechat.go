<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\UseCase\EmitirFactura\EmitirFacturaCommand;
use App\Application\UseCase\EmitirFactura\EmitirFacturaHandler;
use App\Application\UseCase\AnularComprobante\AnularComprobanteHandler;
use App\Application\UseCase\ConsultarEstado\ConsultarEstadoHandler;
use App\Domain\Facturacion\Exception\FacturacionException;
use App\Infrastructure\Http\Requests\EmitirFacturaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ADAPTADOR DE ENTRADA (Driving adapter): expone los casos de uso via REST.
 * El controlador es delgado: valida, arma el comando y delega al handler.
 */
final class FacturaController
{
    public function emitir(EmitirFacturaRequest $request, EmitirFacturaHandler $handler): JsonResponse
    {
        $d = $request->validated();

        $cmd = new EmitirFacturaCommand(
            pais: $d['pais'], tipo: $d['tipo'], serie: $d['serie'], numero: $d['numero'],
            emisor: $d['emisor'], receptor: $d['receptor'], lineas: $d['lineas'],
            moneda: $d['moneda'], fechaEmision: $d['fechaEmision'],
            claveIdempotencia: $request->header('Idempotency-Key', ''),
        );

        try {
            $resp = $handler($cmd);
        } catch (FacturacionException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($resp, $resp->estado === 'aceptado' ? 201 : 202);
    }

    public function anular(string $id, Request $request, AnularComprobanteHandler $handler): JsonResponse
    {
        $motivo = (string) $request->input('motivo', 'Anulacion solicitada por el emisor');
        return response()->json($handler($id, $motivo));
    }

    public function estado(string $id, ConsultarEstadoHandler $handler): JsonResponse
    {
        return response()->json($handler($id));
    }
}
