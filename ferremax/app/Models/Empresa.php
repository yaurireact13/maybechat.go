<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresas';
    protected $guarded = [];
    protected $casts = [
        'activo' => 'boolean',
        'plan_vence' => 'date',
        'impuesto' => 'decimal:2',
        'factura_activa' => 'boolean',
    ];

    /** Precio mensual de suscripción por plan (para el cálculo de MRR). */
    public const PRECIOS_PLAN = [
        'basico' => 49.00,
        'pro' => 99.00,
        'premium' => 199.00,
    ];

    /** Límites de recursos por plan (null = ilimitado). */
    public const LIMITES_PLAN = [
        'basico' => ['usuarios' => 2, 'productos' => 150],
        'pro' => ['usuarios' => 8, 'productos' => null],
        'premium' => ['usuarios' => null, 'productos' => null],
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function facturacionConfig()
    {
        return $this->hasOne(FacturacionConfig::class);
    }

    public function suscripcionPagos()
    {
        return $this->hasMany(SuscripcionPago::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    public function precioPlan(): float
    {
        return self::PRECIOS_PLAN[$this->plan] ?? 0.0;
    }

    public function planVencido(): bool
    {
        return $this->plan_vence && $this->plan_vence->isPast();
    }

    /* ===== Límites por plan ===== */

    public function limiteUsuarios(): ?int
    {
        return self::LIMITES_PLAN[$this->plan]['usuarios'] ?? null;
    }

    public function limiteProductos(): ?int
    {
        return self::LIMITES_PLAN[$this->plan]['productos'] ?? null;
    }

    public function usuariosUsados(): int
    {
        return $this->users()->count();
    }

    public function productosUsados(): int
    {
        return $this->productos()->where('activo', true)->count();
    }

    public function puedeAgregarUsuario(): bool
    {
        $limite = $this->limiteUsuarios();

        return is_null($limite) || $this->usuariosUsados() < $limite;
    }

    public function puedeAgregarProducto(): bool
    {
        $limite = $this->limiteProductos();

        return is_null($limite) || $this->productosUsados() < $limite;
    }
}
