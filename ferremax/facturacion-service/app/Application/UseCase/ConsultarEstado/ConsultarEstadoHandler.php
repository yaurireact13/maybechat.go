<?php

declare(strict_types=1);

namespace App\Application\UseCase\ConsultarEstado;

use App\Application\Service\ResolverProveedor;
use App\Domain\Facturacion\Exception\FacturacionException;
use App\Domain\Facturacion\Port\ComprobanteRepository;

/** CASO DE USO: Consultar el estado de un comprobante ante el organismo fiscal. */
final class ConsultarEstadoHandler
{
    public function __construct(
        private readonly ResolverProveedor $resolver,
        private readonly ComprobanteRepository $repositorio,
    ) {}

    public function __invoke(string $comprobanteId): array
    {
        $comprobante = $this->repositorio->porId($comprobanteId)
            ?? throw new FacturacionException('Comprobante no encontrado.');

        $estado = $this->resolver->para($comprobante->pais)->consultarEstado($comprobante);

        return [
            'comprobanteId' => $comprobante->id,
            'estadoLocal'   => $comprobante->estado()->value,
            'estadoRemoto'  => $estado->estado->value,
            'codigoUnico'   => $estado->codigoUnico,
            'mensaje'       => $estado->mensaje,
        ];
    }
}
