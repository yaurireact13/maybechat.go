<?php

/*
|--------------------------------------------------------------------------
| Permisos por rol de la ferretería
|--------------------------------------------------------------------------
| Cada permiso indica qué roles lo tienen. El rol "admin" tiene TODOS los
| permisos siempre (no hace falta listarlo). Se registran como Gates en
| AppServiceProvider, por lo que se usan con:
|   - rutas:  ->middleware('can:caja')
|   - vistas: @can('caja') ... @endcan
|   - código: auth()->user()->can('caja')
|
| Para cambiar lo que puede hacer un rol, edita solo este archivo.
*/

return [

    'roles' => [
        'admin' => [
            'nombre' => 'Administrador',
            'descripcion' => 'Acceso total: usuarios, configuración, reportes, anulaciones e IA.',
        ],
        'vendedor' => [
            'nombre' => 'Vendedor',
            'descripcion' => 'Vende en el POS, hace cotizaciones y atiende clientes.',
        ],
        'cajero' => [
            'nombre' => 'Cajero',
            'descripcion' => 'Cobra en el POS, abre y cierra caja y registra cobros de créditos.',
        ],
        'almacenero' => [
            'nombre' => 'Almacenero',
            'descripcion' => 'Gestiona productos, inventario, compras y proveedores.',
        ],
    ],

    'permisos' => [
        // permiso            => [etiqueta, roles con acceso (además de admin)]
        'vender'            => ['Punto de venta (POS)',            ['vendedor', 'cajero']],
        'ver-ventas'        => ['Historial de ventas y recibos',   ['vendedor', 'cajero']],
        'anular-ventas'     => ['Anular ventas',                   []],
        'cotizaciones'      => ['Cotizaciones',                    ['vendedor']],
        'caja'              => ['Caja (apertura, cierre, arqueo)', ['cajero']],
        'cuentas-cobrar'    => ['Cuentas por cobrar y cobros',     ['cajero']],
        'cuentas-pagar'     => ['Cuentas por pagar y pagos',       []],
        'clientes'          => ['Clientes',                        ['vendedor', 'cajero']],
        'productos'         => ['Productos',                       ['almacenero']],
        'catalogos'         => ['Categorías, marcas y unidades',   ['almacenero']],
        'inventario'        => ['Inventario / Kardex y ajustes',   ['almacenero']],
        'compras'           => ['Compras',                         ['almacenero']],
        'anular-compras'    => ['Anular compras',                  []],
        'proveedores'       => ['Proveedores',                     ['almacenero']],
        'reportes'          => ['Reportes y utilidad',             []],
        'finanzas-dashboard'=> ['Indicadores financieros del Dashboard', []],
        'usuarios'          => ['Usuarios y roles',                []],
        'configuracion'     => ['Configuración y facturación',     []],
        'ia'                => ['Asistente IA y Chat IA',          []],
    ],
];
