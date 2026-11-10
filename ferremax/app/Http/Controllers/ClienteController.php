<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $clientes = Cliente::withCount('ventas')
            ->withSum(['ventas as total_comprado' => fn ($q) => $q->where('estado', 'completada')], 'total')
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = $request->buscar;
                $q->where(fn ($w) => $w->where('nombre', 'like', "%{$b}%")
                    ->orWhere('numero_documento', 'like', "%{$b}%")
                    ->orWhere('telefono', 'like', "%{$b}%"));
            })
            ->when($request->estado === 'inactivos', fn ($q) => $q->where('activo', false), fn ($q) => $q->where('activo', true))
            ->orderBy('nombre')
            ->paginate(12)
            ->withQueryString();

        return view('clientes.index', compact('clientes'));
    }

    public function store(Request $request)
    {
        Cliente::create($this->validar($request));

        return back()->with('ok', 'Cliente registrado.');
    }

    public function update(Request $request, Cliente $cliente)
    {
        $cliente->update($this->validar($request));

        return back()->with('ok', 'Cliente actualizado.');
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->update(['activo' => false]);

        return back()->with('ok', "Cliente «{$cliente->nombre}» desactivado.");
    }

    public function activar(Cliente $cliente)
    {
        $cliente->update(['activo' => true]);

        return back()->with('ok', "Cliente «{$cliente->nombre}» reactivado.");
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'tipo_documento' => ['required', 'in:DNI,RUC,CE,PASAPORTE'],
            'numero_documento' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
        ]);
    }
}
