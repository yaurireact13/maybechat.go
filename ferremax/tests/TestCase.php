<?php

namespace Tests;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Crea una ferretería con un administrador, un vendedor, una categoría, un cliente y un producto. */
    protected function crearFerreteria(string $sufijo = 'a', array $empresa = []): array
    {
        $e = Empresa::create(array_merge([
            'nombre' => "Ferretería Test {$sufijo}",
            'plan' => 'pro',
            'plan_vence' => now()->addMonth()->toDateString(),
        ], $empresa));

        $admin = User::create([
            'empresa_id' => $e->id, 'name' => "Admin {$sufijo}", 'email' => "admin-{$sufijo}@test.local",
            'password' => 'secreto123', 'rol' => 'admin',
        ]);
        $vendedor = User::create([
            'empresa_id' => $e->id, 'name' => "Vendedor {$sufijo}", 'email' => "vendedor-{$sufijo}@test.local",
            'password' => 'secreto123', 'rol' => 'vendedor',
        ]);

        $categoria = Categoria::create(['empresa_id' => $e->id, 'nombre' => "Herramientas {$sufijo}"]);
        $cliente = Cliente::create(['empresa_id' => $e->id, 'nombre' => "Cliente {$sufijo}", 'tipo_documento' => 'DNI']);
        $producto = Producto::create([
            'empresa_id' => $e->id, 'categoria_id' => $categoria->id, 'codigo' => "P-{$sufijo}",
            'nombre' => "Martillo {$sufijo}", 'precio_compra' => 20, 'precio_venta' => 50,
            'stock' => 10, 'stock_minimo' => 2,
        ]);

        return compact('e', 'admin', 'vendedor', 'categoria', 'cliente', 'producto');
    }

    protected function superAdmin(): User
    {
        return User::create([
            'empresa_id' => null, 'name' => 'Super', 'email' => 'super@test.local',
            'password' => 'secreto123', 'rol' => 'superadmin',
        ]);
    }
}
