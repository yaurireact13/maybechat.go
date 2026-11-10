<?php

/**
 * Configuracion del cliente de facturacion electronica del ERP.
 *
 * El ERP no conoce SUNAT/DIAN: solo habla con el microservicio de facturacion.
 * En modo "simulado" se genera un comprobante aceptado localmente (sin llamar al
 * microservicio) para poder probar el flujo completo aunque el servicio aun no
 * este desplegado. En modo "real" se consume la API REST del microservicio.
 */
return [

    // Activa/desactiva la emision de comprobantes electronicos.
    'enabled' => (bool) env('FACT_ENABLED', true),

    // simulado | real
    'modo' => env('FACT_MODO', 'simulado'),

    // Conexion al microservicio (modo real).
    'base_url' => env('FACT_BASE_URL', 'http://localhost:8090'),
    'token'    => env('FACT_TOKEN', ''),
    'timeout'  => (int) env('FACT_TIMEOUT', 30),

    // Pais por defecto del emisor.
    'pais' => env('FACT_PAIS', 'PE'),

    // Series por tipo de comprobante (SUNAT: F=factura, B=boleta).
    'series' => [
        'factura' => env('FACT_SERIE_FACTURA', 'F001'),
        'boleta'  => env('FACT_SERIE_BOLETA', 'B001'),
    ],

    // Reintentos del job de emision.
    'reintentos' => (int) env('FACT_REINTENTOS', 5),
];
