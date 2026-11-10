<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_correcto_redirige_al_dashboard(): void
    {
        $this->crearFerreteria();

        $this->post('/login', ['email' => 'admin-a@test.local', 'password' => 'secreto123'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_login_de_ferreteria_suspendida_es_rechazado(): void
    {
        $this->crearFerreteria('a', ['activo' => false]);

        $this->post('/login', ['email' => 'admin-a@test.local', 'password' => 'secreto123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_sesion_abierta_se_cierra_al_suspender_la_ferreteria(): void
    {
        ['admin' => $admin, 'e' => $e] = $this->crearFerreteria();

        $this->actingAs($admin)->get('/dashboard')->assertOk();

        $e->update(['activo' => false]);

        $this->actingAs($admin->fresh())->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_sesion_abierta_se_cierra_al_desactivar_el_usuario(): void
    {
        ['vendedor' => $vendedor] = $this->crearFerreteria();

        $vendedor->update(['activo' => false]);

        $this->actingAs($vendedor)->get('/pos')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_tiene_limite_de_intentos(): void
    {
        $this->crearFerreteria();

        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', ['email' => 'admin-a@test.local', 'password' => 'incorrecta']);
        }

        $this->post('/login', ['email' => 'admin-a@test.local', 'password' => 'incorrecta'])
            ->assertStatus(429);
    }

    public function test_una_ferreteria_no_ve_las_ventas_de_otra(): void
    {
        ['vendedor' => $vendedorA, 'producto' => $productoA] = $this->crearFerreteria('a');
        ['admin' => $adminB] = $this->crearFerreteria('b');

        $this->actingAs($vendedorA)->post('/pos', [
            'tipo_comprobante' => 'ticket', 'metodo_pago' => 'efectivo',
            'items' => json_encode([['id' => $productoA->id, 'cantidad' => 1]]),
        ])->assertRedirect('/pos');

        $venta = Venta::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($adminB)->get("/ventas/{$venta->id}")->assertNotFound();
        $this->actingAs($adminB)->get("/productos/{$productoA->id}/edit")->assertNotFound();
    }

    public function test_producto_no_acepta_categoria_de_otra_ferreteria(): void
    {
        ['admin' => $adminA] = $this->crearFerreteria('a');
        ['categoria' => $categoriaB] = $this->crearFerreteria('b');

        $this->actingAs($adminA)->post('/productos', [
            'codigo' => 'NUEVO-1', 'nombre' => 'Alicate', 'categoria_id' => $categoriaB->id,
            'precio_compra' => 5, 'precio_venta' => 9, 'stock' => 1, 'stock_minimo' => 0,
        ])->assertSessionHasErrors('categoria_id');
    }

    public function test_cotizacion_no_acepta_productos_de_otra_ferreteria(): void
    {
        ['admin' => $adminA] = $this->crearFerreteria('a');
        ['producto' => $productoB] = $this->crearFerreteria('b');

        $this->actingAs($adminA)->post('/cotizaciones', [
            'items' => json_encode([['id' => $productoB->id, 'cantidad' => 1, 'precio' => 1]]),
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_el_vendedor_no_gestiona_usuarios(): void
    {
        ['vendedor' => $vendedor] = $this->crearFerreteria();

        $this->actingAs($vendedor)->post('/usuarios', [
            'name' => 'Intruso', 'email' => 'intruso@test.local', 'rol' => 'admin', 'password' => 'secreto123',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'intruso@test.local']);
    }

    public function test_el_admin_de_ferreteria_no_entra_al_panel_de_plataforma(): void
    {
        ['admin' => $admin] = $this->crearFerreteria();

        $this->actingAs($admin)->get('/panel')->assertForbidden();
    }
}
