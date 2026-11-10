<?php

declare(strict_types=1);

namespace App\Domain\Facturacion\Enum;

/**
 * Ciclo de vida del comprobante dentro de la plataforma (agnostico del pais).
 * El detalle local (CDR de SUNAT, CUFE de DIAN, CAE de ARCA, timbrado del PAC en Mexico)
 * se guarda aparte en los metadatos del evento.
 */
enum EstadoComprobante: string
{
    case PENDIENTE   = 'pendiente';   // creado, aun no enviado
    case ENVIADO     = 'enviado';     // enviado al organismo, esperando respuesta (async)
    case ACEPTADO    = 'aceptado';    // aprobado por el organismo
    case OBSERVADO   = 'observado';   // aceptado con observaciones
    case RECHAZADO   = 'rechazado';   // rechazado por el organismo
    case ANULADO     = 'anulado';     // baja / comunicacion de baja aceptada
    case ERROR       = 'error';       // fallo tecnico (se reintenta)

    public function esFinalExitoso(): bool
    {
        return $this === self::ACEPTADO || $this === self::OBSERVADO;
    }

    public function permiteReintento(): bool
    {
        return $this === self::ERROR || $this === self::PENDIENTE;
    }
}
