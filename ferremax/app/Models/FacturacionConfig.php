<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuracion del emisor de facturacion electronica de una empresa.
 * Las credenciales (clave SOL y clave del certificado) se guardan CIFRADAS
 * en reposo mediante el cast "encrypted" de Laravel.
 */
class FacturacionConfig extends Model
{
    protected $table = 'facturacion_configs';
    protected $guarded = [];

    protected $casts = [
        'clave_sol'         => 'encrypted',
        'clave_certificado' => 'encrypted',
        'certificado_vence' => 'date',
    ];

    // Nunca exponer las credenciales al serializar.
    protected $hidden = ['clave_sol', 'clave_certificado'];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function tieneCertificado(): bool
    {
        return filled($this->certificado_path);
    }

    public function certificadoVencido(): bool
    {
        return $this->certificado_vence && $this->certificado_vence->isPast();
    }
}
