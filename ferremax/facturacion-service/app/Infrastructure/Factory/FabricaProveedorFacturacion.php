<?php

declare(strict_types=1);

namespace App\Infrastructure\Factory;

use App\Application\Service\ResolverProveedor;
use App\Domain\Facturacion\Port\ProveedorFacturacion;
use Illuminate\Contracts\Container\Container;

/**
 * Construye el mapa PAIS => adaptador leyendo config/facturacion.php y usando el
 * contenedor de Laravel para resolver dependencias de cada adaptador.
 *
 * Es el UNICO punto que conoce todas las implementaciones concretas; el resto del
 * sistema depende solo del puerto ProveedorFacturacion (Inversion de Dependencias).
 */
final class FabricaProveedorFacturacion
{
    public function __construct(private readonly Container $container) {}

    public function crearResolver(): ResolverProveedor
    {
        $mapa = [];

        foreach ((array) config('facturacion.proveedores') as $codigoPais => $clase) {
            /** @var ProveedorFacturacion $adaptador */
            $adaptador = $this->container->make($clase);
            $mapa[$codigoPais] = $adaptador;
        }

        return new ResolverProveedor($mapa);
    }
}
