<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'clientes';
    protected $guarded = [];

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }
}
