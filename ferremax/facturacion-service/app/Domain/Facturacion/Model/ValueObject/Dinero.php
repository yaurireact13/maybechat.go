<?php

declare(strict_types=1);

namespace App\Domain\Facturacion\Model\ValueObject;

use App\Domain\Facturacion\Exception\FacturacionException;

/**
 * Objeto de valor inmutable para importes monetarios.
 * Evita errores de redondeo trabajando en la unidad menor cuando conviene.
 */
final readonly class Dinero
{
    public function __construct(
        public float $monto,
        public string $moneda = 'PEN',
    ) {
        if ($monto < 0) {
            throw new FacturacionException('El importe no puede ser negativo.');
        }
    }

    public function sumar(Dinero $otro): self
    {
        $this->mismaMoneda($otro);
        return new self(round($this->monto + $otro->monto, 2), $this->moneda);
    }

    public function porcentaje(float $pct): self
    {
        return new self(round($this->monto * $pct / 100, 2), $this->moneda);
    }

    private function mismaMoneda(Dinero $otro): void
    {
        if ($this->moneda !== $otro->moneda) {
            throw new FacturacionException("Monedas incompatibles: {$this->moneda} vs {$otro->moneda}.");
        }
    }
}
