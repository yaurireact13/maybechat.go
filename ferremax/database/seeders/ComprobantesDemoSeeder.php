<?php

namespace Database\Seeders;

use App\Models\ComprobanteElectronico;
use App\Models\Venta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Genera comprobantes electronicos de demostracion (modo simulado) para las
 * ventas boleta/factura ya existentes, para que la UI se vea poblada.
 *
 * Idempotente: se puede ejecutar sobre una base ya poblada sin duplicar ni perder datos:
 *   php artisan db:seed --class=ComprobantesDemoSeeder
 */
class ComprobantesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $correlativos = []; // [empresa_id.serie] => contador

        Venta::withoutGlobalScope('empresa')
            ->whereIn('tipo_comprobante', ['boleta', 'factura'])
            ->where('estado', '!=', 'anulada')
            ->orderBy('id')
            ->chunk(200, function ($ventas) use (&$correlativos) {
                foreach ($ventas as $v) {
                    $ce = ComprobanteElectronico::firstOrNew(['venta_id' => $v->id]);
                    if ($ce->exists) {
                        continue; // ya tiene comprobante
                    }

                    $serie = $v->tipo_comprobante === 'factura' ? 'F001' : 'B001';
                    $clave = $v->empresa_id . '.' . $serie;
                    $correlativos[$clave] = ($correlativos[$clave] ?? 0) + 1;
                    $numero = str_pad((string) $correlativos[$clave], 8, '0', STR_PAD_LEFT);

                    // Distribucion realista de estados (la mayoria aceptados)
                    $estado = match (true) {
                        $v->id % 23 === 0 => 'rechazado',
                        $v->id % 17 === 0 => 'observado',
                        $v->id % 11 === 0 => 'error',
                        default           => 'aceptado',
                    };

                    $ce->fill([
                        'empresa_id' => $v->empresa_id,
                        'pais'       => 'PE',
                        'tipo'       => $v->tipo_comprobante,
                        'serie'      => $serie,
                        'numero'     => $numero,
                        'estado'     => $estado,
                        'modo'       => 'simulado',
                        'intentos'   => 1,
                        'codigo_unico' => in_array($estado, ['aceptado', 'observado'], true)
                            ? strtoupper(Str::random(8)) . '-SIM' : null,
                        'comprobante_id_externo' => (string) Str::uuid(),
                        'mensaje' => match ($estado) {
                            'aceptado'  => 'Aceptado (modo simulado).',
                            'observado' => 'Aceptado con observaciones (simulado).',
                            'rechazado' => 'Rechazado por el organismo (simulado): dato del receptor invalido.',
                            'error'     => 'Fallo tecnico de envio (simulado). Reintentar.',
                            default     => null,
                        },
                        'respuesta' => ['simulado' => true],
                        'created_at' => $v->fecha,
                        'updated_at' => $v->fecha,
                    ])->save();
                }
            });
    }
}
