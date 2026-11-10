<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $proveedores = Proveedor::withCount('compras')
            ->withSum(['compras as total_comprado' => fn ($q) => $q->where('estado', 'recibida')], 'total')
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = $request->buscar;
                $q->where(fn ($w) => $w->where('razon_social', 'like', "%{$b}%")
                    ->orWhere('ruc', 'like', "%{$b}%")
                    ->orWhere('contacto', 'like', "%{$b}%"));
            })
            ->when($request->estado === 'inactivos', fn ($q) => $q->where('activo', false), fn ($q) => $q->where('activo', true))
            ->orderBy('razon_social')
            ->paginate(12)
            ->withQueryString();

        return view('proveedores.index', compact('proveedores'));
    }

    public function store(Request $request)
    {
        Proveedor::create($this->validar($request));

        return back()->with('ok', 'Proveedor registrado.');
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $proveedor->update($this->validar($request));

        return back()->with('ok', 'Proveedor actualizado.');
    }

    public function destroy(Proveedor $proveedor)
    {
        $proveedor->update(['activo' => false]);

        return back()->with('ok', "Proveedor «{$proveedor->razon_social}» desactivado.");
    }

    public function activar(Proveedor $proveedor)
    {
        $proveedor->update(['activo' => true]);

        return back()->with('ok', "Proveedor «{$proveedor->razon_social}» reactivado.");
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'razon_social' => ['required', 'string', 'max:255'],
            'ruc' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'contacto' => ['nullable', 'string', 'max:255'],
        ], [
            'razon_social.required' => 'La razón social es obligatoria.',
            'email.email' => 'Ingresa un correo válido.',
        ]);
    }
}
