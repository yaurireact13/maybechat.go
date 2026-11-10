<?php

declare(strict_types=1);

namespace App\Application\UseCase\EmitirFactura;

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
 * CASO DE USO: Emitir una factura/boleta.
 *
 * Orquesta el flujo pero NO conoce ningun pais concreto: pide el adaptador al
 * ResolverProveedor. Depende solo de PUERTOS (interfaces), nunca de infraestructura.
 *
 * Flujo:
 *   1. Idempotencia: si la clave/serie-numero ya existe, devuelve el resultado previo.
 *   2. Reconstruye el agregado Comprobante (dominio valida y calcula totales).
 *   3. Persiste PENDIENTE.
 *   4. Resuelve el adaptador del pais y emite.
 *   5. Aplica el resultado al agregado, guarda XML/CDR, audita y persiste.
 */
final class EmitirFacturaHandler
{
    public function __construct(
        private readonly ResolverProveedor $resolver,
        private readonly ComprobanteRepository $repositorio,
        private readonly AlmacenDocumentos $almacen,
        private readonly RegistroAuditoria $auditoria,
    ) {}

    public function __invoke(EmitirFacturaCommand $cmd): EmitirFacturaResponse
    {
        $pais = Pais::from($cmd->pais);

        // (1) Idempotencia
        $existente = $this->repositorio->porClaveNegocio(
            $cmd->pais, $cmd->serie, $cmd->numero, $cmd->emisor['identificadorFiscal']
        );
        if ($existente !== null) {
            return EmitirFacturaResponse::desde($existente);
        }

        // (2) Construir el agregado (el dominio valida invariantes)
        $comprobante = new Comprobante(
            id: bin2hex(random_bytes(16)),
            pais: $pais,
            tipo: TipoComprobante::from($cmd->tipo),
            serie: $cmd->serie,
            numero: $cmd->numero,
            emisor: new Emisor($cmd->emisor['identificadorFiscal'], $cmd->emisor['razonSocial'], $cmd->emisor['nombreComercial'] ?? null, $cmd->emisor['direccion'] ?? null),
            receptor: new Receptor($cmd->receptor['tipoDocumento'], $cmd->receptor['numeroDocumento'], $cmd->receptor['razonSocial'], $cmd->receptor['direccion'] ?? null, $cmd->receptor['email'] ?? null),
            lineas: array_map(fn (array $l) => new LineaDetalle(
                $l['descripcion'], (float) $l['cantidad'],
                new Dinero((float) $l['precioUnitario'], $cmd->moneda),
                $l['unidadMedida'] ?? 'NIU', (float) ($l['tasaImpuesto'] ?? 18.0), $l['codigo'] ?? null
            ), $cmd->lineas),
            fechaEmision: new \DateTimeImmutable($cmd->fechaEmision),
            moneda: $cmd->moneda,
        );

        // (3) Persistir en estado PENDIENTE (para trazabilidad y reintentos)
        $this->repositorio->guardar($comprobante);
        $this->auditoria->registrar($comprobante, 'creado', ['clave_idempotencia' => $cmd->claveIdempotencia]);

        // (4) Resolver el adaptador del pais y emitir
        $proveedor = $this->resolver->para($pais);
        $comprobante->marcarEnviado();
        $resultado = $proveedor->emitirFactura($comprobante);

        // (5) Aplicar resultado
        if ($resultado->estado->esFinalExitoso()) {
            $docId = $this->almacen->guardar(
                "{$cmd->pais}/{$cmd->emisor['identificadorFiscal']}/{$comprobante->numeroCompleto()}.xml",
                (string) $resultado->xmlFirmado, 'application/xml'
            );
            $comprobante->marcarAceptado((string) $resultado->codigoUnico, $docId);
        } elseif ($resultado->estado === \App\Domain\Facturacion\Enum\EstadoComprobante::RECHAZADO) {
            $comprobante->marcarRechazado((string) $resultado->codigoError, (string) $resultado->mensaje);
        } else {
            $comprobante->marcarError((string) $resultado->mensaje); // reintentable por el worker
        }

        $this->repositorio->guardar($comprobante);
        $this->auditoria->registrar($comprobante, 'emision', [
            'estado' => $comprobante->estado()->value,
            'codigo' => $resultado->codigoUnico,
            'respuesta' => $resultado->respuestaCruda,
        ]);

        return EmitirFacturaResponse::desde($comprobante);
    }
}
