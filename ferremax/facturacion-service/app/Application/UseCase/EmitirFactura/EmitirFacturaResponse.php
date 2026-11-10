<?php

declare(strict_types=1);

namespace App\Application\UseCase\EmitirFactura;

use App\Domain\Facturacion\Model\Comprobante;

/** Respuesta neutral del caso de uso (la serializa el controlador REST). */
final readonly class EmitirFacturaResponse
{
    public function __construct(
        public string $comprobanteId,
        public string $estado,
        public ?string $codigoUnico,
        public ?string $xmlDocumentoId,
        public float $total,
    ) {}

    public static function desde(Comprobante $c): self
    {
        return new self(
            $c->id,
            $c->estado()->value,
            $c->cufe(),
            $c->xmlDocumentoId(),
            $c->total()->monto,
        );
    }
}
