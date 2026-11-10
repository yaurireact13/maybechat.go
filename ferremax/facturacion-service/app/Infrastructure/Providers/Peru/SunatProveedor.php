<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers\Peru;

use App\Domain\Facturacion\Enum\Pais;
use App\Domain\Facturacion\Model\Comprobante;
use App\Domain\Facturacion\Port\ProveedorFacturacion;
use App\Domain\Facturacion\Port\Resultado\EstadoRemoto;
use App\Domain\Facturacion\Port\Resultado\ResultadoEmision;
use App\Infrastructure\Providers\Peru\Sunat\ConstructorXmlUbl;
use App\Infrastructure\Providers\Peru\Sunat\FirmadorXml;
use App\Infrastructure\Providers\Peru\Sunat\ClienteSoapSunat;
use App\Infrastructure\Providers\Peru\Sunat\LectorCdr;

/**
 * ADAPTADOR PERU (SUNAT).  Implementa el puerto ProveedorFacturacion.
 *
 * Traduce el modelo de dominio -> UBL 2.1, firma el XML, lo envia por SOAP a SUNAT,
 * y traduce la respuesta (CDR) de vuelta al modelo neutral (ResultadoEmision).
 *
 * Toda la particularidad de SUNAT queda ENCAPSULADA aqui. El resto del sistema
 * no cambia si SUNAT modifica su formato: solo se ajusta este adaptador.
 */
final class SunatProveedor implements ProveedorFacturacion
{
    public function __construct(
        private readonly ConstructorXmlUbl $constructorXml,
        private readonly FirmadorXml $firmador,
        private readonly ClienteSoapSunat $soap,
        private readonly LectorCdr $lectorCdr,
    ) {}

    public function pais(): Pais
    {
        return Pais::PE;
    }

    public function emitirFactura(Comprobante $comprobante): ResultadoEmision
    {
        try {
            $xml = $this->constructorXml->construir($comprobante);   // UBL 2.1
            $xmlFirmado = $this->firmador->firmar($xml, $comprobante->emisor);
            $cdrZip = $this->soap->enviarComprobante($comprobante, $xmlFirmado);
            $cdr = $this->lectorCdr->leer($cdrZip);

            return match ($cdr->codigo) {
                0       => ResultadoEmision::aceptado($cdr->hashCpe, $xmlFirmado, $cdr->crudo),
                default => $cdr->esObservacion()
                    ? new ResultadoEmision(\App\Domain\Facturacion\Enum\EstadoComprobante::OBSERVADO, $cdr->hashCpe, $xmlFirmado, $cdr->crudo, $cdr->descripcion)
                    : ResultadoEmision::rechazado((string) $cdr->codigo, $cdr->descripcion),
            };
        } catch (\SoapFault | \RuntimeException $e) {
            // Fallo tecnico (red/timeout/SUNAT caido) => reintentable por el worker.
            return ResultadoEmision::errorTecnico($e->getMessage());
        }
    }

    public function emitirNotaCredito(Comprobante $notaCredito): ResultadoEmision
    {
        // Mismo pipeline; el ConstructorXmlUbl detecta el tipo NOTA_CREDITO (UBL CreditNote).
        return $this->emitirFactura($notaCredito);
    }

    public function anularComprobante(Comprobante $comprobante, string $motivo): ResultadoEmision
    {
        // SUNAT: Comunicacion de Baja (RA) o Resumen. Se genera el XML de baja, se firma y envia.
        $xmlBaja = $this->constructorXml->construirBaja($comprobante, $motivo);
        $firmado = $this->firmador->firmar($xmlBaja, $comprobante->emisor);
        $ticket  = $this->soap->enviarBaja($firmado);

        // SUNAT responde con un "ticket"; el estado real se consulta luego (async).
        return new ResultadoEmision(\App\Domain\Facturacion\Enum\EstadoComprobante::ENVIADO, $ticket);
    }

    public function consultarEstado(Comprobante $comprobante): EstadoRemoto
    {
        return $this->soap->consultarEstado($comprobante);
    }
}
