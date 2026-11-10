<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('empresa')
            ->where('rol', '!=', 'superadmin')
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = $request->buscar;
                $q->where(fn ($w) => $w->where('name', 'like', "%{$b}%")->orWhere('email', 'like', "%{$b}%"));
            })
            ->when($request->filled('empresa_id'), fn ($q) => $q->where('empresa_id', $request->empresa_id))
            ->when($request->filled('rol'), fn ($q) => $q->where('rol', $request->rol))
            ->when($request->estado === 'activos', fn ($q) => $q->where('activo', true))
            ->when($request->estado === 'inactivos', fn ($q) => $q->where('activo', false));

        $usuarios = $query->orderBy('empresa_id')->orderBy('name')->paginate(20)->withQueryString();
        $empresas = Empresa::orderBy('nombre')->get(['id', 'nombre']);

        $totalUsuarios = User::where('rol', '!=', 'superadmin')->count();
        $activos = User::where('rol', '!=', 'superadmin')->where('activo', true)->count();

        return view('superadmin.usuarios.index', compact('usuarios', 'empresas', 'totalUsuarios', 'activos'));
    }

    public function toggle(User $usuario)
    {
        if ($usuario->esSuperAdmin()) {
            return back()->with('error', 'No se puede modificar una cuenta de plataforma.');
        }

        $usuario->update(['activo' => ! $usuario->activo]);

        return back()->with('ok', "Usuario {$usuario->name} " . ($usuario->activo ? 'activado' : 'desactivado') . '.');
    }

    public function resetPassword(Request $request, User $usuario)
    {
        if ($usuario->esSuperAdmin()) {
            return back()->with('error', 'No se puede modificar una cuenta de plataforma.');
        }

        $datos = $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ], ['password.min' => 'La nueva contraseña debe tener al menos 6 caracteres.']);

        $usuario->update(['password' => Hash::make($datos['password'])]);

        return back()->with('ok', "Contraseña de {$usuario->name} restablecida.");
    }
}
