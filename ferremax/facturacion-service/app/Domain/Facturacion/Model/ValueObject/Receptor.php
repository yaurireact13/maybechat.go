<?php

declare(strict_types=1);

namespace App\Domain\Facturacion\Model\ValueObject;

/**
 * Receptor (cliente). Objeto de valor inmutable.
 * "tipoDocumento" y "numeroDocumento" son genericos; cada adaptador los mapea
 * al catalogo local (p.ej. SUNAT: 6=RUC, 1=DNI).
 */
final readonly class Receptor
{
    public function __construct(
        public string $tipoDocumento,
        public string $numeroDocumento,
        public string $razonSocial,
        public ?string $direccion = null,
        public ?string $email = null,
    ) {}
}
