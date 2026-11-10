<?php

declare(strict_types=1);

namespace App\Application\UseCase\EmitirNotaCredito;

use App\Application\Service\ResolverProveedor;
use App\Domain\Facturacion\Enum\Pais;
use App\Domain\Facturacion\Enum\TipoComprobante;
use App\Domain\Facturacion\Model\Comprobante;
use App\Domain\Facturacion\Model\ValueObject\Dinero;
use App\Domain\Facturacion\Model\ValueObject\Emisor;
use App\Domain\Facturacion\Model\ValueObject\LineaDetalle;
use App\Domain\Facturacion\Model\ValueObject\Receptor;
use App\Domain\Facturacion\Port\AlmacenDocumentos;
use App\Domain\Facturacion\Port\ComprobanteRepository;
use App\Domain\Facturacion\Port\RegistroAuditoria;

/**
 * CASO DE USO: Emitir una nota de credito que afecta a un comprobante previo.
 * Reutiliza el mismo agregado Comprobante (tipo NOTA_CREDITO) y el mismo puerto.
 */
final class EmitirNotaCreditoHandler
{
    public function __construct(
        private readonly ResolverProveedor $resolver,
        private readonly ComprobanteRepository $repositorio,
        private readonly AlmacenDocumentos $almacen,
        private readonly RegistroAuditoria $auditoria,
    ) {}

    public function __invoke(array $datos): array
    {
        $pais = Pais::from($datos['pais']);

        $nota = new Comprobante(
            id: bin2hex(random_bytes(16)),
            pais: $pais,
            tipo: TipoComprobante::NOTA_CREDITO,
            serie: $datos['serie'],
            numero: $datos['numero'],
            emisor: new Emisor($datos['emisor']['identificadorFiscal'], $datos['emisor']['razonSocial']),
            receptor: new Receptor($datos['receptor']['tipoDocumento'], $datos['receptor']['numeroDocumento'], $datos['receptor']['razonSocial']),
            lineas: array_map(fn ($l) => new LineaDetalle(
                $l['descripcion'], (float) $l['cantidad'],
                new Dinero((float) $l['precioUnitario'], $datos['moneda']),
                $l['unidadMedida'] ?? 'NIU', (float) ($l['tasaImpuesto'] ?? 18.0)
            ), $datos['lineas']),
            fechaEmision: new \DateTimeImmutable($datos['fechaEmision']),
            moneda: $datos['moneda'],
            comprobanteAfectadoId: $datos['comprobanteAfectadoId'],
        );

        $this->repositorio->guardar($nota);
        $resultado = $this->resolver->para($pais)->emitirNotaCredito($nota);

        if ($resultado->estado->esFinalExitoso()) {
            $docId = $this->almacen->guardar("{$datos['pais']}/nc/{$nota->numeroCompleto()}.xml", (string) $resultado->xmlFirmado, 'application/xml');
            $nota->marcarAceptado((string) $resultado->codigoUnico, $docId);
        } else {
            $nota->marcarRechazado((string) $resultado->codigoError, (string) $resultado->mensaje);
        }

        $this->repositorio->guardar($nota);
        $this->auditoria->registrar($nota, 'nota_credito', ['afecta' => $datos['comprobanteAfectadoId']]);

        return ['comprobanteId' => $nota->id, 'estado' => $nota->estado()->value, 'codigoUnico' => $nota->cufe()];
    }
}
