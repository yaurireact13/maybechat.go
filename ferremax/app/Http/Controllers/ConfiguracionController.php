<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    private function soloAdmin(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403, 'Solo los administradores pueden modificar la configuración.');
    }

    public function index()
    {
        $this->soloAdmin();

        return view('configuracion.index', ['empresa' => auth()->user()->empresa]);
    }

    public function update(Request $request)
    {
        $this->soloAdmin();

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'ruc' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'moneda' => ['required', 'string', 'max:10'],
            'impuesto' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'nombre.required' => 'El nombre de la empresa es obligatorio.',
            'impuesto.max' => 'El impuesto no puede superar el 100%.',
        ]);

        auth()->user()->empresa->update($datos);

        return back()->with('ok', 'Configuración guardada correctamente.');
    }
}
