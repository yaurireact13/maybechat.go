<?php

namespace Database\Seeders;

use App\Models\Caja;
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
use App\Models\Unidad;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * RecargaDashboardSeeder
 * ----------------------------------------------------------------------
 * Agrega datos DE DEMOSTRACIÓN RECIENTES para que todos los paneles y
 * gráficos del Dashboard muestren información:
 *   - Ventas del MES ACTUAL repartidas por categoría  -> "Ventas por categoría"
 *   - Ventas de los últimos 30 días con HORA real 7:00-21:00 -> "Ventas por hora"
 *   - Ingresos vs Egresos de los últimos 6 meses
 *   - Cuentas por cobrar / pagar (ventas y compras a crédito con abonos)
 *   - +10 registros por módulo: productos, clientes, proveedores, compras,
 *     cotizaciones, movimientos de inventario y aperturas de caja.
 *
 * Es aditivo e idempotente-por-lote: cada ejecución usa un TAG único, por lo
 * que se puede correr varias veces sin colisiones de números de documento.
 */
class RecargaDashboardSeeder extends Seeder
{
    public function run(): void
    {
        // Empresa objetivo = la del usuario administrador principal.
        $admin = User::where('email', 'admin@ferremax.com')->first();
        $empresa = $admin
            ? Empresa::find($admin->empresa_id)
            : Empresa::orderBy('id')->first();

        if (! $empresa) {
            $this->command->error('No se encontró la empresa principal. Ejecuta primero: php artisan migrate --seed');
            return;
        }

        $empresaId = $empresa->id;
        $userId = $admin?->id
            ?? User::where('empresa_id', $empresaId)->value('id');

        $tag = strtoupper(substr(uniqid(), -4));

        // ------------------------------------------------------------------
        // Catálogos base (reusar los existentes; crear si faltan)
        // ------------------------------------------------------------------
        $nombresCat = [
            'Herramientas Manuales', 'Herramientas Eléctricas', 'Tornillería y Fijaciones',
            'Pinturas y Acabados', 'Electricidad', 'Gasfitería', 'Construcción', 'Seguridad Industrial',
        ];
        $categorias = [];
        foreach ($nombresCat as $c) {
            $categorias[$c] = Categoria::firstOrCreate(
                ['empresa_id' => $empresaId, 'nombre' => $c],
                ['activo' => true]
            );
        }

        $unidad = Unidad::where('empresa_id', $empresaId)->first()
            ?? Unidad::create(['empresa_id' => $empresaId, 'nombre' => 'Unidad', 'abreviatura' => 'UND']);

        // ------------------------------------------------------------------
        // 10 PRODUCTOS nuevos (repartidos entre categorías reales)
        // ------------------------------------------------------------------
        $catalogoProductos = [
            ['Juego de llaves mixtas 8 pzas',       'Herramientas Manuales',    35, 59.90],
            ['Lijadora orbital 300W',               'Herramientas Eléctricas',  95, 149.90],
            ['Perno hexagonal 3/8 x100',            'Tornillería y Fijaciones',  9, 16.50],
            ['Esmalte sintético negro 1L',          'Pinturas y Acabados',      18, 29.90],
            ['Interruptor doble empotrar',          'Electricidad',              4, 8.90],
            ['Codo PVC 1/2" x paquete',             'Gasfitería',                3, 6.50],
            ['Fierro corrugado 1/2" x9m',           'Construcción',             28, 39.90],
            ['Lentes de seguridad antiempaño',      'Seguridad Industrial',      6, 12.90],
            ['Cinta métrica 5m profesional',        'Herramientas Manuales',    11, 19.90],
            ['Rotomartillo SDS 800W',               'Herramientas Eléctricas', 240, 359.90],
        ];
        $productosNuevos = [];
        foreach ($catalogoProductos as $i => [$nom, $cat, $pc, $pv]) {
            $productosNuevos[] = Producto::create([
                'empresa_id'   => $empresaId,
                'categoria_id' => $categorias[$cat]->id,
                'unidad_id'    => $unidad->id,
                'codigo'       => 'PD-' . $tag . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'nombre'       => $nom,
                'precio_compra'=> $pc,
                'precio_venta' => $pv,
                'stock'        => rand(15, 120),
                'stock_minimo' => 10,
                'activo'       => true,
            ]);
        }

        // Conjunto de productos para las ventas: nuevos + existentes con categoría,
        // así el gráfico "Ventas por categoría" queda bien repartido.
        $productosVenta = Producto::where('empresa_id', $empresaId)
            ->whereNotNull('categoria_id')
            ->get()
            ->all();
        if (count($productosVenta) < 3) {
            $productosVenta = $productosNuevos;
        }

        // ------------------------------------------------------------------
        // 10 CLIENTES nuevos
        // ------------------------------------------------------------------
        $nombresCliente = [
            'Constructora Los Andes SAC', 'Edificaciones Perú EIRL', 'Roberto Salazar Núñez',
            'Ana Torres Vega', 'Ferretería Aliada SRL', 'Grupo Inmobiliario Sur SAC',
            'Luis Campos Rivera', 'Mantenimiento Integral SAC', 'Carla Mendoza Ríos',
            'Servicios Eléctricos JR EIRL',
        ];
        $clientesNuevos = [];
        foreach ($nombresCliente as $i => $n) {
            $esEmpresa = str_contains($n, 'SAC') || str_contains($n, 'EIRL') || str_contains($n, 'SRL');
            $clientesNuevos[] = Cliente::create([
                'empresa_id'       => $empresaId,
                'tipo_documento'   => $esEmpresa ? 'RUC' : 'DNI',
                'numero_documento' => $esEmpresa ? '206' . $tag . rand(10000, 99999) : '4' . $tag . rand(1000, 9999),
                'nombre'           => $n,
                'telefono'         => '9' . rand(10000000, 99999999),
                'activo'           => true,
            ]);
        }
        $clientesTodos = Cliente::where('empresa_id', $empresaId)->get()->all();

        // ------------------------------------------------------------------
        // 10 PROVEEDORES nuevos
        // ------------------------------------------------------------------
        $nombresProv = [
            'Aceros y Perfiles del Pacífico SAC', 'Distribuidora Eléctrica Total EIRL',
            'Importadora de Herramientas Andina SAC', 'Pinturas y Solventes Lima SRL',
            'Comercial Gasfitería Express SAC', 'Ferremayorista del Centro EIRL',
            'Insumos de Construcción Perú SAC', 'Seguridad Industrial Andes SRL',
            'Tornillos y Fijaciones del Sur SAC', 'Suministros Eléctricos JB EIRL',
        ];
        $proveedoresNuevos = [];
        foreach ($nombresProv as $i => $rs) {
            $proveedoresNuevos[] = Proveedor::create([
                'empresa_id'   => $empresaId,
                'razon_social' => $rs,
                'ruc'          => '207' . $tag . rand(10000, 99999),
                'telefono'     => '01-' . rand(2000000, 7999999),
                'activo'       => true,
            ]);
        }
        $proveedoresTodos = Proveedor::where('empresa_id', $empresaId)->get()->all();

        // ==================================================================
        // VENTAS
        // ==================================================================
        $corr = 1;
        $numeroVenta = fn () => 'V-' . $tag . '-' . str_pad($corr++, 4, '0', STR_PAD_LEFT);

        // Helper para crear una venta completada con N ítems en una fecha/hora dada
        $crearVenta = function (Carbon $fecha) use ($empresaId, $userId, $clientesTodos, $productosVenta, $numeroVenta) {
            $venta = Venta::create([
                'empresa_id'      => $empresaId,
                'cliente_id'      => $clientesTodos[array_rand($clientesTodos)]->id,
                'user_id'         => $userId,
                'numero'          => $numeroVenta(),
                'tipo_comprobante'=> ['ticket', 'boleta', 'factura'][rand(0, 2)],
                'fecha'           => $fecha,
                'metodo_pago'     => ['efectivo', 'tarjeta', 'yape', 'transferencia', 'plin'][rand(0, 4)],
                'estado'          => 'completada',
            ]);
            $total = 0;
            $items = rand(1, 4);
            for ($i = 0; $i < $items; $i++) {
                $p = $productosVenta[array_rand($productosVenta)];
                $cant = rand(1, 6);
                $sub = round($cant * (float) $p->precio_venta, 2);
                VentaDetalle::create([
                    'venta_id'   => $venta->id,
                    'producto_id'=> $p->id,
                    'cantidad'   => $cant,
                    'precio'     => $p->precio_venta,
                    'subtotal'   => $sub,
                ]);
                $total += $sub;
            }
            $subt = round($total / 1.18, 2);
            $venta->update([
                'subtotal' => $subt,
                'impuesto' => round($total - $subt, 2),
                'total'    => $total,
                'saldo'    => 0,
            ]);
            return $venta;
        };

        // (A) Ventas del MES ACTUAL -> alimenta "Ventas de hoy/mes" y "Ventas por categoría"
        $inicioMes = Carbon::now()->startOfMonth();
        $diasEnCurso = Carbon::now()->day; // días transcurridos del mes actual
        for ($k = 0; $k < 22; $k++) {
            $dia = rand(0, max(0, $diasEnCurso - 1));
            $fecha = $inicioMes->copy()->addDays($dia)->setTime(rand(8, 20), rand(0, 59), rand(0, 59));
            if ($fecha->greaterThan(Carbon::now())) {
                $fecha = Carbon::now()->subMinutes(rand(5, 120));
            }
            $crearVenta($fecha);
        }

        // (B) Ventas de los últimos 30 días con HORA real -> "Ventas por hora del día"
        // Se concentran en horas pico comerciales (mañana y tarde).
        for ($d = 30; $d >= 1; $d--) {
            $numVentas = rand(1, 4);
            for ($v = 0; $v < $numVentas; $v++) {
                // Horas con más peso comercial: 9-12 y 16-19
                $bloque = rand(0, 1) === 0 ? rand(9, 12) : rand(16, 19);
                // A veces horas de borde
                $hora = rand(0, 4) === 0 ? rand(7, 21) : $bloque;
                $fecha = Carbon::today()->subDays($d)->setTime($hora, rand(0, 59), rand(0, 59));
                $crearVenta($fecha);
            }
        }

        // (C) Refuerzo de los últimos 6 meses -> "Ingresos vs Egresos"
        for ($m = 5; $m >= 1; $m--) {
            for ($x = 0; $x < rand(6, 12); $x++) {
                $base = Carbon::now()->subMonths($m)->startOfMonth();
                $fecha = $base->copy()->addDays(rand(0, 26))->setTime(rand(8, 20), rand(0, 59));
                $crearVenta($fecha);
            }
        }

        // ==================================================================
        // COMPRAS (10) -> "Egresos" y Cuentas por Pagar
        // ==================================================================
        $cc = 1;
        for ($i = 0; $i < 10; $i++) {
            $aCredito = $i < 4; // 4 a crédito -> cuentas por pagar
            $fecha = Carbon::now()->subDays(rand(0, 150));
            $compra = Compra::create([
                'empresa_id'   => $empresaId,
                'proveedor_id' => $proveedoresTodos[array_rand($proveedoresTodos)]->id,
                'user_id'      => $userId,
                'numero'       => 'C-' . $tag . '-' . str_pad($cc++, 4, '0', STR_PAD_LEFT),
                'fecha'        => $fecha->toDateString(),
                'estado'       => 'recibida',
                'a_credito'    => $aCredito,
            ]);
            $total = 0;
            for ($j = 0; $j < rand(2, 4); $j++) {
                $p = $productosNuevos[array_rand($productosNuevos)];
                $cant = rand(8, 30);
                $sub = round($cant * (float) $p->precio_compra, 2);
                CompraDetalle::create([
                    'compra_id'  => $compra->id,
                    'producto_id'=> $p->id,
                    'cantidad'   => $cant,
                    'precio'     => $p->precio_compra,
                    'subtotal'   => $sub,
                ]);
                $total += $sub;
            }
            $subt = round($total / 1.18, 2);
            $pct = $aCredito ? [0, 0.3, 0.5][rand(0, 2)] : 1.0;
            $pagado = round($total * $pct, 2);
            $compra->update([
                'subtotal' => $subt,
                'impuesto' => round($total - $subt, 2),
                'total'    => $total,
                'saldo'    => $aCredito ? round($total - $pagado, 2) : 0,
                'fecha_vencimiento' => $aCredito ? $fecha->copy()->addDays(30)->toDateString() : null,
            ]);
            if ($aCredito && $pagado > 0) {
                Pago::create([
                    'empresa_id' => $empresaId,
                    'tipo'       => 'pago',
                    'compra_id'  => $compra->id,
                    'user_id'    => $userId,
                    'monto'      => $pagado,
                    'metodo_pago'=> ['transferencia', 'cheque', 'deposito'][rand(0, 2)],
                    'fecha'      => $fecha->copy()->addDays(rand(2, 20)),
                    'referencia' => 'PAG-' . rand(10000, 99999),
                ]);
            }
        }

        // ==================================================================
        // VENTAS A CRÉDITO (para Cuentas por Cobrar) + abonos
        // ==================================================================
        $clientesCredito = array_values(array_filter($clientesTodos, fn ($c) => ! empty($c->numero_documento)));
        if (empty($clientesCredito)) {
            $clientesCredito = $clientesTodos;
        }
        for ($i = 0; $i < 6; $i++) {
            $emit = [40, 25, 60, 12, 8, 3][$i];
            $plazo = 30;
            $pct = [0.5, 0, 0.3, 0, 0.25, 0][$i];
            $fecha = Carbon::now()->subDays($emit)->setTime(rand(9, 18), rand(0, 59));
            $venta = Venta::create([
                'empresa_id'      => $empresaId,
                'cliente_id'      => $clientesCredito[$i % count($clientesCredito)]->id,
                'user_id'         => $userId,
                'numero'          => $numeroVenta(),
                'tipo_comprobante'=> 'factura',
                'fecha'           => $fecha,
                'metodo_pago'     => 'credito',
                'estado'          => 'completada',
                'fecha_vencimiento'=> $fecha->copy()->addDays($plazo)->toDateString(),
            ]);
            $total = 0;
            for ($j = 0; $j < rand(2, 4); $j++) {
                $p = $productosVenta[array_rand($productosVenta)];
                $cant = rand(2, 8);
                $sub = round($cant * (float) $p->precio_venta, 2);
                VentaDetalle::create([
                    'venta_id'   => $venta->id,
                    'producto_id'=> $p->id,
                    'cantidad'   => $cant,
                    'precio'     => $p->precio_venta,
                    'subtotal'   => $sub,
                ]);
                $total += $sub;
            }
            $subt = round($total / 1.18, 2);
            $pagado = round($total * $pct, 2);
            $venta->update([
                'subtotal' => $subt,
                'impuesto' => round($total - $subt, 2),
                'total'    => $total,
                'saldo'    => round($total - $pagado, 2),
            ]);
            if ($pagado > 0) {
                Pago::create([
                    'empresa_id' => $empresaId,
                    'tipo'       => 'cobro',
                    'venta_id'   => $venta->id,
                    'user_id'    => $userId,
                    'monto'      => $pagado,
                    'metodo_pago'=> ['efectivo', 'transferencia', 'yape'][rand(0, 2)],
                    'fecha'      => $fecha->copy()->addDays(rand(2, 25)),
                    'referencia' => 'OP-' . rand(10000, 99999),
                ]);
            }
        }

        // ==================================================================
        // COTIZACIONES (10)
        // ==================================================================
        $cq = 1;
        foreach (range(1, 10) as $i) {
            $fecha = Carbon::now()->subDays(rand(0, 25));
            $cot = Cotizacion::create([
                'empresa_id'  => $empresaId,
                'cliente_id'  => $clientesTodos[array_rand($clientesTodos)]->id,
                'user_id'     => $userId,
                'numero'      => 'COT-' . $tag . '-' . str_pad($cq++, 4, '0', STR_PAD_LEFT),
                'fecha'       => $fecha->toDateString(),
                'vencimiento' => $fecha->copy()->addDays(15)->toDateString(),
                'estado'      => ['vigente', 'vigente', 'aceptada', 'vencida'][rand(0, 3)],
            ]);
            $total = 0;
            foreach (range(1, rand(2, 4)) as $j) {
                $p = $productosVenta[array_rand($productosVenta)];
                $cant = rand(1, 6);
                $sub = round($cant * (float) $p->precio_venta, 2);
                DB::table('cotizacion_detalles')->insert([
                    'cotizacion_id' => $cot->id,
                    'producto_id'   => $p->id,
                    'cantidad'      => $cant,
                    'precio'        => $p->precio_venta,
                    'subtotal'      => $sub,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $total += $sub;
            }
            $cot->update(['total' => $total]);
        }

        // ==================================================================
        // MOVIMIENTOS DE INVENTARIO (10)
        // ==================================================================
        foreach (range(1, 10) as $i) {
            $p = $productosNuevos[array_rand($productosNuevos)];
            MovimientoInventario::create([
                'empresa_id'  => $empresaId,
                'producto_id' => $p->id,
                'user_id'     => $userId,
                'tipo'        => ['entrada', 'salida', 'ajuste'][rand(0, 2)],
                'cantidad'    => rand(1, 25),
                'motivo'      => ['Compra a proveedor', 'Venta mostrador', 'Ajuste por inventario', 'Devolución'][rand(0, 3)],
                'fecha'       => Carbon::now()->subDays(rand(0, 30))->setTime(rand(8, 19), rand(0, 59)),
            ]);
        }

        // ==================================================================
        // CAJA (10 aperturas/cierres en distintos días)
        // ==================================================================
        foreach (range(1, 10) as $i) {
            $apertura = Carbon::now()->subDays($i)->setTime(8, 0);
            $esUltima = $i === 1; // dejar la más reciente abierta
            $montoApertura = 200;
            $ingresos = rand(300, 2500);
            Caja::create([
                'empresa_id'     => $empresaId,
                'user_id'        => $userId,
                'fecha_apertura' => $apertura,
                'fecha_cierre'   => $esUltima ? null : $apertura->copy()->setTime(20, rand(0, 59)),
                'monto_apertura' => $montoApertura,
                'monto_cierre'   => $esUltima ? null : $montoApertura + $ingresos,
                'estado'         => $esUltima ? 'abierta' : 'cerrada',
            ]);
        }

        $this->command->info("RecargaDashboardSeeder OK (lote $tag): productos, clientes, proveedores, ventas del mes y por hora, compras, cotizaciones, movimientos, caja y pagos agregados a la empresa #{$empresaId}.");
    }
}
