<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

class Unidad extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'unidades';
    protected $guarded = [];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
