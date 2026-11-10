<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ strtoupper($venta->tipo_comprobante) }} {{ $venta->numero }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; color: #000; background: #f1f5f9; display: flex; flex-direction: column; align-items: center; padding: 20px; }
        .acciones { margin-bottom: 16px; display: flex; gap: 8px; }
        .acciones button, .acciones a { font-family: Arial, sans-serif; font-size: 13px; font-weight: bold; padding: 9px 18px; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; }
        .btn-imprimir { background: #2A9D8F; color: #fff; }
        .btn-volver { background: #fff; color: #334155; border: 1px solid #cbd5e1 !important; }
        .ticket { background: #fff; width: 300px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.15); }
        .centro { text-align: center; }
        .negrita { font-weight: bold; }
        .linea { border-top: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1px 0; vertical-align: top; }
        .der { text-align: right; }
        .totales td { padding: 2px 0; }
        @media print {
            body { background: #fff; padding: 0; }
            .acciones { display: none; }
            .ticket { box-shadow: none; width: 100%; }
        }
    </style>
</head>
<body>
    <div class="acciones">
        <button class="btn-imprimir" onclick="window.print()">🖨 Imprimir</button>
        <a class="btn-volver" href="{{ route('pos.index') }}">← Volver al POS</a>
    </div>

    <div class="ticket">
        <div class="centro">
            <p class="negrita" style="font-size:14px">{{ $empresa->nombre }}</p>
            @if ($empresa->ruc)<p>RUC: {{ $empresa->ruc }}</p>@endif
            @if ($empresa->direccion)<p>{{ $empresa->direccion }}</p>@endif
            @if ($empresa->telefono)<p>Tel: {{ $empresa->telefono }}</p>@endif
        </div>

        <div class="linea"></div>
        <div class="centro negrita">
            <p>{{ strtoupper($venta->tipo_comprobante) }} DE VENTA</p>
            <p>{{ $venta->numero }}</p>
        </div>
        <div class="linea"></div>

        <p>Fecha: {{ $venta->fecha->format('d/m/Y H:i') }}</p>
        <p>Cliente: {{ $venta->cliente->nombre ?? 'Cliente Varios' }}</p>
        @if ($venta->cliente?->numero_documento)
            <p>{{ $venta->cliente->tipo_documento }}: {{ $venta->cliente->numero_documento }}</p>
        @endif
        <p>Atendido por: {{ $venta->user->name ?? '—' }}</p>

        <div class="linea"></div>
        <table>
            <tr class="negrita">
                <td>CANT</td>
                <td>DESCRIPCIÓN</td>
                <td class="der">IMPORTE</td>
            </tr>
            @foreach ($venta->detalles as $d)
                <tr>
                    <td style="width:36px">{{ $d->cantidad + 0 }}</td>
                    <td>{{ $d->producto->nombre ?? '—' }}<br><small>{{ number_format($d->precio, 2) }} c/u</small></td>
                    <td class="der">{{ number_format($d->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </table>

        <div class="linea"></div>
        <table class="totales">
            <tr><td>SUBTOTAL</td><td class="der">{{ $empresa->moneda }} {{ number_format($venta->subtotal, 2) }}</td></tr>
            <tr><td>IGV ({{ $empresa->impuesto + 0 }}%)</td><td class="der">{{ $empresa->moneda }} {{ number_format($venta->impuesto, 2) }}</td></tr>
            @if ($venta->descuento > 0)
                <tr><td>DESCUENTO</td><td class="der">- {{ $empresa->moneda }} {{ number_format($venta->descuento, 2) }}</td></tr>
            @endif
            <tr class="negrita" style="font-size:14px"><td>TOTAL</td><td class="der">{{ $empresa->moneda }} {{ number_format($venta->total, 2) }}</td></tr>
            <tr><td>Pago</td><td class="der">{{ strtoupper($venta->metodo_pago) }}</td></tr>
        </table>

        <div class="linea"></div>
        <div class="centro">
            <p>¡Gracias por su compra!</p>
            <p><small>Generado por FerreMax SaaS</small></p>
        </div>
    </div>
</body>
</html>
