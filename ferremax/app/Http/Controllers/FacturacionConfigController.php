<?php

namespace App\Http\Controllers;

use App\Models\ComprobanteElectronico;
use App\Models\FacturacionConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FacturacionConfigController extends Controller
{
    private function soloAdmin(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403, 'Solo los administradores pueden configurar la facturación electrónica.');
    }

    public function index()
    {
        $this->soloAdmin();

        $empresa = auth()->user()->empresa;

        // Configuracion del emisor (se crea en memoria si aun no existe, con datos de la empresa)
        $config = $empresa->facturacionConfig ?: new FacturacionConfig([
            'ruc'              => $empresa->ruc,
            'razon_social'     => $empresa->nombre,
            'direccion_fiscal' => $empresa->direccion,
            'ambiente'         => 'beta',
        ]);

        $stats = ComprobanteElectronico::where('empresa_id', $empresa->id)
            ->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado');

        return view('configuracion.facturacion', [
            'empresa' => $empresa,
            'config'  => $config,
            'stats'   => $stats,
            'total'   => $stats->sum(),
            'global'  => [
                'enabled'  => (bool) config('facturacion.enabled'),
                'base_url' => config('facturacion.base_url'),
                'con_token' => filled(config('facturacion.token')),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $this->soloAdmin();

        $datos = $request->validate([
            // Ajustes generales (en la tabla empresas)
            'factura_activa'        => ['nullable', 'boolean'],
            'factura_modo'          => ['required', 'in:simulado,real'],
            'factura_pais'          => ['required', 'in:PE,CO,CL,AR,MX'],
            'factura_serie_factura' => ['required', 'string', 'max:8'],
            'factura_serie_boleta'  => ['required', 'string', 'max:8'],
            // Emisor / ambiente / credenciales (en facturacion_configs)
            'ambiente'          => ['required', 'in:beta,produccion'],
            'ruc'               => ['nullable', 'string', 'max:20'],
            'razon_social'      => ['nullable', 'string', 'max:255'],
            'nombre_comercial'  => ['nullable', 'string', 'max:255'],
            'direccion_fiscal'  => ['nullable', 'string', 'max:255'],
            'ubigeo'            => ['nullable', 'string', 'max:10'],
            'usuario_sol'       => ['nullable', 'string', 'max:100'],
            'clave_sol'         => ['nullable', 'string', 'max:100'],
            'clave_certificado' => ['nullable', 'string', 'max:100'],
            'certificado_vence' => ['nullable', 'date'],
            'certificado'       => ['nullable', 'file', 'max:5120'],
        ]);

        $empresa = auth()->user()->empresa;

        // 1) Ajustes generales
        $empresa->update([
            'factura_activa'        => $request->boolean('factura_activa'),
            'factura_modo'          => $datos['factura_modo'],
            'factura_pais'          => $datos['factura_pais'],
            'factura_serie_factura' => $datos['factura_serie_factura'],
            'factura_serie_boleta'  => $datos['factura_serie_boleta'],
        ]);

        // 2) Configuracion del emisor
        $config = $empresa->facturacionConfig ?: new FacturacionConfig(['empresa_id' => $empresa->id]);
        $config->empresa_id       = $empresa->id;
        $config->ambiente         = $datos['ambiente'];
        $config->ruc              = $datos['ruc'] ?? null;
        $config->razon_social     = $datos['razon_social'] ?? null;
        $config->nombre_comercial = $datos['nombre_comercial'] ?? null;
        $config->direccion_fiscal = $datos['direccion_fiscal'] ?? null;
        $config->ubigeo           = $datos['ubigeo'] ?? null;
        $config->usuario_sol      = $datos['usuario_sol'] ?? null;
        $config->certificado_vence = $datos['certificado_vence'] ?? null;

        // Las claves solo se actualizan si el admin escribe algo (no se borran al guardar).
        if (filled($datos['clave_sol'] ?? null)) {
            $config->clave_sol = $datos['clave_sol'];
        }
        if (filled($datos['clave_certificado'] ?? null)) {
            $config->clave_certificado = $datos['clave_certificado'];
        }

        // Certificado digital: se guarda fuera de public/.
        if ($request->hasFile('certificado')) {
            $file = $request->file('certificado');
            $ext = strtolower($file->getClientOriginalExtension());
            if (! in_array($ext, ['pfx', 'p12', 'pem'], true)) {
                return back()->with('error', 'El certificado debe ser .pfx, .p12 o .pem.');
            }
            // Borra el anterior si existe
            if ($config->certificado_path) {
                Storage::disk('local')->delete($config->certificado_path);
            }
            $config->certificado_path = $file->storeAs("certificados/{$empresa->id}", 'certificado.' . $ext, 'local');
            $config->certificado_nombre = $file->getClientOriginalName();
        }

        $config->save();

        return back()->with('ok', 'Configuración de facturación electrónica guardada.');
    }
}
