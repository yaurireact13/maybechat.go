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
}
