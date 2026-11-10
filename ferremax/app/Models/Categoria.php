<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'categorias';
    protected $guarded = [];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
