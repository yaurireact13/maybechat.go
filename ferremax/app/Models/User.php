<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['empresa_id', 'name', 'email', 'password', 'rol', 'activo'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function esSuperAdmin(): bool
    {
        return $this->rol === 'superadmin';
    }

    /**
     * ¿El rol del usuario incluye este permiso? (ver config/permisos.php)
     * El administrador de la ferretería tiene todos; el super admin no opera
     * dentro de una ferretería (para eso usa la impersonación).
     */
    public function tienePermiso(string $permiso): bool
    {
        if (! $this->empresa_id) {
            return false;
        }

        if ($this->esAdmin()) {
            return true;
        }

        $roles = config("permisos.permisos.{$permiso}.1");

        return is_array($roles) && in_array($this->rol, $roles, true);
    }

    /** Nombre legible del rol (Administrador, Vendedor...). */
    public function getRolNombreAttribute(): string
    {
        return config("permisos.roles.{$this->rol}.nombre", ucfirst((string) $this->rol));
    }
}
