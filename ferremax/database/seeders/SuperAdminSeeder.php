<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea (o actualiza) la cuenta Super Admin de la plataforma.
 * Es idempotente: se puede ejecutar sobre una base ya poblada sin perder datos:
 *   php artisan db:seed --class=SuperAdminSeeder
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@ferremax.com'],
            [
                'empresa_id' => null,
                'name' => 'Super Administrador',
                'password' => Hash::make('super123'),
                'rol' => 'superadmin',
                'activo' => true,
            ]
        );
    }
}
