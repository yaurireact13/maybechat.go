<?php

declare(strict_types=1);

namespace App\Domain\Facturacion\Port;

use App\Domain\Facturacion\Model\Comprobante;

/**
 * Puerto de auditoria: registra cada transicion y llamada al organismo
 * (quien, cuando, request, response, codigo). Traza completa e inmutable.
 */
interface RegistroAuditoria
{
    public function registrar(
        Comprobante $comprobante,
        string $accion,
        array $metadatos = [],
    ): void;
}
