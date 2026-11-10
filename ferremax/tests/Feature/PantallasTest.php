<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Recorre todas las pantallas principales y verifica que ninguna falle. */
class PantallasTest extends TestCase
{
    use RefreshDatabase;

    private const PANTALLAS_TENANT = [
        '/dashboard', '/pos', '/ventas', '/cotizaciones', '/cotizaciones/nueva', '/caja',
        '/cuentas/por-cobrar', '/cuentas/por-pagar', '/compras', '/compras/nueva', '/productos',
        '/productos/create', '/inventario', '/catalogos', '/clientes', '/proveedores', '/reportes',
    ];

    private const PANTALLAS_ADMIN = ['/usuarios', '/configuracion', '/configuracion/facturacion', '/asistente', '/chat-ia'];

    public function test_el_administrador_abre_todas_las_pantallas(): void
    {
        ['admin' => $admin] = $this->crearFerreteria();

        foreach ([...self::PANTALLAS_TENANT, ...self::PANTALLAS_ADMIN] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_el_vendedor_abre_las_pantallas_operativas_pero_no_las_de_administracion(): void
    {
        ['vendedor' => $vendedor] = $this->crearFerreteria();

        foreach (self::PANTALLAS_TENANT as $url) {
            $this->actingAs($vendedor)->get($url)->assertOk();
        }
        foreach (self::PANTALLAS_ADMIN as $url) {
            $this->actingAs($vendedor)->get($url)->assertForbidden();
        }
    }

    public function test_el_super_admin_abre_el_panel_de_plataforma(): void
    {
        ['e' => $empresa] = $this->crearFerreteria();
        $super = $this->superAdmin();

        foreach (['/panel', '/panel/planes', '/panel/empresas', '/panel/empresas/nueva',
            "/panel/empresas/{$empresa->id}", "/panel/empresas/{$empresa->id}/editar",
            '/panel/usuarios', '/panel/facturacion'] as $url) {
            $this->actingAs($super)->get($url)->assertOk();
        }
    }

    public function test_los_botones_editar_llevan_los_datos_escapados(): void
    {
        ['admin' => $admin, 'e' => $e] = $this->crearFerreteria();
        \App\Models\Cliente::create(['empresa_id' => $e->id, 'nombre' => '<script>alert(1)</script>', 'tipo_documento' => 'DNI']);

        $this->actingAs($admin)->get('/clientes')
            ->assertOk()
            ->assertSee('data-registro=', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
