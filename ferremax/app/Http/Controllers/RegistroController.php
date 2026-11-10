<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegistroController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.registro');
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            // Empresa
            'empresa_nombre' => ['required', 'string', 'max:255'],
            'empresa_ruc' => ['nullable', 'string', 'max:20'],
            'empresa_telefono' => ['nullable', 'string', 'max:30'],
            'empresa_direccion' => ['nullable', 'string', 'max:255'],
            'moneda' => ['required', 'string', 'max:10'],
            'impuesto' => ['required', 'numeric', 'min:0', 'max:100'],
            // Cuenta admin
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'acepta' => ['accepted'],
        ], [
            'empresa_nombre.required' => 'El nombre de tu ferretería es obligatorio.',
            'name.required' => 'Tu nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.unique' => 'Este correo ya está registrado. ¿Quieres iniciar sesión?',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'acepta.accepted' => 'Debes aceptar los términos para continuar.',
        ]);

        $user = DB::transaction(function () use ($datos) {
            $empresa = Empresa::create([
                'nombre' => $datos['empresa_nombre'],
                'ruc' => $datos['empresa_ruc'] ?? null,
                'telefono' => $datos['empresa_telefono'] ?? null,
                'direccion' => $datos['empresa_direccion'] ?? null,
                'moneda' => $datos['moneda'],
                'impuesto' => $datos['impuesto'],
                'plan' => 'basico',
            ]);

            $user = User::create([
                'empresa_id' => $empresa->id,
                'name' => $datos['name'],
                'email' => $datos['email'],
                'password' => Hash::make($datos['password']),
                'rol' => 'admin',
            ]);

            // Datos iniciales para que la empresa no empiece vacía
            foreach (['Herramientas Manuales', 'Herramientas Eléctricas', 'Tornillería y Fijaciones', 'Pinturas y Acabados', 'Electricidad', 'Gasfitería', 'Construcción', 'Seguridad Industrial'] as $cat) {
                Categoria::create(['empresa_id' => $empresa->id, 'nombre' => $cat]);
            }

            foreach ([['Unidad', 'UND'], ['Caja', 'CJA'], ['Metro', 'MT'], ['Galón', 'GAL'], ['Kilogramo', 'KG'], ['Bolsa', 'BLS']] as [$n, $a]) {
                Unidad::create(['empresa_id' => $empresa->id, 'nombre' => $n, 'abreviatura' => $a]);
            }

            Cliente::create([
                'empresa_id' => $empresa->id,
                'nombre' => 'Cliente Varios',
                'tipo_documento' => 'DNI',
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('bienvenida', true);
    }
}
