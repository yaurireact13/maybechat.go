<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Cotizacion;
use App\Models\Empresa;
use App\Models\MovimientoInventario;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * DashboardDemoSeeder
 * -------------------------------------------------------------------------
 * Agrega 10 registros a cada módulo del sistema con fechas distribuidas
 * (mes actual + últimos 6 meses) para que TODOS los paneles y gráficos del
 * dashboard muestren información representativa. En especial garantiza
 * ventas del MES EN CURSO para que el gráfico "Ventas por categoría" se
 * llene (deja de mostrar "Sin ventas este mes").
 *
 * Es seguro ejecutarlo varias veces: usa un sufijo único por corrida para
 * no colisionar con códigos/números ya existentes.
 *
 * Ejecutar con:  php artisan db:seed --class=DashboardDemoSeeder
 */
class DashboardDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Empresa principal (la primera creada = Ferretería El Tornillo Feliz).
        $empresa = Empresa::orderBy('id')->first();
        if (! $empresa) {
            $this->command?->error('No existe ninguna empresa. Ejecuta primero: php artisan migrate --seed');
            return;
        }

        $empresaId = $empresa->id;
        $userId = User::where('empresa_id', $empresaId)->orderBy('id')->value('id') ?? 1;
        $tk = strtoupper(substr(uniqid(), -5)); // token único por corrida

        DB::transaction(function () use ($empresaId, $userId, $tk) {

            // ============================================================
            // 1) 10 CATEGORÍAS (nombres reales de ferretería)
            // ============================================================
            $nombresCat = [
                'Herramientas Manuales', 'Herramientas Eléctricas', 'Tornillería y Fijaciones',
                'Pinturas y Acabados', 'Electricidad', 'Gasfitería', 'Construcción',
                'Seguridad Industrial', 'Adhesivos y Selladores', 'Jardinería',
            ];
            $categorias = [];
            foreach ($nombresCat as $n) {
                $categorias[] = Categoria::create([
                    'empresa_id' => $empresaId,
                    'nombre' => $n . " ({$tk})",
                    'activo' => true,
                ]);
            }

            // ============================================================
            // 2) 10 PRODUCTOS (uno por categoría, con precios y stock real)
            // ============================================================
            $prodData = [
                ['Juego de llaves mixtas 8 pzas', 45.00, 79.90, 40, 8],
                ['Rotomartillo SDS 800W', 210.00, 329.90, 9, 3],
                ['Perno hexagonal 3/8" x100', 12.00, 21.50, 120, 25],
                ['Esmalte sintético 1 gal', 34.00, 54.90, 30, 10],
                ['Interruptor doble empotrable', 5.50, 10.90, 80, 20],
                ['Tubo PVC desagüe 2" x 3m', 14.00, 23.90, 6, 12],
                ['Cemento portland 42.5kg', 27.00, 34.90, 90, 20],
                ['Lentes de seguridad antiempaño', 6.00, 12.90, 55, 15],
                ['Silicona neutra transparente 280ml', 9.00, 16.90, 4, 10],
                ['Manguera reforzada 1/2" x 15m', 28.00, 46.90, 18, 6],
            ];
            $productos = [];
            foreach ($prodData as $i => [$nom, $pc, $pv, $stock, $min]) {
                $productos[] = Producto::create([
                    'empresa_id' => $empresaId,
                    'categoria_id' => $categorias[$i]->id,
                    'codigo' => "PD-{$tk}-" . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'nombre' => $nom,
                    'precio_compra' => $pc,
                    'precio_venta' => $pv,
                    'stock' => $stock,
                    'stock_minimo' => $min,
                    'activo' => true,
                ]);
            }

            // ============================================================
            // 3) 10 CLIENTES
            // ============================================================
            $nombresCli = [
                'Constructora Los Andes SAC', 'Rosa Huamán Torres', 'Pedro Castillo Ríos',
                'Inmobiliaria Sol Naciente EIRL', 'Luis Fernández Vega', 'Maestro Obras del Sur SAC',
                'Ana Quispe Mamani', 'Servicios Eléctricos JR SRL', 'Carmen Díaz Ponce',
                'Grupo Edifica Perú SAC',
            ];
            $clientes = [];
            foreach ($nombresCli as $i => $n) {
                $esRuc = str_contains($n, 'SAC') || str_contains($n, 'EIRL') || str_contains($n, 'SRL');
                $clientes[] = Cliente::create([
                    'empresa_id' => $empresaId,
                    'tipo_documento' => $esRuc ? 'RUC' : 'DNI',
                    'numero_documento' => $esRuc ? '20' . rand(100000000, 999999999) : (string) rand(40000000, 79999999),
                    'nombre' => $n,
                    'telefono' => '9' . rand(10000000, 99999999),
                    'activo' => true,
                ]);
            }

            // ============================================================
            // 4) 10 PROVEEDORES
            // ============================================================
            $nombresProv = [
                'Aceros y Fierros del Perú SAC', 'Distribuidora Bosch Andina EIRL',
                'Pinturas Andinas SRL', 'Importaciones Eléctricas Lima SAC',
                'Gasfitería Total Distribuciones', 'Cementos y Agregados SAC',
                'Equipos de Seguridad Pro SAC', 'Adhesivos Industriales del Sur',
                'Herramientas Truper Perú', 'Comercial El Constructor EIRL',
            ];
            $proveedores = [];
            foreach ($nombresProv as $n) {
                $proveedores[] = Proveedor::create([
                    'empresa_id' => $empresaId,
                    'razon_social' => $n,
                    'ruc' => '20' . rand(100000000, 999999999),
                    'telefono' => '01-' . rand(2000000, 7999999),
                    'activo' => true,
                ]);
            }

            // ============================================================
            // 5) 10 VENTAS con fechas distribuidas
            //    - 5 en el MES ACTUAL (distintos días y horas) -> gráfico categorías
            //    - 5 en los meses -1 .. -5 -> gráfico Ingresos vs Egresos (6M)
            //    Algunas a crédito -> alimentan Cuentas por Cobrar
            // ============================================================
            $fechasVenta = [];
            // Mes actual: 5 ventas en días y horas variados (7:00 a 20:00)
            for ($k = 0; $k < 5; $k++) {
                $fechasVenta[] = Carbon::now()->startOfMonth()
                    ->addDays(rand(0, max(0, Carbon::now()->day - 1)))
                    ->setTime(rand(7, 20), rand(0, 59));
            }
            // Meses anteriores: uno por cada mes -1..-5
            for ($m = 1; $m <= 5; $m++) {
                $fechasVenta[] = Carbon::now()->subMonths($m)
                    ->startOfMonth()->addDays(rand(1, 25))
                    ->setTime(rand(7, 20), rand(0, 59));
            }

            $vc = 1;
            foreach ($fechasVenta as $idx => $fecha) {
                $aCredito = in_array($idx, [1, 4, 7]); // 3 ventas a crédito
                $cliente = $clientes[array_rand($clientes)];

                $venta = Venta::create([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $cliente->id,
                    'user_id' => $userId,
                    'numero' => "V-{$tk}-" . str_pad($vc++, 4, '0', STR_PAD_LEFT),
                    'tipo_comprobante' => ['ticket', 'boleta', 'factura'][rand(0, 2)],
                    'fecha' => $fecha,
                    'metodo_pago' => $aCredito ? 'credito' : ['efectivo', 'tarjeta', 'yape', 'transferencia'][rand(0, 3)],
                    'estado' => 'completada',
                ]);

                $total = 0;
                foreach ((array) array_rand($productos, rand(2, 4)) as $pi) {
                    $p = $productos[$pi];
                    $cant = rand(1, 6);
                    $sub = round($cant * $p->precio_venta, 2);
                    VentaDetalle::create([
                        'venta_id' => $venta->id,
                        'producto_id' => $p->id,
                        'cantidad' => $cant,
                        'precio' => $p->precio_venta,
                        'subtotal' => $sub,
                    ]);
                    $total += $sub;
                }

                $subt = round($total / 1.18, 2);
                $saldo = 0;
                $venc = null;
                if ($aCredito) {
                    $venc = $fecha->copy()->addDays(30)->toDateString();
                    $pagado = round($total * [0, 0.4, 0.5][rand(0, 2)], 2);
                    $saldo = round($total - $pagado, 2);
                    if ($pagado > 0) {
                        Pago::create([
                            'empresa_id' => $empresaId,
                            'tipo' => 'cobro',
                            'venta_id' => $venta->id,
                            'user_id' => $userId,
                            'monto' => $pagado,
                            'metodo_pago' => ['efectivo', 'transferencia', 'yape'][rand(0, 2)],
                            'fecha' => $fecha->copy()->addDays(rand(3, 20)),
                            'referencia' => 'OP-' . rand(10000, 99999),
                        ]);
                    }
                }

                $venta->update([
                    'subtotal' => $subt,
                    'impuesto' => round($total - $subt, 2),
                    'total' => $total,
                    'saldo' => $saldo,
                    'fecha_vencimiento' => $venc,
                ]);
            }

            // ============================================================
            // 6) 10 COMPRAS distribuidas en 6 meses (gráfico Egresos)
            //    Algunas a crédito -> Cuentas por Pagar
            // ============================================================
            $cc = 1;
            for ($i = 0; $i < 10; $i++) {
                // Distribuye: 4 en el mes actual/reciente, 6 en meses anteriores
                if ($i < 4) {
                    $fecha = Carbon::now()->subDays(rand(0, 25));
                } else {
                    $fecha = Carbon::now()->subMonths(rand(1, 5))->startOfMonth()->addDays(rand(1, 25));
                }
                $aCredito = in_array($i, [2, 5, 8]); // 3 compras a crédito
                $prov = $proveedores[array_rand($proveedores)];

                $compra = Compra::create([
                    'empresa_id' => $empresaId,
                    'proveedor_id' => $prov->id,
                    'user_id' => $userId,
                    'numero' => "C-{$tk}-" . str_pad($cc++, 4, '0', STR_PAD_LEFT),
                    'fecha' => $fecha->toDateString(),
                    'estado' => 'recibida',
                    'a_credito' => $aCredito,
                ]);

                $total = 0;
                foreach ((array) array_rand($productos, rand(2, 4)) as $pi) {
                    $p = $productos[$pi];
                    $cant = rand(8, 25);
                    $sub = round($cant * $p->precio_compra, 2);
                    CompraDetalle::create([
                        'compra_id' => $compra->id,
                        'producto_id' => $p->id,
                        'cantidad' => $cant,
                        'precio' => $p->precio_compra,
                        'subtotal' => $sub,
                    ]);
                    $total += $sub;
                }

                $subt = round($total / 1.18, 2);
                $saldo = 0;
                $venc = null;
                if ($aCredito) {
                    $venc = $fecha->copy()->addDays(30)->toDateString();
                    $pagado = round($total * [0, 0.5, 0.6][rand(0, 2)], 2);
                    $saldo = round($total - $pagado, 2);
                    if ($pagado > 0) {
                        Pago::create([
                            'empresa_id' => $empresaId,
                            'tipo' => 'pago',
                            'compra_id' => $compra->id,
                            'user_id' => $userId,
                            'monto' => $pagado,
                            'metodo_pago' => ['transferencia', 'cheque', 'deposito'][rand(0, 2)],
                            'fecha' => $fecha->copy()->addDays(rand(3, 20)),
                            'referencia' => 'PAG-' . rand(10000, 99999),
                        ]);
                    }
                }

                $compra->update([
                    'subtotal' => $subt,
                    'impuesto' => round($total - $subt, 2),
                    'total' => $total,
                    'saldo' => $saldo,
                    'fecha_vencimiento' => $venc,
                ]);
            }

            // ============================================================
            // 7) 10 COTIZACIONES (últimos ~40 días, estados variados)
            // ============================================================
            $qc = 1;
            $estadosCot = ['vigente', 'aceptada', 'vencida'];
            for ($i = 0; $i < 10; $i++) {
                $fecha = Carbon::now()->subDays(rand(0, 40));
                $cliente = $clientes[array_rand($clientes)];

                $cot = Cotizacion::create([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $cliente->id,
                    'user_id' => $userId,
                    'numero' => "COT-{$tk}-" . str_pad($qc++, 4, '0', STR_PAD_LEFT),
                    'fecha' => $fecha->toDateString(),
                    'vencimiento' => $fecha->copy()->addDays(15)->toDateString(),
                    'estado' => $estadosCot[array_rand($estadosCot)],
                ]);

                $total = 0;
                foreach ((array) array_rand($productos, rand(2, 4)) as $pi) {
                    $p = $productos[$pi];
                    $cant = rand(1, 5);
                    $sub = round($cant * $p->precio_venta, 2);
                    DB::table('cotizacion_detalles')->insert([
                        'cotizacion_id' => $cot->id,
                        'producto_id' => $p->id,
                        'cantidad' => $cant,
                        'precio' => $p->precio_venta,
                        'subtotal' => $sub,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $total += $sub;
                }
                $cot->update(['total' => $total]);
            }

            // ============================================================
            // 8) 10 MOVIMIENTOS DE INVENTARIO (Kardex, últimos 60 días)
            // ============================================================
            $tipos = ['entrada', 'salida', 'ajuste'];
            for ($i = 0; $i < 10; $i++) {
                $p = $productos[array_rand($productos)];
                MovimientoInventario::create([
                    'empresa_id' => $empresaId,
                    'producto_id' => $p->id,
                    'user_id' => $userId,
                    'tipo' => $tipos[array_rand($tipos)],
                    'cantidad' => rand(1, 20),
                    'motivo' => 'Movimiento de demostración',
                    'fecha' => Carbon::now()->subDays(rand(0, 60))->setTime(rand(8, 18), rand(0, 59)),
                ]);
            }
        });

        $this->command?->info("✅ DashboardDemoSeeder: 10 registros por módulo agregados (token {$tk}).");
        $this->command?->info('   Incluye ventas del mes actual para el gráfico "Ventas por categoría".');
    }
}
