<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers\Peru\Sunat;

/**
 * Lee y parsea el CDR (Constancia de Recepcion) que devuelve SUNAT.
 * codigo 0 = aceptado; 2000-3999 = rechazo; 4000+ = observacion (aceptado con notas).
 */
final class LectorCdr
{
    public function leer(string $cdrZip): CdrRespuesta
    {
        // TODO: descomprimir ZIP, leer applicationResponse UBL y extraer ResponseCode/Description.
        return new CdrRespuesta(0, 'La Factura ha sido aceptada', hash('sha256', $cdrZip), $cdrZip);
    }
}

/** DTO simple del contenido del CDR. */
final readonly class CdrRespuesta
{
    public function __construct(
        public int $codigo,
        public string $descripcion,
        public string $hashCpe,
        public string $crudo,
    ) {}

    public function esObservacion(): bool
    {
        return $this->codigo >= 4000;
    }
}
