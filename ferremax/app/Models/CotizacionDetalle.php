<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotizacionDetalle extends Model
{
    protected $table = 'cotizacion_detalles';
    protected $guarded = [];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
