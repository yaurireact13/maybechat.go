<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Domain\Facturacion\Port\AlmacenDocumentos;
use Illuminate\Support\Facades\Storage;

/**
 * Adaptador de almacenamiento (S3, disco local, GCS via Flysystem).
 * Guarda XML firmado, CDR y PDF. Implementa el puerto AlmacenDocumentos.
 */
final class AlmacenDocumentosFilesystem implements AlmacenDocumentos
{
    public function __construct(private readonly string $disk = 's3') {}

    public function guardar(string $ruta, string $contenido, string $tipoMime): string
    {
        Storage::disk($this->disk)->put($ruta, $contenido);
        return $ruta; // el "documentoId" es la ruta relativa
    }

    public function obtener(string $documentoId): string
    {
        return (string) Storage::disk($this->disk)->get($documentoId);
    }

    public function url(string $documentoId, int $expiraSegundos = 600): string
    {
        return Storage::disk($this->disk)->temporaryUrl($documentoId, now()->addSeconds($expiraSegundos));
    }
}
