<?php

declare(strict_types=1);

namespace App\Domain\Facturacion\Model\ValueObject;

/**
 * Emisor (la empresa que factura). Objeto de valor inmutable.
 * El "identificadorFiscal" es generico: RUC (PE), NIT (CO), RUT (CL), CUIT (AR), RFC (MX).
 */
final readonly class Emisor
{
    public function __construct(
        public string $identificadorFiscal,
        public string $razonSocial,
        public ?string $nombreComercial = null,
        public ?string $direccion = null,
        public ?string $ubigeo = null,
    ) {}
}
