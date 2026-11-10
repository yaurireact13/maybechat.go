<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Matriz de acceso por rol. Está escrita a mano a propósito: si alguien cambia
 * config/permisos.php, estas pruebas obligan a confirmar el cambio aquí también.
 */
class PermisosTest extends TestCase
{
    use RefreshDatabase;

    /** pantalla => roles que deben poder abrirla (además del admin, que entra a todo) */
    private const MATRIZ = [
        '/dashboard'                 => ['vendedor', 'cajero', 'almacenero'],
        '/pos'                       => ['vendedor', 'cajero'],
        '/ventas'                    => ['vendedor', 'cajero'],
        '/cotizaciones'              => ['vendedor'],
        '/cotizaciones/nueva'        => ['vendedor'],
        '/caja'                      => ['cajero'],
        '/cuentas/por-cobrar'        => ['cajero'],
        '/cuentas/por-pagar'         => [],
        '/clientes'                  => ['vendedor', 'cajero'],
        '/productos'                 => ['almacenero'],
        '/productos/create'          => ['almacenero'],
        '/catalogos'                 => ['almacenero'],
        '/inventario'                => ['almacenero'],
        '/compras'                   => ['almacenero'],
        '/compras/nueva'             => ['almacenero'],
        '/proveedores'               => ['almacenero'],
        '/reportes'                  => [],
        '/reportes/exportar'         => [],
        '/usuarios'                  => [],
        '/configuracion'             => [],
        '/configuracion/facturacion' => [],
        '/asistente'                 => [],
        '/chat-ia'                   => [],
    ];

    public function test_cada_rol_entra_solo_a_sus_pantallas(): void
    {
        $f = $this->crearFerreteria();

        foreach (['admin', 'vendedor', 'cajero', 'almacenero'] as $rol) {
            foreach (self::MATRIZ as $url => $roles) {
                $esperado = $rol === 'admin' || in_array($rol, $roles, true) ? 200 : 403;
                $estado = $this->actingAs($f[$rol])->get($url)->getStatusCode();
                $this->assertSame($esperado, $estado, "Rol {$rol} en {$url}: esperado {$esperado}, obtenido {$estado}");
            }
        }
    }

    public function test_solo_el_admin_anula_ventas(): void
    {
        $f = $this->crearFerreteria();
        $this->actingAs($f['vendedor'])->post('/pos', [
            'tipo_comprobante' => 'ticket', 'metodo_pago' => 'efectivo',
            'items' => json_encode([['id' => $f['producto']->id, 'cantidad' => 2]]),
        ]);
        $venta = Venta::withoutGlobalScopes()->firstOrFail();

        foreach (['vendedor', 'cajero', 'almacenero'] as $rol) {
            $this->actingAs($f[$rol])->patch("/ventas/{$venta->id}/anular")->assertForbidden();
        }
        $this->assertSame('completada', $venta->fresh()->estado);
        $this->assertEquals(8, (float) $f['producto']->fresh()->stock);

        // El vendedor ve el detalle, pero sin el botón de anular
        $this->actingAs($f['vendedor'])->get("/ventas/{$venta->id}")
            ->assertOk()->assertDontSee("/ventas/{$venta->id}/anular", false);

        $this->actingAs($f['admin'])->patch("/ventas/{$venta->id}/anular")->assertRedirect();
        $this->assertSame('anulada', $venta->fresh()->estado);
        $this->assertEquals(10, (float) $f['producto']->fresh()->stock);
    }

    public function test_el_almacenero_compra_pero_no_anula_compras(): void
    {
        $f = $this->crearFerreteria();
        $prov = Proveedor::create(['empresa_id' => $f['e']->id, 'razon_social' => 'Proveedor SAC']);

        $this->actingAs($f['almacenero'])->post('/compras', [
            'proveedor_id' => $prov->id, 'tipo_comprobante' => 'factura', 'numero' => 'F001-1',
            'fecha' => now()->toDateString(),
            'items' => json_encode([['id' => $f['producto']->id, 'cantidad' => 5, 'precio' => 20]]),
        ])->assertRedirect();

        $compra = Compra::withoutGlobalScopes()->firstOrFail();
        $this->assertEquals(15, (float) $f['producto']->fresh()->stock);

        $this->actingAs($f['almacenero'])->patch("/compras/{$compra->id}/anular")->assertForbidden();
        $this->actingAs($f['almacenero'])->get("/compras/{$compra->id}")
            ->assertOk()->assertDontSee("/compras/{$compra->id}/anular", false);
    }

    public function test_acciones_prohibidas_por_rol(): void
    {
        $f = $this->crearFerreteria();
        $item = json_encode([['id' => $f['producto']->id, 'cantidad' => 1]]);

        // El almacenero no vende
        $this->actingAs($f['almacenero'])->post('/pos', ['tipo_comprobante' => 'ticket', 'metodo_pago' => 'efectivo', 'items' => $item])
            ->assertForbidden();
        // El cajero no ajusta inventario ni crea productos
        $this->actingAs($f['cajero'])->post('/inventario/ajustar', ['producto_id' => $f['producto']->id, 'tipo' => 'entrada', 'cantidad' => 100, 'motivo' => 'x'])
            ->assertForbidden();
        $this->actingAs($f['cajero'])->post('/productos', ['codigo' => 'X1', 'nombre' => 'X', 'precio_compra' => 1, 'precio_venta' => 2, 'stock' => 1, 'stock_minimo' => 0])
            ->assertForbidden();
        // El vendedor no abre caja
        $this->actingAs($f['vendedor'])->post('/caja/abrir', ['monto_apertura' => 100])->assertForbidden();

        $this->assertEquals(10, (float) $f['producto']->fresh()->stock);
        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('cajas', 0);
    }

    public function test_el_dashboard_no_expone_cifras_financieras_a_otros_roles(): void
    {
        $f = $this->crearFerreteria();

        $this->actingAs($f['admin'])->get('/dashboard')->assertOk()
            ->assertSee('chartIngresosEgresos', false)->assertSee('Por Pagar');

        $this->actingAs($f['vendedor'])->get('/dashboard')->assertOk()
            ->assertSee('Ventas de Hoy', false)
            ->assertDontSee('chartIngresosEgresos', false)
            ->assertDontSee('chartCartera', false)
            ->assertDontSee('Por Pagar')
            ->assertDontSee('Por Cobrar');

        $this->actingAs($f['cajero'])->get('/dashboard')->assertOk()
            ->assertSee('Por Cobrar')->assertDontSee('Por Pagar');

        $this->actingAs($f['almacenero'])->get('/dashboard')->assertOk()
            ->assertSee('Alertas de stock bajo')
            ->assertDontSee('Ventas de Hoy', false)
            ->assertDontSee('Nueva Venta')
            ->assertDontSee('chartHoras', false);
    }

    public function test_el_menu_muestra_solo_los_modulos_del_rol(): void
    {
        $f = $this->crearFerreteria();

        $this->actingAs($f['almacenero'])->get('/dashboard')
            ->assertSee(route('productos.index'), false)
            ->assertSee(route('compras.index'), false)
            ->assertDontSee(route('pos.index'), false)
            ->assertDontSee(route('reportes.index'), false)
            ->assertDontSee(route('usuarios.index'), false)
            ->assertSee('Almacenero');

        $this->actingAs($f['cajero'])->get('/dashboard')
            ->assertSee(route('caja.index'), false)
            ->assertDontSee(route('inventario.index'), false)
            ->assertDontSee(route('cuentas.pagar'), false);
    }

    public function test_pagina_403_en_espanol(): void
    {
        $f = $this->crearFerreteria();

        $this->actingAs($f['vendedor'])->get('/reportes')
            ->assertForbidden()
            ->assertSee('Acceso restringido')
            ->assertSee('Tu rol no tiene permiso')
            ->assertSee('Vendedor')
            ->assertDontSee('unauthorized');
    }

    public function test_usuarios_muestra_la_matriz_y_valida_roles(): void
    {
        $f = $this->crearFerreteria();

        $this->actingAs($f['admin'])->get('/usuarios')->assertOk()
            ->assertSee('Permisos por rol')
            ->assertSee('Anular ventas')
            ->assertSee('data-descripcion=', false);

        $this->actingAs($f['admin'])->post('/usuarios', [
            'name' => 'X', 'email' => 'x@test.local', 'rol' => 'superadmin', 'password' => 'secreto123',
        ])->assertSessionHasErrors('rol');
        $this->assertDatabaseMissing('users', ['email' => 'x@test.local']);
    }
}
