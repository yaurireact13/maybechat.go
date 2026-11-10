<?php

namespace Database\Seeders;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Cotizacion;
use App\Models\Empresa;
use App\Models\Marca;
use App\Models\MovimientoInventario;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Unidad;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * DashboardBoostSeeder
 * --------------------------------------------------------------------------
 * Agrega ~10 registros a cada módulo del sistema con fechas distribuidas en
 * los últimos 6 meses e incluyendo el MES ACTUAL, de modo que todos los
 * paneles y gráficos del Dashboard queden poblados:
 *   - KPIs (Ventas de hoy / del mes, inventario, stock bajo)
 *   - Ingresos vs Egresos (6 meses)
 *   - Ventas por categoría (mes actual)  <-- el gráfico de la flecha roja
 *   - Ventas por hora del día (últimos 30 días, horario 7:00-21:00)
 *   - Estado de cartera / Cuentas por Cobrar y por Pagar (con saldos vencidos)
 *
 * Es re-ejecutable: cada corrida usa un sufijo único, así que agrega un nuevo
 * lote de datos sin chocar con códigos/números anteriores.
 *
 *   php artisan db:seed --class=DashboardBoostSeeder
 */
class DashboardBoostSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::first();
        if (! $empresa) {
            $this->command?->error('No hay ninguna empresa. Ejecuta primero el seeder principal.');

            return;
        }

        $empresaId = $empresa->id;

        // Usuario dueño de los registros (primer usuario con empresa, o el primero).
        $userId = User::whereNotNull('empresa_id')->value('id')
            ?? User::value('id')
            ?? 1;

        $sfx = strtoupper(substr(uniqid(), -4)); // sufijo único por corrida
        $igv = 1.18;

        DB::transaction(function () use ($empresaId, $userId, $sfx, $igv) {

            /* ============================================================
             * 1) CATÁLOGOS BÁSICOS (10 c/u): categorías, marcas, unidades
             * ============================================================ */
            $nombresCat = [
                'Herramientas Manuales', 'Herramientas Eléctricas', 'Fontanería',
                'Electricidad', 'Pinturas y Solventes', 'Ferretería General',
                'Construcción', 'Seguridad Industrial', 'Jardinería', 'Adhesivos y Sellantes',
            ];
            $categorias = [];
            foreach ($nombresCat as $i => $n) {
                $categorias[] = Categoria::create([
                    'empresa_id' => $empresaId,
                    'nombre' => "$n [$sfx]",
                    'descripcion' => "Categoría de $n",
                    'activo' => true,
                ])->id;
            }

            $nombresMarca = ['Stanley', 'Bosch', 'Truper', 'DeWalt', 'Makita', 'Pavco', 'CPP', 'Vencedor', '3M', 'Sika'];
            $marcas = [];
            foreach ($nombresMarca as $n) {
                $marcas[] = Marca::create([
                    'empresa_id' => $empresaId,
                    'nombre' => "$n [$sfx]",
                    'activo' => true,
                ])->id;
            }

            $nombresUni = [
                ['Unidad', 'UND'], ['Caja', 'CJA'], ['Metro', 'MT'], ['Kilogramo', 'KG'],
                ['Litro', 'LT'], ['Galón', 'GAL'], ['Docena', 'DOC'], ['Rollo', 'ROL'],
                ['Bolsa', 'BLS'], ['Juego', 'JGO'],
            ];
            $unidades = [];
            foreach ($nombresUni as $u) {
                $unidades[] = Unidad::create([
                    'empresa_id' => $empresaId,
                    'nombre' => $u[0],
                    'abreviatura' => $u[1].substr($sfx, 0, 1),
                ])->id;
            }

            /* ============================================================
             * 2) PROVEEDORES (10) y CLIENTES (10)
             * ============================================================ */
            $razones = ['Distribuidora', 'Importaciones', 'Comercial', 'Grupo', 'Corporación',
                'Ferrecenter', 'Suministros', 'Represent.', 'Almacenes', 'Mayorista'];
            $proveedores = [];
            foreach (range(1, 10) as $i) {
                $proveedores[] = Proveedor::create([
                    'empresa_id' => $empresaId,
                    'razon_social' => $razones[$i - 1]." $sfx S.A.C.",
                    'ruc' => '20'.rand(100000000, 599999999),
                    'telefono' => '01'.rand(2000000, 7999999),
                    'email' => 'ventas'.strtolower($sfx).$i.'@proveedor.com',
                    'direccion' => 'Av. Industrial '.rand(100, 999),
                    'contacto' => 'Contacto '.$i,
                    'activo' => true,
                ])->id;
            }

            $nombresCli = ['Juan Pérez', 'María Gómez', 'Carlos Ríos', 'Ana Torres', 'Luis Díaz',
                'Rosa Vega', 'Pedro Salas', 'Lucía Mora', 'Jorge Campos', 'Elena Ruiz'];
            $clientes = [];
            foreach ($nombresCli as $i => $n) {
                $clientes[] = Cliente::create([
                    'empresa_id' => $empresaId,
                    'tipo_documento' => $i % 3 === 0 ? 'RUC' : 'DNI',
                    'numero_documento' => $i % 3 === 0 ? '20'.rand(100000000, 599999999) : (string) rand(10000000, 79999999),
                    'nombre' => "$n [$sfx]",
                    'telefono' => '9'.rand(10000000, 99999999),
                    'email' => 'cliente'.strtolower($sfx).$i.'@correo.com',
                    'direccion' => 'Jr. Los Olivos '.rand(100, 999),
                    'activo' => true,
                ])->id;
            }

            /* ============================================================
             * 3) PRODUCTOS (10) — cada uno con categoría, marca y unidad
             *    (dos con stock bajo para el panel "Stock bajo")
             * ============================================================ */
            $nombresProd = [
                'Martillo carpintero 16oz', 'Taladro percutor 1/2"', 'Llave stillson 14"',
                'Cinta aislante 3M', 'Pintura látex blanco 1gal', 'Juego destornilladores 6pz',
                'Cemento gris 42.5kg', 'Casco de seguridad', 'Manguera jardín 15m', 'Silicona transparente',
            ];
            $productos = [];
            foreach ($nombresProd as $i => $n) {
                $compra = rand(8, 80);
                $venta = round($compra * (1.35 + (rand(0, 40) / 100)), 2);
                $stockBajo = $i >= 8; // últimos 2 en stock bajo
                $productos[] = Producto::create([
                    'empresa_id' => $empresaId,
                    'categoria_id' => $categorias[$i % count($categorias)],
                    'marca_id' => $marcas[$i % count($marcas)],
                    'unidad_id' => $unidades[$i % count($unidades)],
                    'codigo' => "PB-$sfx-".str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'codigo_barras' => (string) rand(7500000000000, 7599999999999),
                    'nombre' => "$n [$sfx]",
                    'descripcion' => $n,
                    'precio_compra' => $compra,
                    'precio_venta' => $venta,
                    'stock' => $stockBajo ? rand(1, 4) : rand(40, 200),
                    'stock_minimo' => 5,
                    'ubicacion' => 'P-'.chr(65 + ($i % 6)).rand(1, 9),
                    'activo' => true,
                ]);
            }

            $metodos = ['efectivo', 'tarjeta', 'transferencia', 'yape', 'plin'];

            // Fechas objetivo: 5 en el mes actual + 5 repartidas en los 5 meses previos.
            $fechasVenta = [];
            for ($k = 0; $k < 5; $k++) {
                // Mes actual, entre el día 1 y hoy, en horario comercial (7-21h)
                $dia = Carbon::now()->startOfMonth()->addDays(rand(0, max(0, Carbon::now()->day - 1)));
                $fechasVenta[] = $dia->setTime(rand(7, 21), rand(0, 59));
            }
            for ($m = 1; $m <= 5; $m++) {
                $fechasVenta[] = Carbon::now()->subMonths($m)
                    ->setDay(rand(2, 26))->setTime(rand(7, 21), rand(0, 59));
            }

            /* ============================================================
             * 4) VENTAS (10) con detalles.  3 a crédito (cuentas x cobrar)
             * ============================================================ */
            foreach ($fechasVenta as $i => $fecha) {
                $esCredito = $i < 3; // 3 ventas a crédito
                $metodo = $esCredito ? 'credito' : $metodos[array_rand($metodos)];

                $venta = Venta::create([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $clientes[array_rand($clientes)],
                    'user_id' => $userId,
                    'numero' => "V-$sfx-".str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'tipo_comprobante' => ['ticket', 'boleta', 'factura'][rand(0, 2)],
                    'fecha' => $fecha,
                    'metodo_pago' => $metodo,
                    'subtotal' => 0, 'impuesto' => 0, 'descuento' => 0, 'total' => 0, 'saldo' => 0,
                    'estado' => 'completada',
                ]);

                $total = 0;
                $items = [];
                foreach (array_rand($productos, rand(2, 4)) as $pi) {
                    $p = $productos[$pi];
                    $cant = rand(1, 6);
                    $sub = round($cant * $p->precio_venta, 2);
                    $items[] = ['producto_id' => $p->id, 'cantidad' => $cant, 'precio' => $p->precio_venta, 'subtotal' => $sub];
                    $total += $sub;
                }
                foreach ($items as $it) {
                    VentaDetalle::create(['venta_id' => $venta->id] + $it);
                }

                $subtotal = round($total / $igv, 2);
                $saldo = 0;
                $venc = null;
                if ($esCredito) {
                    // Deja saldo pendiente; 1 vencida (fecha pasada), otras por vencer.
                    $saldo = round($total * (rand(40, 90) / 100), 2);
                    $venc = $i === 0
                        ? Carbon::now()->subDays(rand(5, 20))   // vencida
                        : Carbon::now()->addDays(rand(10, 30));  // vigente
                }

                $venta->update([
                    'subtotal' => $subtotal,
                    'impuesto' => round($total - $subtotal, 2),
                    'total' => $total,
                    'saldo' => $saldo,
                    'fecha_vencimiento' => $venc,
                ]);

                // Movimiento de inventario por la salida (venta) + descuenta stock
                foreach ($items as $it) {
                    MovimientoInventario::create([
                        'empresa_id' => $empresaId,
                        'producto_id' => $it['producto_id'],
                        'user_id' => $userId,
                        'tipo' => 'salida',
                        'cantidad' => $it['cantidad'],
                        'motivo' => 'Venta '.$venta->numero,
                        'fecha' => $fecha,
                    ]);
                    Producto::where('id', $it['producto_id'])->decrement('stock', $it['cantidad']);
                }

                // Registro de cobro (pago) por la parte pagada
                $pagado = round($total - $saldo, 2);
                if ($pagado > 0) {
                    Pago::create([
                        'empresa_id' => $empresaId,
                        'tipo' => 'cobro',
                        'venta_id' => $venta->id,
                        'user_id' => $userId,
                        'monto' => $pagado,
                        'metodo_pago' => $esCredito ? 'efectivo' : $metodo,
                        'fecha' => $fecha,
                        'referencia' => 'COB-'.$venta->numero,
                    ]);
                }
            }

            /* ============================================================
             * 5) COMPRAS (10) con detalles.  3 a crédito (cuentas x pagar)
             * ============================================================ */
            $fechasCompra = [];
            for ($k = 0; $k < 4; $k++) {
                $fechasCompra[] = Carbon::now()->startOfMonth()->addDays(rand(0, max(0, Carbon::now()->day - 1)));
            }
            for ($m = 1; $m <= 6; $m++) {
                $fechasCompra[] = Carbon::now()->subMonths($m)->setDay(rand(2, 26));
            }
            $fechasCompra = array_slice($fechasCompra, 0, 10);

            foreach ($fechasCompra as $i => $fecha) {
                $esCredito = $i < 3;
                $compra = Compra::create([
                    'empresa_id' => $empresaId,
                    'proveedor_id' => $proveedores[array_rand($proveedores)],
                    'user_id' => $userId,
                    'numero' => "C-$sfx-".str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'fecha' => $fecha->toDateString(),
                    'a_credito' => $esCredito,
                    'subtotal' => 0, 'impuesto' => 0, 'total' => 0, 'saldo' => 0,
                    'estado' => 'recibida',
                ]);

                $total = 0;
                $items = [];
                foreach (array_rand($productos, rand(2, 4)) as $pi) {
                    $p = $productos[$pi];
                    $cant = rand(10, 40);
                    $sub = round($cant * $p->precio_compra, 2);
                    $items[] = ['producto_id' => $p->id, 'cantidad' => $cant, 'precio' => $p->precio_compra, 'subtotal' => $sub];
                    $total += $sub;
                }
                foreach ($items as $it) {
                    DB::table('compra_detalles')->insert(['compra_id' => $compra->id] + $it + [
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    // La compra ingresa stock
                    Producto::where('id', $it['producto_id'])->increment('stock', $it['cantidad']);
                    MovimientoInventario::create([
                        'empresa_id' => $empresaId,
                        'producto_id' => $it['producto_id'],
                        'user_id' => $userId,
                        'tipo' => 'entrada',
                        'cantidad' => $it['cantidad'],
                        'motivo' => 'Compra '.$compra->numero,
                        'fecha' => $fecha,
                    ]);
                }

                $subtotal = round($total / $igv, 2);
                $saldo = 0;
                $venc = null;
                if ($esCredito) {
                    $saldo = round($total * (rand(50, 100) / 100), 2);
                    $venc = $i === 0
                        ? Carbon::now()->subDays(rand(5, 20))   // vencida
                        : Carbon::now()->addDays(rand(10, 30));
                }

                $compra->update([
                    'subtotal' => $subtotal,
                    'impuesto' => round($total - $subtotal, 2),
                    'total' => $total,
                    'saldo' => $saldo,
                    'fecha_vencimiento' => $venc,
                ]);

                $pagado = round($total - $saldo, 2);
                if ($pagado > 0) {
                    Pago::create([
                        'empresa_id' => $empresaId,
                        'tipo' => 'pago',
                        'compra_id' => $compra->id,
                        'user_id' => $userId,
                        'monto' => $pagado,
                        'metodo_pago' => 'transferencia',
                        'fecha' => $fecha,
                        'referencia' => 'PAG-'.$compra->numero,
                    ]);
                }
            }

            /* ============================================================
             * 6) COTIZACIONES (10) con detalles
             * ============================================================ */
            $estadosCot = ['vigente', 'aceptada', 'vencida', 'vigente', 'aceptada'];
            foreach (range(1, 10) as $i) {
                $fecha = Carbon::now()->subDays(rand(0, 40));
                $cot = Cotizacion::create([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $clientes[array_rand($clientes)],
                    'user_id' => $userId,
                    'numero' => "COT-$sfx-".str_pad($i, 4, '0', STR_PAD_LEFT),
                    'fecha' => $fecha->toDateString(),
                    'vencimiento' => (clone $fecha)->addDays(15)->toDateString(),
                    'total' => 0,
                    'estado' => $estadosCot[array_rand($estadosCot)],
                ]);

                $total = 0;
                foreach (array_rand($productos, rand(2, 4)) as $pi) {
                    $p = $productos[$pi];
                    $cant = rand(1, 8);
                    $sub = round($cant * $p->precio_venta, 2);
                    DB::table('cotizacion_detalles')->insert([
                        'cotizacion_id' => $cot->id,
                        'producto_id' => $p->id,
                        'cantidad' => $cant,
                        'precio' => $p->precio_venta,
                        'subtotal' => $sub,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $total += $sub;
                }
                $cot->update(['total' => $total]);
            }

            /* ============================================================
             * 7) MOVIMIENTOS DE INVENTARIO adicionales (ajustes) — 10
             * ============================================================ */
            foreach (range(1, 10) as $i) {
                $p = $productos[array_rand($productos)];
                MovimientoInventario::create([
                    'empresa_id' => $empresaId,
                    'producto_id' => $p->id,
                    'user_id' => $userId,
                    'tipo' => 'ajuste',
                    'cantidad' => rand(1, 8),
                    'motivo' => 'Ajuste de inventario '.$i,
                    'fecha' => Carbon::now()->subDays(rand(0, 60))->setTime(rand(8, 18), rand(0, 59)),
                ]);
            }

            /* ============================================================
             * 8) CAJAS (10) — aperturas/cierres de los últimos días
             * ============================================================ */
            foreach (range(1, 10) as $i) {
                $ap = Carbon::now()->subDays($i)->setTime(8, 0);
                $apertura = rand(100, 300);
                $ventasDia = rand(300, 1500);
                Caja::create([
                    'empresa_id' => $empresaId,
                    'user_id' => $userId,
                    'fecha_apertura' => $ap,
                    'fecha_cierre' => $i === 1 ? null : (clone $ap)->setTime(20, 0),
                    'monto_apertura' => $apertura,
                    'monto_cierre' => $i === 1 ? null : $apertura + $ventasDia,
                    'estado' => $i === 1 ? 'abierta' : 'cerrada',
                ]);
            }
        });

        $this->command?->info("DashboardBoostSeeder OK (lote $sfx): +10 registros por módulo con fechas variadas.");
    }
}
