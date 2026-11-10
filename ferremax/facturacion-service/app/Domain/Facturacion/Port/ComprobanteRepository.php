<?php

declare(strict_types=1);

namespace App\Domain\Facturacion\Port;

use App\Domain\Facturacion\Model\Comprobante;

/**
 * Puerto de persistencia (Repository, DDD). El dominio no sabe si detras hay
 * MySQL, Postgres o memoria. La implementacion vive en Infrastructure/Persistence.
 */
interface ComprobanteRepository
{
    public function guardar(Comprobante $comprobante): void;

    public function porId(string $id): ?Comprobante;

    /** Idempotencia: recupera un comprobante ya emitido por su clave de negocio. */
    public function porClaveNegocio(string $pais, string $serie, string $numero, string $rucEmisor): ?Comprobante;
}
