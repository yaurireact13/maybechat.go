<?php

namespace Tests\Feature;

use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    public function test_venta_descuenta_stock_y_registra_kardex(): void
    {
        ['vendedor' => $v, 'producto' => $p] = $this->crearFerreteria();

        $this->actingAs($v)->post('/pos', [
            'tipo_comprobante' => 'ticket', 'metodo_pago' => 'efectivo',
            'items' => json_encode([['id' => $p->id, 'cantidad' => 3, 'precio' => 50]]),
        ])->assertRedirect('/pos')->assertSessionHas('ok');

        $this->assertEquals(7, (float) $p->fresh()->stock);
        $venta = Venta::withoutGlobalScopes()->firstOrFail();
        $this->assertEquals(150.00, (float) $venta->total);
        $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $p->id, 'tipo' => 'salida']);
    }

    public function test_el_precio_lo_define_el_servidor_no_el_navegador(): void
    {
        ['vendedor' => $v, 'producto' => $p] = $this->crearFerreteria();

        // Petición manipulada: intenta vender a 0.01 un producto de 50.00
        $this->actingAs($v)->post('/pos', [
            'tipo_comprobante' => 'ticket', 'metodo_pago' => 'efectivo',
            'items' => json_encode([['id' => $p->id, 'cantidad' => 2, 'precio' => 0.01]]),
        ])->assertRedirect('/pos');

        $venta = Venta::withoutGlobalScopes()->with('detalles')->firstOrFail();
        $this->assertEquals(100.00, (float) $venta->total);
        $this->assertEquals(50.00, (float) $venta->detalles->first()->precio);
    }

    public function test_productos_repetidos_se_suman_al_validar_stock(): void
    {
        ['vendedor' => $v, 'producto' => $p] = $this->crearFerreteria(); // stock 10

        $this->actingAs($v)->post('/pos', [
            'tipo_comprobante' => 'ticket', 'metodo_pago' => 'efectivo',
            'items' => json_encode([['id' => $p->id, 'cantidad' => 6], ['id' => $p->id, 'cantidad' => 6]]),
        ])->assertSessionHas('error');

        $this->assertEquals(10, (float) $p->fresh()->stock);
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_no_se_vende_un_producto_de_otra_ferreteria(): void
    {
        ['vendedor' => $vA] = $this->crearFerreteria('a');
        ['producto' => $pB] = $this->crearFerreteria('b');

        $this->actingAs($vA)->post('/pos', [
            'tipo_comprobante' => 'ticket', 'metodo_pago' => 'efectivo',
            'items' => json_encode([['id' => $pB->id, 'cantidad' => 1]]),
        ])->assertSessionHas('error');

        $this->assertEquals(10, (float) $pB->fresh()->stock);
    }

    public function test_venta_a_credito_exige_cliente_y_genera_saldo(): void
    {
        ['vendedor' => $v, 'producto' => $p, 'cliente' => $c] = $this->crearFerreteria();

        $this->actingAs($v)->post('/pos', [
            'tipo_comprobante' => 'ticket', 'metodo_pago' => 'credito',
            'items' => json_encode([['id' => $p->id, 'cantidad' => 1]]),
        ])->assertSessionHas('error');

        $this->actingAs($v)->post('/pos', [
            'cliente_id' => $c->id, 'tipo_comprobante' => 'ticket', 'metodo_pago' => 'credito',
            'items' => json_encode([['id' => $p->id, 'cantidad' => 1]]),
        ])->assertSessionHas('ok');

        $this->assertEquals(50.00, (float) Venta::withoutGlobalScopes()->firstOrFail()->saldo);
    }
}
