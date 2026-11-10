<?php

declare(strict_types=1);

namespace App\Domain\Facturacion\Enum;

/**
 * Tipo de comprobante en lenguaje del NEGOCIO (agnostico del pais).
 * Cada adaptador traduce estos valores al catalogo local
 * (p.ej. SUNAT: 01=Factura, 03=Boleta, 07=Nota de credito).
 */
enum TipoComprobante: string
{
    case FACTURA        = 'factura';
    case BOLETA         = 'boleta';        // consumidor final
    case NOTA_CREDITO   = 'nota_credito';
    case NOTA_DEBITO    = 'nota_debito';
}
