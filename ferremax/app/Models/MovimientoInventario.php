<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'movimientos_inventario';
    protected $guarded = [];
    protected $casts = ['fecha' => 'datetime'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
