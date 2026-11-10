<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'ventas';
    protected $guarded = [];
    protected $casts = [
        'fecha' => 'datetime',
        'fecha_vencimiento' => 'date',
    ];

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
        return $this->hasMany(VentaDetalle::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class)->where('tipo', 'cobro');
    }

    public function comprobanteElectronico()
    {
        return $this->hasOne(ComprobanteElectronico::class);
    }

    /* ===== Cuentas por cobrar ===== */

    public function esCredito(): bool
    {
        return $this->metodo_pago === 'credito';
    }

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
