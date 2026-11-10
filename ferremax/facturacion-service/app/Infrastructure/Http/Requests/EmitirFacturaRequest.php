<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class EmitirFacturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la autenticacion la resuelve el middleware (token del ERP)
    }

    public function rules(): array
    {
        return [
            'pais'                       => 'required|string|size:2',
            'tipo'                       => 'required|in:factura,boleta',
            'serie'                      => 'required|string|max:8',
            'numero'                     => 'required|string|max:12',
            'moneda'                     => 'required|string|size:3',
            'fechaEmision'               => 'required|date',
            'emisor.identificadorFiscal' => 'required|string',
            'emisor.razonSocial'         => 'required|string',
            'receptor.tipoDocumento'     => 'required|string',
            'receptor.numeroDocumento'   => 'required|string',
            'receptor.razonSocial'       => 'required|string',
            'lineas'                     => 'required|array|min:1',
            'lineas.*.descripcion'       => 'required|string',
            'lineas.*.cantidad'          => 'required|numeric|gt:0',
            'lineas.*.precioUnitario'    => 'required|numeric|gte:0',
        ];
    }
}
