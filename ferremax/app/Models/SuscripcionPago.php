<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuscripcionPago extends Model
{
    protected $table = 'suscripcion_pagos';
    protected $guarded = [];
    protected $casts = [
        'monto' => 'decimal:2',
        'periodo_desde' => 'date',
        'periodo_hasta' => 'date',
        'fecha_pago' => 'date',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
