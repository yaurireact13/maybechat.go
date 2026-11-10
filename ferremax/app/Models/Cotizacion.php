<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'cotizaciones';
    protected $guarded = [];
    protected $casts = ['fecha' => 'date', 'vencimiento' => 'date'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detalles()
    {
        return $this->hasMany(CotizacionDetalle::class);
    }
}
