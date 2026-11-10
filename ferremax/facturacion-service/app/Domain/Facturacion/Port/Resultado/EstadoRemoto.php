<?php

declare(strict_types=1);

namespace App\Domain\Facturacion\Port\Resultado;

use App\Domain\Facturacion\Enum\EstadoComprobante;

/** Respuesta neutral de una consulta de estado ante el organismo fiscal. */
final readonly class EstadoRemoto
{
    public function __construct(
        public EstadoComprobante $estado,
        public ?string $codigoUnico = null,
        public ?string $mensaje = null,
        public ?string $respuestaCruda = null,
    ) {}
}
