<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Facturacion\Enum\Pais;
use App\Domain\Facturacion\Exception\FacturacionException;
use App\Domain\Facturacion\Port\ProveedorFacturacion;

/**
 * Selecciona el adaptador (Strategy) correcto segun el pais del comprobante.
 *
 * La lista de adaptadores se inyecta ya resuelta (map codigo-pais => instancia),
 * construida por la Fabrica en Infrastructure a partir de config/facturacion.php.
 * Asi, agregar un pais NO cambia esta clase (Abierto/Cerrado).
 */
final class ResolverProveedor
{
    /** @param array<string, ProveedorFacturacion> $proveedores */
    public function __construct(private readonly array $proveedores) {}

    public function para(Pais $pais): ProveedorFacturacion
    {
        $proveedor = $this->proveedores[$pais->value] ?? null;

        if ($proveedor === null) {
            throw new FacturacionException(
                "No hay proveedor de facturacion configurado para {$pais->value} ({$pais->organismo()})."
            );
        }

        return $proveedor;
    }
}
