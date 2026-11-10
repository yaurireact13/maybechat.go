<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Cotizacion;
use App\Models\MovimientoInventario;
use App\Models\Empresa;
use Carbon\Carbon;

class AddRecentDataSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::first();
        if (!$empresa) {
            return;
        }

        $empresaId = $empresa->id;
        $userId = 1; // From our tinker output
        $uuid = substr(uniqid(), -4);

        // Add 10 Categorias
        $categorias = [];
        for ($i = 1; $i <= 10; $i++) {
            $categorias[] = DB::table('categorias')->insertGetId([
                'empresa_id' => $empresaId,
                'nombre' => "Categoría Test $i $uuid",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add 10 Productos
        $productos = [];
        for ($i = 1; $i <= 10; $i++) {
            $producto = Producto::create([
                'empresa_id' => $empresaId,
                'categoria_id' => $categorias[array_rand($categorias)],
                'codigo' => 'PROD-T-' . $uuid . str_pad($i, 4, '0', STR_PAD_LEFT),
                'nombre' => "Producto Test $i $uuid",
                'precio_compra' => rand(10, 50),
                'precio_venta' => rand(60, 150),
                'stock' => rand(10, 100),
                'stock_minimo' => 5,
                'activo' => true,
            ]);
            $productos[] = $producto;
        }

        // Add 10 Clientes
        $clientes = [];
        for ($i = 1; $i <= 10; $i++) {
            $cliente = Cliente::create([
                'empresa_id' => $empresaId,
                'tipo_documento' => 'DNI',
                'numero_documento' => '8765' . $uuid . $i,
                'nombre' => "Cliente Test $i $uuid",
                'activo' => true,
            ]);
            $clientes[] = $cliente;
        }

        // Add 10 Proveedores
        $proveedores = [];
        for ($i = 1; $i <= 10; $i++) {
            $proveedor = Proveedor::create([
                'empresa_id' => $empresaId,
                'ruc' => '2012' . $uuid . '78' . $i,
                'razon_social' => "Proveedor Test $i $uuid",
                'activo' => true,
            ]);
            $proveedores[] = $proveedor;
        }

        // Add 10 Ventas (Scatter dates in the last 7 days and some older)
        for ($i = 1; $i <= 10; $i++) {
            // Distribute dates: 5 in the last 7 days, 5 in the last 6 months
            if ($i <= 5) {
                $fecha = Carbon::today()->subDays(rand(0, 6)); // Within last 7 days
            } else {
                $fecha = Carbon::today()->subMonths(rand(1, 5))->subDays(rand(1, 20)); // Within 6 months
            }

            $cliente = $clientes[array_rand($clientes)];
            
            $venta = Venta::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'user_id' => $userId,
                'numero' => 'V-TEST-' . $uuid . str_pad($i, 4, '0', STR_PAD_LEFT),
                'fecha' => $fecha,
                'metodo_pago' => array_rand(['efectivo' => 1, 'tarjeta' => 1, 'transferencia' => 1, 'yape' => 1]),
                'subtotal' => 0,
                'impuesto' => 0,
                'total' => 0,
                'saldo' => 0,
                'estado' => 'completada',
            ]);

            $total = 0;
            // Add 2 detalles per venta
            for ($j = 1; $j <= 2; $j++) {
                $producto = $productos[array_rand($productos)];
                $cantidad = rand(1, 5);
                $precio = $producto->precio_venta;
                $subtotal = $cantidad * $precio;

                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $subtotal,
                ]);

                $total += $subtotal;
            }

            $venta->update([
                'subtotal' => $total / 1.18,
                'impuesto' => $total - ($total / 1.18),
                'total' => $total,
            ]);
        }

        // Add 10 Compras
        for ($i = 1; $i <= 10; $i++) {
            $fecha = Carbon::today()->subDays(rand(0, 30));
            $proveedor = $proveedores[array_rand($proveedores)];

            $compra = Compra::create([
                'empresa_id' => $empresaId,
                'proveedor_id' => $proveedor->id,
                'user_id' => $userId,
                'numero' => 'F-TEST-' . $uuid . str_pad($i, 4, '0', STR_PAD_LEFT),
                'fecha' => $fecha,
                'a_credito' => false,
                'subtotal' => 0,
                'impuesto' => 0,
                'total' => 0,
                'saldo' => 0,
                'estado' => 'recibida',
            ]);

            $total = 0;
            for ($j = 1; $j <= 2; $j++) {
                $producto = $productos[array_rand($productos)];
                $cantidad = rand(10, 20);
                $precio = $producto->precio_compra;
                $subtotal = $cantidad * $precio;

                DB::table('compra_detalles')->insert([
                    'compra_id' => $compra->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $subtotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $total += $subtotal;
            }

            $compra->update([
                'subtotal' => $total / 1.18,
                'impuesto' => $total - ($total / 1.18),
                'total' => $total,
            ]);
        }

        // Add 10 Cotizaciones
        for ($i = 1; $i <= 10; $i++) {
            $fecha = Carbon::today()->subDays(rand(0, 15));
            $cliente = $clientes[array_rand($clientes)];

            $cotizacion = Cotizacion::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'user_id' => $userId,
                'numero' => 'C-TEST-' . $uuid . str_pad($i, 4, '0', STR_PAD_LEFT),
                'fecha' => $fecha,
                'vencimiento' => Carbon::today()->addDays(15),
                'total' => 0,
                'estado' => 'vigente',
            ]);

            $total = 0;
            for ($j = 1; $j <= 2; $j++) {
                $producto = $productos[array_rand($productos)];
                $cantidad = rand(1, 5);
                $precio = $producto->precio_venta;
                $subtotal = $cantidad * $precio;

                DB::table('cotizacion_detalles')->insert([
                    'cotizacion_id' => $cotizacion->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $subtotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $total += $subtotal;
            }

            $cotizacion->update([
                'total' => $total,
            ]);
        }

        // Add 10 Movimientos de Inventario
        for ($i = 1; $i <= 10; $i++) {
            $fecha = Carbon::today()->subDays(rand(0, 30));
            $producto = $productos[array_rand($productos)];

            MovimientoInventario::create([
                'empresa_id' => $empresaId,
                'producto_id' => $producto->id,
                'user_id' => $userId,
                'tipo' => array_rand(['entrada' => 1, 'salida' => 1, 'ajuste' => 1]),
                'cantidad' => rand(1, 10),
                'motivo' => 'Movimiento de prueba',
                'fecha' => $fecha,
            ]);
        }
    }
}
