<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'cajas';
    protected $guarded = [];
    protected $casts = ['fecha_apertura' => 'datetime', 'fecha_cierre' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
