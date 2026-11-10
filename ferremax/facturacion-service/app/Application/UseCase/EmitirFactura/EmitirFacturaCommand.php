<?php

declare(strict_types=1);

namespace App\Application\UseCase\EmitirFactura;

/**
 * Comando de entrada del caso de uso (DTO plano, sin logica).
 * Lo construye el controlador REST a partir del request del ERP.
 */
final readonly class EmitirFacturaCommand
{
    public function __construct(
        public string $pais,
        public string $tipo,             // factura | boleta
        public string $serie,
        public string $numero,
        public array  $emisor,           // ['identificadorFiscal','razonSocial',...]
        public array  $receptor,         // ['tipoDocumento','numeroDocumento','razonSocial',...]
        public array  $lineas,           // [['descripcion','cantidad','precioUnitario','tasaImpuesto'],...]
        public string $moneda,
        public string $fechaEmision,     // ISO-8601
        public string $claveIdempotencia,
    ) {}
}
