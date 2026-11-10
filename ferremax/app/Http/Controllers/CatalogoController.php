<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Unidad;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    public function index()
    {
        return view('catalogos.index', [
            'categorias' => Categoria::withCount('productos')->orderBy('nombre')->get(),
            'marcas' => Marca::withCount('productos')->orderBy('nombre')->get(),
            'unidades' => Unidad::withCount('productos')->orderBy('nombre')->get(),
        ]);
    }

    // ----- Categorías -----
    public function guardarCategoria(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        Categoria::create($datos);

        return back()->with('ok', 'Categoría creada.');
    }

    public function actualizarCategoria(Request $request, Categoria $categoria)
    {
        $categoria->update($request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]));

        return back()->with('ok', 'Categoría actualizada.');
    }

    public function eliminarCategoria(Categoria $categoria)
    {
        if ($categoria->productos()->exists()) {
            return back()->with('error', 'No se puede eliminar: tiene productos asociados.');
        }
        $categoria->delete();

        return back()->with('ok', 'Categoría eliminada.');
    }

    // ----- Marcas -----
    public function guardarMarca(Request $request)
    {
        Marca::create($request->validate(['nombre' => ['required', 'string', 'max:255']]));

        return back()->with('ok', 'Marca creada.');
    }

    public function actualizarMarca(Request $request, Marca $marca)
    {
        $marca->update($request->validate(['nombre' => ['required', 'string', 'max:255']]));

        return back()->with('ok', 'Marca actualizada.');
    }

    public function eliminarMarca(Marca $marca)
    {
        if ($marca->productos()->exists()) {
            return back()->with('error', 'No se puede eliminar: tiene productos asociados.');
        }
        $marca->delete();

        return back()->with('ok', 'Marca eliminada.');
    }

    // ----- Unidades -----
    public function guardarUnidad(Request $request)
    {
        Unidad::create($request->validate([
            'nombre' => ['required', 'string', 'max:50'],
            'abreviatura' => ['required', 'string', 'max:10'],
        ]));

        return back()->with('ok', 'Unidad creada.');
    }

    public function actualizarUnidad(Request $request, Unidad $unidad)
    {
        $unidad->update($request->validate([
            'nombre' => ['required', 'string', 'max:50'],
            'abreviatura' => ['required', 'string', 'max:10'],
        ]));

        return back()->with('ok', 'Unidad actualizada.');
    }

    public function eliminarUnidad(Unidad $unidad)
    {
        if ($unidad->productos()->exists()) {
            return back()->with('error', 'No se puede eliminar: tiene productos asociados.');
        }
        $unidad->delete();

        return back()->with('ok', 'Unidad eliminada.');
    }
}
