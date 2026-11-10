@php
    /** @var string $recurso  'usuarios' | 'productos' */
    $emp = auth()->user()->empresa;
    $limite = $recurso === 'usuarios' ? $emp->limiteUsuarios() : $emp->limiteProductos();
    $usados = $recurso === 'usuarios' ? $emp->usuariosUsados() : $emp->productosUsados();
    $etiqueta = $recurso === 'usuarios' ? 'usuarios' : 'productos activos';
@endphp

@if (! is_null($limite))
    @php
        $pct = $limite > 0 ? min(100, round(($usados / $limite) * 100)) : 100;
        $lleno = $usados >= $limite;
        $barra = $lleno ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-teal-brand');
    @endphp
    <div class="mb-5 bg-white rounded-xl border {{ $lleno ? 'border-red-200' : 'border-slate-200' }} p-4">
        <div class="flex items-center justify-between gap-3 mb-2">
            <p class="text-sm font-semibold text-slate-600">
                Plan <span class="capitalize font-bold text-navy">{{ $emp->plan }}</span> ·
                {{ $usados }} / {{ $limite }} {{ $etiqueta }}
            </p>
            @if ($lleno)
                <span class="text-[11px] font-bold px-2 py-1 rounded-full bg-red-100 text-red-700">Límite alcanzado</span>
            @elseif ($pct >= 80)
                <span class="text-[11px] font-bold px-2 py-1 rounded-full bg-amber-100 text-amber-700">Casi lleno</span>
            @endif
        </div>
        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full {{ $barra }}" style="width: {{ $pct }}%"></div>
        </div>
        @if ($lleno)
            <p class="text-[11px] text-red-500 mt-2">Para registrar más {{ $etiqueta }} mejora el plan de tu ferretería.</p>
        @endif
    </div>
@endif
