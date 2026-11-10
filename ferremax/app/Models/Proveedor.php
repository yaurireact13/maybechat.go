<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'proveedores';
    protected $guarded = [];

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }
}
