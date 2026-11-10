<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'marcas';
    protected $guarded = [];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
