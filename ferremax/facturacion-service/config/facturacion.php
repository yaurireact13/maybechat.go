<?php

/**
 * Mapa PAIS -> ADAPTADOR (patron Strategy/Adapter).
 *
 * Para incorporar un nuevo pais NO se modifica ningun caso de uso ni el dominio:
 * basta con (1) crear una clase que implemente App\Domain\Facturacion\Port\ProveedorFacturacion
 * y (2) registrarla aqui. Esto cumple el Principio Abierto/Cerrado (OCP).
 */

use App\Infrastructure\Providers\Peru\SunatProveedor;
// use App\Infrastructure\Providers\Colombia\DianProveedor;   // Fase 2
// use App\Infrastructure\Providers\Chile\SiiProveedor;       // Fase 3
// use App\Infrastructure\Providers\Argentina\ArcaProveedor;  // Fase 3
// use App\Infrastructure\Providers\Mexico\SatProveedor;      // Fase 4

return [

    // Proveedor por defecto si el emisor no especifica pais.
    'pais_por_defecto' => env('FACT_PAIS_DEFECTO', 'PE'),

    /*
    |--------------------------------------------------------------------------
    | Registro de proveedores por pais (codigo ISO 3166-1 alpha-2)
    |--------------------------------------------------------------------------
    | clave  = codigo de pais
    | valor  = clase del adaptador (implementa ProveedorFacturacion)
    */
    'proveedores' => [
        'PE' => SunatProveedor::class,   // Peru  - SUNAT   (UBL 2.1 + CDR)
        // 'CO' => DianProveedor::class, // Colombia - DIAN (UBL 2.1 + CUFE)
        // 'CL' => SiiProveedor::class,  // Chile - SII     (DTE + TED/CAF)
        // 'AR' => ArcaProveedor::class, // Argentina - ARCA(WSFE/WSAA + CAE)
        // 'MX' => SatProveedor::class,  // Mexico - SAT    (CFDI 4.0 + PAC)
    ],

    /*
    |--------------------------------------------------------------------------
    | Parametros especificos por pais (endpoints, ambiente, etc.)
    |--------------------------------------------------------------------------
    */
    'pais' => [
        'PE' => [
            'ambiente'   => env('SUNAT_AMBIENTE', 'beta'), // beta | produccion
            'endpoint'   => env('SUNAT_ENDPOINT', 'https://e-beta.sunat.gob.pe/ol-ti-itcpe/billService'),
            'ubl_version' => '2.1',
        ],
        'CO' => ['ambiente' => 'habilitacion', 'ubl_version' => '2.1'],
        'CL' => ['ambiente' => 'certificacion'],
        'AR' => ['ambiente' => 'homologacion'],
        'MX' => ['ambiente' => 'pruebas', 'cfdi_version' => '4.0'],
    ],

    // Reintentos y resiliencia
    'reintentos' => [
        'max'            => env('FACT_REINTENTOS_MAX', 5),
        'backoff_base_s' => 2,   // 2, 4, 8, 16, 32 ...
        'timeout_s'      => 30,
    ],

    // Almacenamiento de documentos (XML firmado, CDR, PDF)
    'almacen' => [
        'disk' => env('FACT_DISK', 's3'),
        'ruta' => '{pais}/{ruc_emisor}/{anio}/{mes}/{tipo}-{serie}-{numero}',
    ],
];
