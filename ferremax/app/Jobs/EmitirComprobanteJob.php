<?php

namespace App\Jobs;

use App\Models\Venta;
use App\Services\Facturacion\EmisorComprobante;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Emite el comprobante electronico de una venta de forma asincrona,
 * para no bloquear el cierre de la venta en el POS. Reintenta ante fallos
 * tecnicos con backoff exponencial.
 */
class EmitirComprobanteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $ventaId) {}

    /** Backoff exponencial en segundos: 10, 30, 60, 120, 300. */
    public function backoff(): array
    {
        return [10, 30, 60, 120, 300];
    }

    public function handle(EmisorComprobante $emisor): void
    {
        $venta = Venta::withoutGlobalScope('empresa')->find($this->ventaId);

        if (! $venta || $venta->estado === 'anulada') {
            return;
        }

        $ce = $emisor->emitir($venta);

        // Si fue un fallo tecnico, se relanza para que el worker reintente.
        if ($ce->estado === 'error' && $this->attempts() < $this->tries) {
            $this->release($this->backoff()[$this->attempts() - 1] ?? 300);
        }
    }
}
