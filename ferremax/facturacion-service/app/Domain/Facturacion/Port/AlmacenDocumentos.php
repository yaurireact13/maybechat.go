<?php

declare(strict_types=1);

namespace App\Domain\Facturacion\Port;

/**
 * Puerto para almacenar y recuperar documentos generados
 * (XML firmado, CDR/acuse del organismo, PDF de representacion impresa).
 * Implementable sobre S3, disco local, GCS, etc.
 */
interface AlmacenDocumentos
{
    /** Devuelve el identificador/URI del documento almacenado. */
    public function guardar(string $ruta, string $contenido, string $tipoMime): string;

    public function obtener(string $documentoId): string;

    public function url(string $documentoId, int $expiraSegundos = 600): string;
}
