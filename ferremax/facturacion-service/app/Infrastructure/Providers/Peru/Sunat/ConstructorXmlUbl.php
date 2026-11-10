<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers\Peru\Sunat;

use App\Domain\Facturacion\Model\Comprobante;

/**
 * Construye el XML UBL 2.1 exigido por SUNAT a partir del agregado de dominio.
 * (Stub: en produccion mapea a Invoice/CreditNote UBL con cbc/cac namespaces.)
 */
final class ConstructorXmlUbl
{
    public function construir(Comprobante $c): string
    {
        // TODO: mapear a UBL 2.1 real (Invoice o CreditNote segun $c->tipo).
        return sprintf(
            '<Invoice><ID>%s</ID><IssueDate>%s</IssueDate><Total>%.2f</Total></Invoice>',
            $c->numeroCompleto(),
            $c->fechaEmision->format('Y-m-d'),
            $c->total()->monto,
        );
    }

    public function construirBaja(Comprobante $c, string $motivo): string
    {
        return sprintf('<VoidedDocuments><Ref>%s</Ref><Reason>%s</Reason></VoidedDocuments>', $c->numeroCompleto(), $motivo);
    }
}
