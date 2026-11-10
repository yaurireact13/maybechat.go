<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'pagos';
    protected $guarded = [];
    protected $casts = [
        'fecha' => 'datetime',
        'monto' => 'decimal:2',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
