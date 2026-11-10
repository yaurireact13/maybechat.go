<?php

declare(strict_types=1);

namespace App\Application\UseCase\AnularComprobante;

use App\Application\Service\ResolverProveedor;
use App\Domain\Facturacion\Exception\FacturacionException;
use App\Domain\Facturacion\Port\ComprobanteRepository;
use App\Domain\Facturacion\Port\RegistroAuditoria;

/** CASO DE USO: Anular / dar de baja un comprobante aceptado. */
final class AnularComprobanteHandler
{
    public function __construct(
        private readonly ResolverProveedor $resolver,
        private readonly ComprobanteRepository $repositorio,
        private readonly RegistroAuditoria $auditoria,
    ) {}

    public function __invoke(string $comprobanteId, string $motivo): array
    {
        $comprobante = $this->repositorio->porId($comprobanteId)
            ?? throw new FacturacionException('Comprobante no encontrado.');

        $proveedor = $this->resolver->para($comprobante->pais);
        $resultado = $proveedor->anularComprobante($comprobante, $motivo);

        if ($resultado->estado->esFinalExitoso()) {
            $comprobante->anular($motivo);
        } else {
            $comprobante->marcarError((string) $resultado->mensaje);
        }

        $this->repositorio->guardar($comprobante);
        $this->auditoria->registrar($comprobante, 'anulacion', [
            'motivo' => $motivo, 'codigo' => $resultado->codigoUnico, 'respuesta' => $resultado->respuestaCruda,
        ]);

        return ['comprobanteId' => $comprobante->id, 'estado' => $comprobante->estado()->value];
    }
}
