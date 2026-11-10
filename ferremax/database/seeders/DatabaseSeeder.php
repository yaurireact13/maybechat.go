<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Empresa;
use App\Models\Marca;
use App\Models\MovimientoInventario;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\SuscripcionPago;
use App\Models\Unidad;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Cuenta Super Admin de la plataforma (no pertenece a ninguna empresa)
        $this->call(SuperAdminSeeder::class);

        $empresa = Empresa::create([
            'nombre' => 'Ferretería El Tornillo Feliz',
            'ruc' => '20512345678',
            'direccion' => 'Av. Los Constructores 1234, Lima',
            'telefono' => '01-456-7890',
            'email' => 'contacto@eltornillofeliz.com',
            'plan' => 'pro',
            'plan_vence' => Carbon::now()->addMonths(2)->toDateString(),
        ]);

        User::create([
            'empresa_id' => $empresa->id,
            'name' => 'Vito Administrador',
            'email' => 'admin@ferremax.com',
            'password' => Hash::make('admin123'),
            'rol' => 'admin',
        ]);

        $vendedor = User::create([
            'empresa_id' => $empresa->id,
            'name' => 'Carlos Vendedor',
            'email' => 'vendedor@ferremax.com',
            'password' => Hash::make('vendedor123'),
            'rol' => 'vendedor',
        ]);

        $cats = [];
        foreach (['Herramientas Manuales', 'Herramientas Eléctricas', 'Tornillería y Fijaciones', 'Pinturas y Acabados', 'Electricidad', 'Gasfitería', 'Construcción', 'Seguridad Industrial'] as $c) {
            $cats[$c] = Categoria::create(['empresa_id' => $empresa->id, 'nombre' => $c]);
        }

        $marcas = [];
        foreach (['Stanley', 'Bosch', 'Truper', 'DeWalt', 'Makita', '3M', 'Sika', 'Genérico'] as $m) {
            $marcas[$m] = Marca::create(['empresa_id' => $empresa->id, 'nombre' => $m]);
        }

        $unds = [];
        foreach ([['Unidad','UND'],['Caja','CJA'],['Metro','MT'],['Galón','GAL'],['Kilogramo','KG'],['Bolsa','BLS']] as [$n,$a]) {
            $unds[$a] = Unidad::create(['empresa_id' => $empresa->id, 'nombre' => $n, 'abreviatura' => $a]);
        }

        $proveedores = [];
        foreach ([
            ['Distribuidora Aceros del Sur SAC', '20498765432'],
            ['Importaciones Ferreteras Lima EIRL', '20567891234'],
            ['Comercial Tornillos & Más SA', '20345678912'],
        ] as [$rs, $ruc]) {
            $proveedores[] = Proveedor::create(['empresa_id' => $empresa->id, 'razon_social' => $rs, 'ruc' => $ruc, 'telefono' => '01-32' . rand(10000, 99999)]);
        }

        $clientes = [];
        foreach ([
            ['Cliente Varios', null, 'DNI'],
            ['Constructora Andina SAC', '20111222333', 'RUC'],
            ['Juan Pérez Gómez', '45678912', 'DNI'],
            ['María Rodríguez Silva', '41234567', 'DNI'],
            ['Inversiones El Maestro EIRL', '20444555666', 'RUC'],
        ] as [$n, $doc, $tipo]) {
            $clientes[] = Cliente::create(['empresa_id' => $empresa->id, 'nombre' => $n, 'numero_documento' => $doc, 'tipo_documento' => $tipo]);
        }

        $productosData = [
            ['P-001', 'Martillo de uña 16oz Stanley', 'Herramientas Manuales', 'Stanley', 'UND', 25.00, 39.90, 45, 10],
            ['P-002', 'Taladro percutor 650W Bosch', 'Herramientas Eléctricas', 'Bosch', 'UND', 180.00, 269.90, 12, 5],
            ['P-003', 'Tornillo autorroscante 1" x100', 'Tornillería y Fijaciones', 'Genérico', 'CJA', 8.00, 14.50, 150, 30],
            ['P-004', 'Pintura látex blanco', 'Pinturas y Acabados', 'Genérico', 'GAL', 32.00, 49.90, 28, 10],
            ['P-005', 'Cable THW 14 AWG', 'Electricidad', 'Genérico', 'MT', 1.20, 2.20, 500, 100],
            ['P-006', 'Llave de paso 1/2" PVC', 'Gasfitería', 'Genérico', 'UND', 6.50, 11.90, 60, 15],
            ['P-007', 'Cemento Sol Tipo I 42.5kg', 'Construcción', 'Genérico', 'BLS', 26.50, 33.90, 80, 20],
            ['P-008', 'Amoladora angular 4.5" Makita', 'Herramientas Eléctricas', 'Makita', 'UND', 220.00, 319.90, 8, 3],
            ['P-009', 'Casco de seguridad blanco 3M', 'Seguridad Industrial', '3M', 'UND', 18.00, 29.90, 35, 10],
            ['P-010', 'Destornillador plano 6" Truper', 'Herramientas Manuales', 'Truper', 'UND', 7.00, 12.90, 3, 10],
            ['P-011', 'Sierra circular 7.25" DeWalt', 'Herramientas Eléctricas', 'DeWalt', 'UND', 380.00, 549.90, 5, 2],
            ['P-012', 'Sika Flex sellador 300ml', 'Construcción', 'Sika', 'UND', 22.00, 34.90, 2, 8],
            ['P-013', 'Cinta aislante 3M negra', 'Electricidad', '3M', 'UND', 2.50, 4.90, 200, 50],
            ['P-014', 'Guantes de cuero reforzado', 'Seguridad Industrial', 'Truper', 'UND', 9.00, 15.90, 40, 12],
            ['P-015', 'Tubería PVC 1/2" x 5m', 'Gasfitería', 'Genérico', 'UND', 8.50, 13.90, 4, 15],
        ];

        $productos = [];
        foreach ($productosData as [$cod, $nom, $cat, $marca, $und, $pc, $pv, $stock, $min]) {
            $productos[] = Producto::create([
                'empresa_id' => $empresa->id,
                'categoria_id' => $cats[$cat]->id,
                'marca_id' => $marcas[$marca]->id,
                'unidad_id' => $unds[$und]->id,
                'codigo' => $cod,
                'nombre' => $nom,
                'precio_compra' => $pc,
                'precio_venta' => $pv,
                'stock' => $stock,
                'stock_minimo' => $min,
            ]);
        }

        // Ventas de los últimos 6 meses para gráficas
        $contador = 1;
        for ($d = 180; $d >= 0; $d--) {
            $fecha = Carbon::now()->subDays($d);
            $numVentas = rand(1, 6);
            for ($v = 0; $v < $numVentas; $v++) {
                $venta = Venta::create([
                    'empresa_id' => $empresa->id,
                    'cliente_id' => $clientes[array_rand($clientes)]->id,
                    'user_id' => $vendedor->id,
                    'numero' => 'V-' . str_pad($contador++, 6, '0', STR_PAD_LEFT),
                    'tipo_comprobante' => ['ticket', 'boleta', 'factura'][rand(0, 2)],
                    'fecha' => $fecha->copy()->setTime(rand(8, 19), rand(0, 59)),
                    'metodo_pago' => ['efectivo', 'tarjeta', 'yape', 'transferencia'][rand(0, 3)],
                ]);
                $total = 0;
                $items = rand(1, 4);
                for ($i = 0; $i < $items; $i++) {
                    $p = $productos[array_rand($productos)];
                    $cant = rand(1, 5);
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
                $sub = round($total / 1.18, 2);
                $venta->update(['subtotal' => $sub, 'impuesto' => round($total - $sub, 2), 'total' => $total]);
            }
        }

        // ===== Cuentas por cobrar: ventas a crédito con abonos parciales =====
        // Clientes con documento (se excluye "Cliente Varios")
        $clientesCredito = array_slice($clientes, 1);
        $correlativoCredito = $contador;
        $cxc = [
            // [días desde hoy de emisión, días de plazo, % ya cobrado]
            [40, 30, 0.5],   // vencida, abono parcial
            [25, 30, 0],     // por vencer, sin pagos
            [60, 30, 0.3],   // vencida, abono parcial
            [10, 45, 0],     // por vencer
            [50, 30, 1.0],   // ya pagada (no aparece en pendientes)
            [5, 60, 0.25],   // reciente, abono parcial
        ];
        foreach ($cxc as $idx => [$emit, $plazo, $pct]) {
            $cliente = $clientesCredito[$idx % count($clientesCredito)];
            $fecha = Carbon::now()->subDays($emit)->setTime(rand(9, 18), rand(0, 59));
            $venta = Venta::create([
                'empresa_id' => $empresa->id,
                'cliente_id' => $cliente->id,
                'user_id' => $vendedor->id,
                'numero' => 'V-' . str_pad($correlativoCredito++, 6, '0', STR_PAD_LEFT),
                'tipo_comprobante' => 'factura',
                'fecha' => $fecha,
                'metodo_pago' => 'credito',
                'estado' => 'completada',
                'fecha_vencimiento' => $fecha->copy()->addDays($plazo)->toDateString(),
            ]);
            $total = 0;
            for ($i = 0; $i < rand(2, 4); $i++) {
                $p = $productos[array_rand($productos)];
                $cant = rand(2, 8);
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
            $pagado = round($total * $pct, 2);
            $venta->update([
                'subtotal' => $subt,
                'impuesto' => round($total - $subt, 2),
                'total' => $total,
                'saldo' => round($total - $pagado, 2),
            ]);
            if ($pagado > 0) {
                Pago::create([
                    'empresa_id' => $empresa->id,
                    'tipo' => 'cobro',
                    'venta_id' => $venta->id,
                    'user_id' => $vendedor->id,
                    'monto' => $pagado,
                    'metodo_pago' => ['efectivo', 'transferencia', 'yape'][rand(0, 2)],
                    'fecha' => $fecha->copy()->addDays(rand(2, $plazo)),
                    'referencia' => 'OP-' . rand(10000, 99999),
                ]);
            }
        }

        // ===== Cuentas por pagar: compras a crédito con abonos parciales =====
        $compraCont = 1;
        $cxp = [
            [35, 30, 0.4],   // vencida, abono parcial
            [20, 30, 0],     // por vencer
            [55, 45, 0.6],   // vencida, abono parcial
            [8, 30, 0],      // reciente
        ];
        foreach ($cxp as $idx => [$emit, $plazo, $pct]) {
            $prov = $proveedores[$idx % count($proveedores)];
            $fecha = Carbon::now()->subDays($emit);
            $compra = Compra::create([
                'empresa_id' => $empresa->id,
                'proveedor_id' => $prov->id,
                'user_id' => $vendedor->id,
                'numero' => 'C-' . str_pad($compraCont++, 6, '0', STR_PAD_LEFT),
                'fecha' => $fecha->toDateString(),
                'estado' => 'recibida',
                'a_credito' => true,
                'fecha_vencimiento' => $fecha->copy()->addDays($plazo)->toDateString(),
            ]);
            $total = 0;
            for ($i = 0; $i < rand(2, 4); $i++) {
                $p = $productos[array_rand($productos)];
                $cant = rand(5, 20);
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
            $pagado = round($total * $pct, 2);
            $compra->update([
                'subtotal' => $subt,
                'impuesto' => round($total - $subt, 2),
                'total' => $total,
                'saldo' => round($total - $pagado, 2),
            ]);
            if ($pagado > 0) {
                Pago::create([
                    'empresa_id' => $empresa->id,
                    'tipo' => 'pago',
                    'compra_id' => $compra->id,
                    'user_id' => $vendedor->id,
                    'monto' => $pagado,
                    'metodo_pago' => ['transferencia', 'cheque', 'deposito'][rand(0, 2)],
                    'fecha' => $fecha->copy()->addDays(rand(2, $plazo)),
                    'referencia' => 'PAG-' . rand(10000, 99999),
                ]);
            }
        }

        // Movimientos de inventario recientes
        foreach (array_slice($productos, 0, 8) as $p) {
            MovimientoInventario::create([
                'empresa_id' => $empresa->id,
                'producto_id' => $p->id,
                'user_id' => $vendedor->id,
                'tipo' => ['entrada', 'salida'][rand(0, 1)],
                'cantidad' => rand(1, 20),
                'motivo' => 'Movimiento de demostración',
                'fecha' => Carbon::now()->subDays(rand(0, 10)),
            ]);
        }

        // ===== Otras ferreterías demo (para poblar el panel de plataforma) =====
        $otras = [
            ['Ferretería Constructor Total', '20623456781', 'premium', true, 4],
            ['Ferremundo Express', '20734567812', 'pro', true, 2],
            ['El Tornillo de Oro', '20845678123', 'basico', true, 1],
            ['Herramientas del Norte', '20956781234', 'basico', false, 0],
            ['MegaFierro SAC', '20167812345', 'pro', true, 3],
        ];
        foreach ($otras as $i => [$nombre, $ruc, $plan, $activa, $mesesAntiguedad]) {
            $emp = Empresa::create([
                'nombre' => $nombre,
                'ruc' => $ruc,
                'telefono' => '01-' . rand(2000000, 7999999),
                'email' => 'contacto@' . str_replace(' ', '', strtolower($nombre)) . '.com',
                'plan' => $plan,
                'plan_vence' => $activa
                    ? Carbon::now()->addMonths(rand(1, 6))->toDateString()
                    : Carbon::now()->subDays(rand(10, 40))->toDateString(),
                'activo' => $activa,
                'suspendido_motivo' => $activa ? null : 'Falta de pago de la suscripción',
                'created_at' => Carbon::now()->subMonths($mesesAntiguedad)->subDays(rand(0, 20)),
            ]);

            User::create([
                'empresa_id' => $emp->id,
                'name' => 'Administrador ' . explode(' ', $nombre)[0],
                'email' => 'admin' . ($i + 2) . '@ferremax.com',
                'password' => Hash::make('admin123'),
                'rol' => 'admin',
                'activo' => $activa,
            ]);

            // Catálogos mínimos para que la ferretería sea operable
            foreach (['Herramientas Manuales', 'Tornillería y Fijaciones', 'Electricidad'] as $cat) {
                Categoria::create(['empresa_id' => $emp->id, 'nombre' => $cat]);
            }
            Unidad::create(['empresa_id' => $emp->id, 'nombre' => 'Unidad', 'abreviatura' => 'UND']);
            Cliente::create(['empresa_id' => $emp->id, 'nombre' => 'Cliente Varios', 'tipo_documento' => 'DNI']);
        }

        // ===== Historial de pagos de suscripción (facturación SaaS) =====
        $admin = User::where('email', 'superadmin@ferremax.com')->first();
        foreach (Empresa::all() as $emp) {
            if (! $emp->plan_vence) {
                continue;
            }
            $precio = Empresa::PRECIOS_PLAN[$emp->plan] ?? 49;
            // Genera 3 mensualidades previas al vencimiento actual
            $hasta = $emp->plan_vence->copy();
            for ($k = 0; $k < 3; $k++) {
                $desde = $hasta->copy()->subMonth();
                SuscripcionPago::create([
                    'empresa_id' => $emp->id,
                    'plan' => $emp->plan,
                    'monto' => $precio,
                    'meses' => 1,
                    'periodo_desde' => $desde->toDateString(),
                    'periodo_hasta' => $hasta->toDateString(),
                    'fecha_pago' => $desde->toDateString(),
                    'metodo' => ['transferencia', 'tarjeta', 'deposito'][rand(0, 2)],
                    'referencia' => 'SUS-' . rand(10000, 99999),
                    'registrado_por' => $admin?->id,
                ]);
                $hasta = $desde;
            }
        }

        // Comprobantes electronicos demo (modo simulado) para las ventas boleta/factura
        $this->call(ComprobantesDemoSeeder::class);
    }
}
