<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'compras';
    protected $guarded = [];
    protected $casts = [
        'fecha' => 'date',
        'fecha_vencimiento' => 'date',
        'a_credito' => 'boolean',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detalles()
    {
        return $this->hasMany(CompraDetalle::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class)->where('tipo', 'pago');
    }

    /* ===== Cuentas por pagar ===== */

    public function pagado(): float
    {
        return round((float) $this->total - (float) $this->saldo, 2);
    }

    public function estadoPago(): string
    {
        if ($this->estado === 'anulada') {
            return 'anulada';
        }
        if ((float) $this->saldo <= 0) {
            return 'pagado';
        }
        if ((float) $this->saldo < (float) $this->total) {
            return 'parcial';
        }

        return 'pendiente';
    }

    public function vencida(): bool
    {
        return (float) $this->saldo > 0
            && $this->fecha_vencimiento
            && $this->fecha_vencimiento->isPast();
    }
}
