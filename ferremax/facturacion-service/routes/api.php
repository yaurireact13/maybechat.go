<?php

use App\Infrastructure\Http\Controllers\FacturaController;
use App\Infrastructure\Http\Controllers\NotaCreditoController;
use App\Infrastructure\Http\Middleware\Idempotencia;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API del microservicio de facturacion (consumida por el ERP)
|--------------------------------------------------------------------------
| Autenticacion por token de servicio (auth:sanctum o API key del ERP).
| Idempotencia en las operaciones de emision.
*/

Route::prefix('api/v1')->middleware(['auth:sanctum'])->group(function () {

    Route::post('/facturas', [FacturaController::class, 'emitir'])
        ->middleware(Idempotencia::class)
        ->name('facturas.emitir');

    Route::post('/notas-credito', [NotaCreditoController::class, 'emitir'])
        ->middleware(Idempotencia::class)
        ->name('notas-credito.emitir');

    Route::post('/comprobantes/{id}/anular', [FacturaController::class, 'anular'])
        ->name('comprobantes.anular');

    Route::get('/comprobantes/{id}/estado', [FacturaController::class, 'estado'])
        ->name('comprobantes.estado');
});
