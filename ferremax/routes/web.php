<?php

use App\Http\Controllers\AsistenteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\ChatIaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ComprobanteElectronicoController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacturacionConfigController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SuperAdmin\EmpresaController as SaEmpresaController;
use App\Http\Controllers\SuperAdmin\FacturacionController as SaFacturacionController;
use App\Http\Controllers\SuperAdmin\PanelController as SaPanelController;
use App\Http\Controllers\SuperAdmin\UsuarioController as SaUsuarioController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

// Autenticación y registro
Route::get('/registro', [RegistroController::class, 'show'])->name('registro');
Route::post('/registro', [RegistroController::class, 'store'])->name('registro.store');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Salir de impersonación (lo usa la sesión del admin impersonado)
Route::post('/impersonacion/salir', [SaEmpresaController::class, 'salirImpersonacion'])
    ->middleware('auth')->name('impersonacion.salir');

// ===== Panel de plataforma (Super Admin) =====
Route::middleware(['auth', 'superadmin'])->prefix('panel')->name('superadmin.')->group(function () {
    Route::get('/', [SaPanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/planes', [SaPanelController::class, 'planes'])->name('planes');

    // Empresas / tenants
    Route::get('/empresas', [SaEmpresaController::class, 'index'])->name('empresas.index');
    Route::get('/empresas/nueva', [SaEmpresaController::class, 'create'])->name('empresas.create');
    Route::post('/empresas', [SaEmpresaController::class, 'store'])->name('empresas.store');
    Route::get('/empresas/{empresa}', [SaEmpresaController::class, 'show'])->name('empresas.show');
    Route::get('/empresas/{empresa}/editar', [SaEmpresaController::class, 'edit'])->name('empresas.edit');
    Route::put('/empresas/{empresa}', [SaEmpresaController::class, 'update'])->name('empresas.update');
    Route::patch('/empresas/{empresa}/suspender', [SaEmpresaController::class, 'suspender'])->name('empresas.suspender');
    Route::post('/empresas/{empresa}/impersonar', [SaEmpresaController::class, 'impersonar'])->name('empresas.impersonar');

    // Usuarios globales
    Route::get('/usuarios', [SaUsuarioController::class, 'index'])->name('usuarios.index');
    Route::patch('/usuarios/{usuario}/toggle', [SaUsuarioController::class, 'toggle'])->name('usuarios.toggle');
    Route::patch('/usuarios/{usuario}/reset', [SaUsuarioController::class, 'resetPassword'])->name('usuarios.reset');

    // Facturación de suscripciones
    Route::get('/facturacion', [SaFacturacionController::class, 'index'])->name('facturacion.index');
    Route::post('/empresas/{empresa}/facturacion', [SaFacturacionController::class, 'store'])->name('facturacion.store');
});

// Área protegida de la ferretería (tenant)
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Punto de Venta (POS)
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
    Route::get('/pos/recibo/{venta}', [PosController::class, 'recibo'])->name('pos.recibo');

    // Ventas (historial)
    Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
    Route::get('/ventas/{venta}', [VentaController::class, 'show'])->name('ventas.show');
    Route::patch('/ventas/{venta}/anular', [VentaController::class, 'anular'])->name('ventas.anular');

    // Comprobante electronico de una venta
    Route::post('/ventas/{venta}/comprobante/reintentar', [ComprobanteElectronicoController::class, 'reintentar'])->name('comprobantes.reintentar');
    Route::post('/ventas/{venta}/comprobante/consultar', [ComprobanteElectronicoController::class, 'consultar'])->name('comprobantes.consultar');

    // Cotizaciones
    Route::get('/cotizaciones', [CotizacionController::class, 'index'])->name('cotizaciones.index');
    Route::get('/cotizaciones/nueva', [CotizacionController::class, 'create'])->name('cotizaciones.create');
    Route::post('/cotizaciones', [CotizacionController::class, 'store'])->name('cotizaciones.store');
    Route::get('/cotizaciones/{cotizacion}', [CotizacionController::class, 'show'])->name('cotizaciones.show');
    Route::patch('/cotizaciones/{cotizacion}/convertir', [CotizacionController::class, 'convertir'])->name('cotizaciones.convertir');
    Route::patch('/cotizaciones/{cotizacion}/anular', [CotizacionController::class, 'anular'])->name('cotizaciones.anular');

    // Caja
    Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
    Route::post('/caja/abrir', [CajaController::class, 'abrir'])->name('caja.abrir');
    Route::patch('/caja/{caja}/cerrar', [CajaController::class, 'cerrar'])->name('caja.cerrar');

    // Cuentas por Cobrar / Pagar
    Route::prefix('cuentas')->name('cuentas.')->group(function () {
        Route::get('/por-cobrar', [CuentaController::class, 'porCobrar'])->name('cobrar');
        Route::get('/por-cobrar/{venta}', [CuentaController::class, 'cobrarDetalle'])->name('cobrar.detalle');
        Route::post('/por-cobrar/{venta}/pago', [CuentaController::class, 'registrarCobro'])->name('cobrar.pago');
        Route::get('/por-pagar', [CuentaController::class, 'porPagar'])->name('pagar');
        Route::get('/por-pagar/{compra}', [CuentaController::class, 'pagarDetalle'])->name('pagar.detalle');
        Route::post('/por-pagar/{compra}/pago', [CuentaController::class, 'registrarPago'])->name('pagar.pago');
        Route::get('/cliente/{cliente}/estado', [CuentaController::class, 'estadoCliente'])->name('cliente');
    });

    // Compras
    Route::get('/compras', [CompraController::class, 'index'])->name('compras.index');
    Route::get('/compras/nueva', [CompraController::class, 'create'])->name('compras.create');
    Route::post('/compras', [CompraController::class, 'store'])->name('compras.store');
    Route::get('/compras/{compra}', [CompraController::class, 'show'])->name('compras.show');
    Route::patch('/compras/{compra}/anular', [CompraController::class, 'anular'])->name('compras.anular');

    // Productos
    Route::resource('productos', ProductoController::class)
        ->except(['show'])
        ->parameters(['productos' => 'producto']);
    Route::patch('/productos/{producto}/activar', [ProductoController::class, 'activar'])->name('productos.activar');

    // Inventario / Kardex
    Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
    Route::post('/inventario/ajustar', [InventarioController::class, 'ajustar'])->name('inventario.ajustar');

    // Catálogos: categorías, marcas y unidades
    Route::get('/catalogos', [CatalogoController::class, 'index'])->name('catalogos.index');
    Route::post('/catalogos/categorias', [CatalogoController::class, 'guardarCategoria'])->name('catalogos.categorias.store');
    Route::put('/catalogos/categorias/{categoria}', [CatalogoController::class, 'actualizarCategoria'])->name('catalogos.categorias.update');
    Route::delete('/catalogos/categorias/{categoria}', [CatalogoController::class, 'eliminarCategoria'])->name('catalogos.categorias.destroy');
    Route::post('/catalogos/marcas', [CatalogoController::class, 'guardarMarca'])->name('catalogos.marcas.store');
    Route::put('/catalogos/marcas/{marca}', [CatalogoController::class, 'actualizarMarca'])->name('catalogos.marcas.update');
    Route::delete('/catalogos/marcas/{marca}', [CatalogoController::class, 'eliminarMarca'])->name('catalogos.marcas.destroy');
    Route::post('/catalogos/unidades', [CatalogoController::class, 'guardarUnidad'])->name('catalogos.unidades.store');
    Route::put('/catalogos/unidades/{unidad}', [CatalogoController::class, 'actualizarUnidad'])->name('catalogos.unidades.update');
    Route::delete('/catalogos/unidades/{unidad}', [CatalogoController::class, 'eliminarUnidad'])->name('catalogos.unidades.destroy');

    // Clientes
    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
    Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
    Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
    Route::patch('/clientes/{cliente}/activar', [ClienteController::class, 'activar'])->name('clientes.activar');

    // Proveedores
    Route::get('/proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
    Route::post('/proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
    Route::put('/proveedores/{proveedor}', [ProveedorController::class, 'update'])->name('proveedores.update');
    Route::delete('/proveedores/{proveedor}', [ProveedorController::class, 'destroy'])->name('proveedores.destroy');
    Route::patch('/proveedores/{proveedor}/activar', [ProveedorController::class, 'activar'])->name('proveedores.activar');

    // Reportes
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/exportar', [ReporteController::class, 'exportar'])->name('reportes.exportar');

    // Usuarios y Roles (solo admin)
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::patch('/usuarios/{usuario}/alternar', [UsuarioController::class, 'alternar'])->name('usuarios.alternar');

    // Configuración (solo admin)
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::put('/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');

    // Configuración de Facturación Electrónica (solo admin)
    Route::get('/configuracion/facturacion', [FacturacionConfigController::class, 'index'])->name('facturacion_config.index');
    Route::put('/configuracion/facturacion', [FacturacionConfigController::class, 'update'])->name('facturacion_config.update');

    // ===== Asistente IA y Chat IA (solo administrador de la ferretería) =====
    Route::middleware('admin')->group(function () {
        Route::get('/asistente', [AsistenteController::class, 'index'])->name('asistente.index');
        Route::prefix('asistente')->name('asistente.')->group(function () {
            Route::get('/estado', [AsistenteController::class, 'estado'])->name('estado');
            Route::post('/config', [AsistenteController::class, 'guardarConfig'])->name('config');
            Route::post('/probar', [AsistenteController::class, 'probar'])->name('probar');
            Route::post('/preguntar', [AsistenteController::class, 'preguntar'])->name('preguntar');
            Route::post('/ejecutar', [AsistenteController::class, 'ejecutar'])->name('ejecutar');
            Route::post('/resumir', [AsistenteController::class, 'resumir'])->name('resumir');
            Route::post('/favorito', [AsistenteController::class, 'favorito'])->name('favorito');
            Route::post('/borrar', [AsistenteController::class, 'borrar'])->name('borrar');
            Route::get('/csv/{id}', [AsistenteController::class, 'csv'])->whereNumber('id')->name('csv');
            Route::get('/esquema', [AsistenteController::class, 'esquema'])->name('esquema');
        });

        Route::get('/chat-ia', [ChatIaController::class, 'index'])->name('chat.index');
        Route::prefix('chat-ia')->name('chat.')->group(function () {
            Route::get('/estado', [ChatIaController::class, 'estado'])->name('estado');
            Route::post('/enviar', [ChatIaController::class, 'enviar'])->name('enviar');
            Route::post('/limpiar', [ChatIaController::class, 'limpiar'])->name('limpiar');
        });
    });
});
