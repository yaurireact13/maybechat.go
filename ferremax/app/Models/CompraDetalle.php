<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraDetalle extends Model
{
    protected $table = 'compra_detalles';
    protected $guarded = [];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
