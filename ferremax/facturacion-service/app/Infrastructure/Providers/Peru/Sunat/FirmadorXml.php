<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers\Peru\Sunat;

use App\Domain\Facturacion\Model\ValueObject\Emisor;

/**
 * Firma digital XML-DSig del comprobante con el certificado del emisor.
 * (Stub: en produccion usa robrichards/xmlseclibs y el certificado .pfx/.pem del emisor.)
 */
final class FirmadorXml
{
    public function firmar(string $xml, Emisor $emisor): string
    {
        // TODO: cargar certificado del emisor y aplicar firma XML-DSig enveloped.
        return $xml; // firmado (placeholder)
    }
}
