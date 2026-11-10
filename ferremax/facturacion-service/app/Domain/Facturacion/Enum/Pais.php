<?php

declare(strict_types=1);

namespace App\Domain\Facturacion\Enum;

/**
 * Paises soportados por la plataforma (ISO 3166-1 alpha-2).
 * Agregar un pais aqui NO obliga a cambiar la logica: el dominio solo
 * conoce el codigo; la implementacion vive en un adaptador de Infraestructura.
 */
enum Pais: string
{
    case PE = 'PE'; // Peru      - SUNAT
    case CO = 'CO'; // Colombia  - DIAN
    case CL = 'CL'; // Chile     - SII
    case AR = 'AR'; // Argentina - ARCA (ex AFIP)
    case MX = 'MX'; // Mexico    - SAT

    public function organismo(): string
    {
        return match ($this) {
            self::PE => 'SUNAT',
            self::CO => 'DIAN',
            self::CL => 'SII',
            self::AR => 'ARCA',
            self::MX => 'SAT',
        };
    }
}
