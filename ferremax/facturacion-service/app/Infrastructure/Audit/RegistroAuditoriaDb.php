<?php

declare(strict_types=1);

namespace App\Infrastructure\Audit;

use App\Domain\Facturacion\Model\Comprobante;
use App\Domain\Facturacion\Port\RegistroAuditoria;
use Illuminate\Support\Facades\DB;

/**
 * Auditoria append-only en base de datos: una fila por accion/transicion.
 * Guarda tambien los eventos del agregado (patron util para Outbox).
 */
final class RegistroAuditoriaDb implements RegistroAuditoria
{
    public function registrar(Comprobante $comprobante, string $accion, array $metadatos = []): void
    {
        DB::table('comprobante_eventos')->insert([
            'comprobante_id' => $comprobante->id,
            'accion'         => $accion,
            'estado'         => $comprobante->estado()->value,
            'metadatos'      => json_encode($metadatos, JSON_UNESCAPED_UNICODE),
            'creado_en'      => now(),
        ]);
    }
}
