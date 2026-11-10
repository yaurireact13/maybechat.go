<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\UseCase\EmitirNotaCredito\EmitirNotaCreditoHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotaCreditoController
{
    public function emitir(Request $request, EmitirNotaCreditoHandler $handler): JsonResponse
    {
        $datos = $request->validate([
            'pais' => 'required|string|size:2',
            'serie' => 'required|string',
            'numero' => 'required|string',
            'comprobanteAfectadoId' => 'required|string',
            'moneda' => 'required|string',
            'fechaEmision' => 'required|date',
            'emisor' => 'required|array',
            'receptor' => 'required|array',
            'lineas' => 'required|array|min:1',
        ]);

        return response()->json($handler($datos), 201);
    }
}
