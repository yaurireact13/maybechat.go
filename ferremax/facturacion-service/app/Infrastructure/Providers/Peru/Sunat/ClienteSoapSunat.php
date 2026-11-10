<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers\Peru\Sunat;

use App\Domain\Facturacion\Enum\EstadoComprobante;
use App\Domain\Facturacion\Model\Comprobante;
use App\Domain\Facturacion\Port\Resultado\EstadoRemoto;

/**
 * Cliente SOAP hacia el billService de SUNAT (sendBill, sendSummary, getStatus).
 * (Stub: en produccion usa ext-soap con WSSecurity y el endpoint de config.)
 */
final class ClienteSoapSunat
{
    public function __construct(private readonly array $config = []) {}

    /** Envia el comprobante (ZIP con XML firmado). Devuelve el CDR (ZIP) de respuesta. */
    public function enviarComprobante(Comprobante $comprobante, string $xmlFirmado): string
    {
        // TODO: sendBill() real. Devuelve el applicationResponse (CDR) comprimido.
        return 'CDR-ZIP-SIMULADO';
    }

    public function enviarBaja(string $xmlFirmado): string
    {
        // TODO: sendSummary() real. Devuelve un ticket para consultar luego.
        return 'TICKET-' . substr(md5($xmlFirmado), 0, 12);
    }

    public function consultarEstado(Comprobante $comprobante): EstadoRemoto
    {
        // TODO: getStatus()/getStatusCdr() real.
        return new EstadoRemoto(EstadoComprobante::ACEPTADO, $comprobante->cufe());
    }
}
