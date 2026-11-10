<?php

declare(strict_types=1);

namespace App\Domain\Facturacion\Port\Resultado;

use App\Domain\Facturacion\Enum\EstadoComprobante;

/**
 * Resultado neutral que devuelve CUALQUIER adaptador de pais tras emitir/anular.
 * Traduce la respuesta local (CDR de SUNAT, CUFE de DIAN, CAE de ARCA, timbre del PAC)
 * a un formato comun que el dominio y la aplicacion entienden.
 */
final readonly class ResultadoEmision
{
    public function __construct(
        public EstadoComprobante $estado,
        public ?string $codigoUnico = null,   // CUFE / CDR hash / CAE / UUID
        public ?string $xmlFirmado = null,     // contenido XML (o referencia)
        public ?string $respuestaCruda = null, // CDR/acuse original para auditoria
        public ?string $mensaje = null,
        public ?string $codigoError = null,
        public bool $reintentable = false,     // true = fallo tecnico; false = rechazo de negocio
    ) {}

    public static function aceptado(string $codigoUnico, string $xmlFirmado, ?string $respuestaCruda = null): self
    {
        return new self(EstadoComprobante::ACEPTADO, $codigoUnico, $xmlFirmado, $respuestaCruda);
    }

    public static function rechazado(string $codigoError, string $mensaje): self
    {
        return new self(EstadoComprobante::RECHAZADO, mensaje: $mensaje, codigoError: $codigoError, reintentable: false);
    }

    public static function errorTecnico(string $mensaje): self
    {
        return new self(EstadoComprobante::ERROR, mensaje: $mensaje, reintentable: true);
    }
}
