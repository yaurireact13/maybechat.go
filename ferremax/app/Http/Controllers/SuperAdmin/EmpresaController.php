<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Unidad;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmpresaController extends Controller
{
    public function index(Request $request)
    {
        $query = Empresa::withCount(['users', 'productos'])
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = $request->buscar;
                $q->where(fn ($w) => $w->where('nombre', 'like', "%{$b}%")
                    ->orWhere('ruc', 'like', "%{$b}%")
                    ->orWhere('email', 'like', "%{$b}%"));
            })
            ->when($request->filled('plan'), fn ($q) => $q->where('plan', $request->plan))
            ->when($request->estado === 'activas', fn ($q) => $q->where('activo', true))
            ->when($request->estado === 'suspendidas', fn ($q) => $q->where('activo', false));

        $empresas = $query->orderByDesc('created_at')->paginate(12)->withQueryString();

        $totalEmpresas = Empresa::count();
        $activas = Empresa::where('activo', true)->count();

        return view('superadmin.empresas.index', compact('empresas', 'totalEmpresas', 'activas'));
    }

    public function create()
    {
        return view('superadmin.empresas.form', ['empresa' => new Empresa(['plan' => 'basico', 'moneda' => 'S/', 'impuesto' => 18])]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'ruc' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'plan' => ['required', 'in:basico,pro,premium'],
            'plan_vence' => ['nullable', 'date'],
            'moneda' => ['required', 'string', 'max:10'],
            'impuesto' => ['required', 'numeric', 'min:0', 'max:100'],
            // Administrador inicial
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:6'],
        ], [
            'nombre.required' => 'El nombre de la ferretería es obligatorio.',
            'admin_email.unique' => 'Ese correo ya está en uso por otro usuario.',
            'admin_password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        $empresa = DB::transaction(function () use ($datos) {
            $empresa = Empresa::create([
                'nombre' => $datos['nombre'],
                'ruc' => $datos['ruc'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
                'direccion' => $datos['direccion'] ?? null,
                'plan' => $datos['plan'],
                'plan_vence' => $datos['plan_vence'] ?? null,
                'moneda' => $datos['moneda'],
                'impuesto' => $datos['impuesto'],
                'activo' => true,
            ]);

            User::create([
                'empresa_id' => $empresa->id,
                'name' => $datos['admin_name'],
                'email' => $datos['admin_email'],
                'password' => Hash::make($datos['admin_password']),
                'rol' => 'admin',
            ]);

            // Provisión inicial para que el tenant no empiece vacío
            foreach (['Herramientas Manuales', 'Herramientas Eléctricas', 'Tornillería y Fijaciones', 'Pinturas y Acabados', 'Electricidad', 'Gasfitería', 'Construcción', 'Seguridad Industrial'] as $cat) {
                Categoria::create(['empresa_id' => $empresa->id, 'nombre' => $cat]);
            }
            foreach ([['Unidad', 'UND'], ['Caja', 'CJA'], ['Metro', 'MT'], ['Galón', 'GAL'], ['Kilogramo', 'KG'], ['Bolsa', 'BLS']] as [$n, $a]) {
                Unidad::create(['empresa_id' => $empresa->id, 'nombre' => $n, 'abreviatura' => $a]);
            }
            Cliente::create(['empresa_id' => $empresa->id, 'nombre' => 'Cliente Varios', 'tipo_documento' => 'DNI']);

            return $empresa;
        });

        return redirect()->route('superadmin.empresas.show', $empresa)
            ->with('ok', "Ferretería «{$empresa->nombre}» creada con su administrador.");
    }

    public function show(Empresa $empresa)
    {
        $empresa->loadCount(['users', 'productos', 'clientes']);

        $ingresos = Venta::where('empresa_id', $empresa->id)->where('estado', 'completada')->sum('total');
        $ventasMes = Venta::where('empresa_id', $empresa->id)->where('estado', 'completada')
            ->where('fecha', '>=', Carbon::now()->startOfMonth())->sum('total');
        $ultimaVenta = Venta::where('empresa_id', $empresa->id)->latest('fecha')->value('fecha');

        $usuarios = User::where('empresa_id', $empresa->id)->orderBy('rol')->orderBy('name')->get();
        $ultimasVentas = Venta::where('empresa_id', $empresa->id)->with('cliente')->latest('fecha')->limit(8)->get();

        $pagosSuscripcion = $empresa->suscripcionPagos()->with('registrador')->latest('fecha_pago')->limit(8)->get();

        return view('superadmin.empresas.show', compact(
            'empresa', 'ingresos', 'ventasMes', 'ultimaVenta', 'usuarios', 'ultimasVentas', 'pagosSuscripcion'
        ));
    }

    public function edit(Empresa $empresa)
    {
        return view('superadmin.empresas.form', compact('empresa'));
    }

    public function update(Request $request, Empresa $empresa)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'ruc' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'plan' => ['required', 'in:basico,pro,premium'],
            'plan_vence' => ['nullable', 'date'],
            'moneda' => ['required', 'string', 'max:10'],
            'impuesto' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $empresa->update($datos);

        return redirect()->route('superadmin.empresas.show', $empresa)
            ->with('ok', 'Datos de la ferretería actualizados.');
    }

    public function suspender(Request $request, Empresa $empresa)
    {
        $empresa->activo = ! $empresa->activo;
        $empresa->suspendido_motivo = $empresa->activo ? null : $request->input('motivo');

        // Bloquea/restaura el acceso de los usuarios del tenant
        User::where('empresa_id', $empresa->id)->update(['activo' => $empresa->activo]);
        $empresa->save();

        $msg = $empresa->activo
            ? "Ferretería «{$empresa->nombre}» reactivada. Sus usuarios pueden iniciar sesión."
            : "Ferretería «{$empresa->nombre}» suspendida. Se bloqueó el acceso de sus usuarios.";

        return back()->with('ok', $msg);
    }

    /* ===== Impersonación (soporte) ===== */

    public function impersonar(Request $request, Empresa $empresa)
    {
        $objetivo = User::where('empresa_id', $empresa->id)
            ->where('rol', 'admin')->where('activo', true)->first()
            ?? User::where('empresa_id', $empresa->id)->where('activo', true)->first();

        if (! $objetivo) {
            return back()->with('error', 'Esta ferretería no tiene usuarios activos para impersonar.');
        }

        $request->session()->put('impersonator_id', auth()->id());
        Auth::login($objetivo);

        return redirect()->route('dashboard')
            ->with('ok', "Estás operando como «{$objetivo->name}» de {$empresa->nombre}.");
    }

    public function salirImpersonacion(Request $request)
    {
        $superId = $request->session()->pull('impersonator_id');

        if (! $superId) {
            return redirect()->route('dashboard');
        }

        $super = User::find($superId);
        if ($super) {
            Auth::login($super);
        }

        return redirect()->route('superadmin.empresas.index')->with('ok', 'Volviste a tu panel de plataforma.');
    }
}
