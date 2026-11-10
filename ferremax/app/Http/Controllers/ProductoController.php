<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $productos = Producto::with(['categoria', 'marca', 'unidad'])
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = $request->buscar;
                $q->where(fn ($w) => $w->where('nombre', 'like', "%{$b}%")
                    ->orWhere('codigo', 'like', "%{$b}%")
                    ->orWhere('codigo_barras', 'like', "%{$b}%"));
            })
            ->when($request->filled('categoria'), fn ($q) => $q->where('categoria_id', $request->categoria))
            ->when($request->filled('marca'), fn ($q) => $q->where('marca_id', $request->marca))
            ->when($request->estado === 'stock_bajo', fn ($q) => $q->stockBajo())
            ->when($request->estado === 'inactivos', fn ($q) => $q->where('activo', false))
            ->when(! $request->filled('estado') || $request->estado === 'activos', fn ($q) => $q->where('activo', true))
            ->orderBy('nombre')
            ->paginate(12)
            ->withQueryString();

        return view('productos.index', [
            'productos' => $productos,
            'categorias' => Categoria::where('activo', true)->orderBy('nombre')->get(),
            'marcas' => Marca::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function create()
    {
        return view('productos.form', [
            'producto' => new Producto(['stock' => 0, 'stock_minimo' => 5, 'activo' => true]),
            'categorias' => Categoria::where('activo', true)->orderBy('nombre')->get(),
            'marcas' => Marca::where('activo', true)->orderBy('nombre')->get(),
            'unidades' => Unidad::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $empresa = auth()->user()->empresa;
        if (! $empresa->puedeAgregarProducto()) {
            return back()->withInput()->with('error', "Alcanzaste el límite de {$empresa->limiteProductos()} productos del plan " . ucfirst($empresa->plan) . '. Mejora tu plan para registrar más.');
        }

        $datos = $this->validar($request);

        $producto = Producto::create($datos);

        if ($producto->stock > 0) {
            MovimientoInventario::create([
                'producto_id' => $producto->id,
                'user_id' => auth()->id(),
                'tipo' => 'entrada',
                'cantidad' => $producto->stock,
                'motivo' => 'Stock inicial',
                'fecha' => now(),
            ]);
        }

        return redirect()->route('productos.index')->with('ok', "Producto «{$producto->nombre}» creado correctamente.");
    }

    public function edit(Producto $producto)
    {
        return view('productos.form', [
            'producto' => $producto,
            'categorias' => Categoria::where('activo', true)->orderBy('nombre')->get(),
            'marcas' => Marca::where('activo', true)->orderBy('nombre')->get(),
            'unidades' => Unidad::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Producto $producto)
    {
        $datos = $this->validar($request, $producto);

        $stockAnterior = (float) $producto->stock;
        $producto->update($datos);
        $diferencia = (float) $producto->stock - $stockAnterior;

        if ($diferencia != 0) {
            MovimientoInventario::create([
                'producto_id' => $producto->id,
                'user_id' => auth()->id(),
                'tipo' => 'ajuste',
                'cantidad' => abs($diferencia),
                'motivo' => 'Ajuste manual de stock (' . ($diferencia > 0 ? '+' : '-') . abs($diferencia) . ')',
                'fecha' => now(),
            ]);
        }

        return redirect()->route('productos.index')->with('ok', "Producto «{$producto->nombre}» actualizado.");
    }

    public function destroy(Producto $producto)
    {
        // Borrado lógico: lo desactivamos para no romper ventas/compras históricas
        $producto->update(['activo' => false]);

        return redirect()->route('productos.index')->with('ok', "Producto «{$producto->nombre}» desactivado.");
    }

    public function activar(Producto $producto)
    {
        $empresa = auth()->user()->empresa;
        if (! $empresa->puedeAgregarProducto()) {
            return back()->with('error', "Alcanzaste el límite de {$empresa->limiteProductos()} productos activos del plan " . ucfirst($empresa->plan) . '. Mejora tu plan o desactiva otros productos.');
        }

        $producto->update(['activo' => true]);

        return redirect()->route('productos.index', ['estado' => 'inactivos'])->with('ok', "Producto «{$producto->nombre}» reactivado.");
    }

    private function validar(Request $request, ?Producto $producto = null): array
    {
        return $request->validate([
            'codigo' => [
                'required', 'string', 'max:50',
                Rule::unique('productos', 'codigo')
                    ->where('empresa_id', auth()->user()->empresa_id)
                    ->ignore($producto?->id),
            ],
            'codigo_barras' => ['nullable', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'marca_id' => ['nullable', 'exists:marcas,id'],
            'unidad_id' => ['nullable', 'exists:unidades,id'],
            'precio_compra' => ['required', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0', 'gte:precio_compra'],
            'stock' => ['required', 'numeric', 'min:0'],
            'stock_minimo' => ['required', 'numeric', 'min:0'],
            'ubicacion' => ['nullable', 'string', 'max:100'],
        ], [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'Ya existe un producto con este código.',
            'nombre.required' => 'El nombre es obligatorio.',
            'precio_venta.gte' => 'El precio de venta debe ser mayor o igual al precio de compra.',
        ]);
    }
}
