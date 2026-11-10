<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    private function soloAdmin(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403, 'Solo los administradores pueden gestionar usuarios.');
    }

    public function index()
    {
        $this->soloAdmin();

        $usuarios = User::where('empresa_id', auth()->user()->empresa_id)
            ->orderBy('name')
            ->get();

        return view('usuarios.index', compact('usuarios'));
    }

    public function store(Request $request)
    {
        $this->soloAdmin();

        $empresa = auth()->user()->empresa;
        if (! $empresa->puedeAgregarUsuario()) {
            return back()->with('error', "Alcanzaste el límite de {$empresa->limiteUsuarios()} usuarios del plan " . ucfirst($empresa->plan) . '. Mejora tu plan para agregar más.');
        }

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'rol' => ['required', 'in:admin,vendedor,almacenero,cajero'],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'email.unique' => 'Este correo ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        User::create([
            'empresa_id' => auth()->user()->empresa_id,
            'name' => $datos['name'],
            'email' => $datos['email'],
            'rol' => $datos['rol'],
            'password' => Hash::make($datos['password']),
        ]);

        return back()->with('ok', "Usuario «{$datos['name']}» creado.");
    }

    public function update(Request $request, User $usuario)
    {
        $this->soloAdmin();
        abort_unless($usuario->empresa_id === auth()->user()->empresa_id, 404);

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'rol' => ['required', 'in:admin,vendedor,almacenero,cajero'],
            'password' => ['nullable', 'string', 'min:6'],
        ], [
            'email.unique' => 'Este correo ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        if ($usuario->id === auth()->id() && $datos['rol'] !== 'admin') {
            return back()->with('error', 'No puedes quitarte el rol de administrador a ti mismo.');
        }

        $usuario->update([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'rol' => $datos['rol'],
            ...(filled($datos['password'] ?? null) ? ['password' => Hash::make($datos['password'])] : []),
        ]);

        return back()->with('ok', "Usuario «{$usuario->name}» actualizado.");
    }

    public function alternar(User $usuario)
    {
        $this->soloAdmin();
        abort_unless($usuario->empresa_id === auth()->user()->empresa_id, 404);

        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes desactivar tu propio usuario.');
        }

        $usuario->update(['activo' => ! $usuario->activo]);

        return back()->with('ok', 'Usuario ' . ($usuario->activo ? 'activado' : 'desactivado') . '.');
    }
}
