<?php

declare(strict_types=1);

namespace App\Domain\Facturacion\Exception;

/**
 * El organismo fiscal RECHAZO el comprobante (error de negocio, NO se reintenta).
 * Se diferencia de un fallo tecnico (timeout, red) que si es reintentable.
 */
class ComprobanteRechazadoException extends FacturacionException
{
    public function __construct(
        public readonly string $codigoOrganismo,
        string $mensaje,
    ) {
        parent::__construct($mensaje);
    }
}
