<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/** Modelo Eloquent (detalle de infraestructura, NO es la entidad de dominio). */
class ComprobanteModel extends Model
{
    protected $table = 'comprobantes';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
    protected $casts = [
        'payload'       => 'array',   // snapshot del agregado
        'fecha_emision' => 'datetime',
    ];
}
