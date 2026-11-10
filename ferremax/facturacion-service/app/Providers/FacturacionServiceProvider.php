<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Service\ResolverProveedor;
use App\Domain\Facturacion\Port\AlmacenDocumentos;
use App\Domain\Facturacion\Port\ComprobanteRepository;
use App\Domain\Facturacion\Port\RegistroAuditoria;
use App\Infrastructure\Audit\RegistroAuditoriaDb;
use App\Infrastructure\Factory\FabricaProveedorFacturacion;
use App\Infrastructure\Persistence\Eloquent\RepositorioComprobanteEloquent;
use App\Infrastructure\Storage\AlmacenDocumentosFilesystem;
use Illuminate\Support\ServiceProvider;

/**
 * COMPOSITION ROOT: aqui se "enchufan" los adaptadores concretos a los puertos.
 * Es el unico lugar que decide que implementacion usa cada interfaz, cumpliendo
 * la Inversion de Dependencias: el dominio/aplicacion solo conocen interfaces.
 */
final class FacturacionServiceProvider extends ServiceProvider
{
    public array $bindings = [
        ComprobanteRepository::class => RepositorioComprobanteEloquent::class,
        RegistroAuditoria::class     => RegistroAuditoriaDb::class,
    ];

    public function register(): void
    {
        // Puerto de almacenamiento con el disco configurado.
        $this->app->bind(AlmacenDocumentos::class, fn () =>
            new AlmacenDocumentosFilesystem(config('facturacion.almacen.disk', 's3'))
        );

        // El resolver de estrategias se arma desde config (mapa pais => adaptador).
        $this->app->singleton(ResolverProveedor::class, fn ($app) =>
            (new FabricaProveedorFacturacion($app))->crearResolver()
        );
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/facturacion.php', 'facturacion');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
