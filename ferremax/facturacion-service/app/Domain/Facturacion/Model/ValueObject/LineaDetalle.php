<?php

declare(strict_types=1);

namespace App\Domain\Facturacion\Model\ValueObject;

/**
 * Linea de detalle (item) del comprobante. Objeto de valor inmutable.
 */
final readonly class LineaDetalle
{
    public function __construct(
        public string $descripcion,
        public float $cantidad,
        public Dinero $precioUnitario,
        public string $unidadMedida = 'NIU',   // codigo generico (SUNAT: NIU=unidad)
        public float $tasaImpuesto = 18.0,      // % IGV/IVA
        public ?string $codigo = null,
    ) {}

    public function valorVenta(): Dinero
    {
        return new Dinero(round($this->cantidad * $this->precioUnitario->monto, 2), $this->precioUnitario->moneda);
    }

    public function impuesto(): Dinero
    {
        return $this->valorVenta()->porcentaje($this->tasaImpuesto);
    }
}
