<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Estado del comprobante electronico de una venta (reportado por el microservicio).
 * No usa el scope multi-empresa porque se accede siempre a traves de la venta
 * (ya filtrada) o desde un job; el empresa_id se asigna de forma explicita.
 */
class ComprobanteElectronico extends Model
{
    protected $table = 'comprobantes_electronicos';
    protected $guarded = [];
    protected $casts = ['respuesta' => 'array'];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function estadoBadge(): array
    {
        return match ($this->estado) {
            'aceptado'  => ['Aceptado', 'bg-emerald-100 text-emerald-700'],
            'observado' => ['Observado', 'bg-amber-100 text-amber-700'],
            'rechazado' => ['Rechazado', 'bg-red-100 text-red-700'],
            'error'     => ['Error de envio', 'bg-red-100 text-red-700'],
            default     => ['Pendiente', 'bg-slate-100 text-slate-600'],
        };
    }

    public function puedeReintentar(): bool
    {
        return in_array($this->estado, ['pendiente', 'error', 'rechazado'], true);
    }
}
