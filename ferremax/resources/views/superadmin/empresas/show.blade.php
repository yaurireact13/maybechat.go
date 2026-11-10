@extends('layouts.superadmin')

@section('titulo', $empresa->nombre)

@section('contenido')
@php $m = $empresa->moneda ?? 'S/'; @endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div class="flex items-center gap-3">
        <a href="{{ route('superadmin.empresas.index') }}" class="p-2 rounded-lg hover:bg-slate-200 text-slate-500">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
        </a>
        <div>
            <h2 class="text-2xl font-extrabold text-plat flex items-center gap-2">
                {{ $empresa->nombre }}
                @if ($empresa->activo)
                    <span class="text-[11px] font-bold px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">Activa</span>
                @else
                    <span class="text-[11px] font-bold px-2 py-1 rounded-full bg-red-100 text-red-700">Suspendida</span>
                @endif
            </h2>
            <p class="text-sm text-slate-500">{{ $empresa->ruc ? 'RUC ' . $empresa->ruc : 'Sin RUC' }} · {{ $empresa->telefono ?? 's/teléfono' }} · registrada {{ $empresa->created_at->format('d/m/Y') }}</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('superadmin.empresas.edit', $empresa) }}" class="text-sm font-bold text-plat bg-slate-100 hover:bg-slate-200 px-4 py-2.5 rounded-lg transition">Editar</a>
        @if ($empresa->activo)
            <form method="POST" action="{{ route('superadmin.empresas.impersonar', $empresa) }}">
                @csrf
                <button class="inline-flex items-center gap-2 text-sm font-bold text-white bg-plat-accent hover:opacity-90 px-4 py-2.5 rounded-lg shadow-lg shadow-plat-accent/30 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                    Impersonar
                </button>
            </form>
        @endif
    </div>
</div>

<!-- KPIs de la empresa -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Plan</p>
        <p class="text-2xl font-extrabold text-plat-accent mt-1 capitalize">{{ $empresa->plan }}</p>
        <p class="text-[11px] mt-1 {{ $empresa->planVencido() ? 'text-red-500 font-semibold' : 'text-slate-400' }}">
            {{ $empresa->plan_vence ? 'Vence ' . $empresa->plan_vence->format('d/m/Y') : 'Sin vencimiento' }}{{ $empresa->planVencido() ? ' (vencido)' : '' }}
        </p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Ingresos históricos</p>
        <p class="text-2xl font-extrabold text-plat mt-1">{{ $m }} {{ number_format($ingresos, 2) }}</p>
        <p class="text-[11px] text-slate-400 mt-1">{{ $m }} {{ number_format($ventasMes, 2) }} este mes</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Usuarios / Productos</p>
        <p class="text-2xl font-extrabold text-plat mt-1">{{ $empresa->users_count }} / {{ $empresa->productos_count }}</p>
        <p class="text-[11px] text-slate-400 mt-1">{{ $empresa->clientes_count }} clientes</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Última actividad</p>
        <p class="text-lg font-extrabold text-plat mt-2">{{ $ultimaVenta ? \Carbon\Carbon::parse($ultimaVenta)->format('d/m/Y') : '—' }}</p>
        <p class="text-[11px] text-slate-400 mt-1">{{ $ultimaVenta ? \Carbon\Carbon::parse($ultimaVenta)->locale('es')->diffForHumans() : 'sin ventas' }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <!-- Usuarios -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 font-bold text-plat">Usuarios de la ferretería</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                        <th class="px-5 py-3 font-bold">Nombre</th>
                        <th class="px-4 py-3 font-bold">Correo</th>
                        <th class="px-4 py-3 font-bold">Rol</th>
                        <th class="px-4 py-3 font-bold text-center">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($usuarios as $u)
                        <tr>
                            <td class="px-5 py-3 font-semibold text-plat">{{ $u->name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $u->email }}</td>
                            <td class="px-4 py-3 capitalize text-slate-600">{{ $u->rol }}</td>
                            <td class="px-4 py-3 text-center">
                                @if ($u->activo)
                                    <span class="text-[11px] font-bold px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">Activo</span>
                                @else
                                    <span class="text-[11px] font-bold px-2 py-1 rounded-full bg-slate-200 text-slate-600">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Acciones / suspensión -->
    <div class="space-y-5">
        <div class="bg-white rounded-xl border {{ $empresa->activo ? 'border-slate-200' : 'border-red-200' }} shadow-sm p-5">
            <h3 class="font-bold text-plat mb-2">{{ $empresa->activo ? 'Suspender ferretería' : 'Reactivar ferretería' }}</h3>
            <p class="text-xs text-slate-500 mb-4">
                {{ $empresa->activo
                    ? 'Bloquea el inicio de sesión de todos sus usuarios. Los datos se conservan.'
                    : 'Restaura el acceso de los usuarios de esta ferretería.' }}
            </p>
            @unless ($empresa->activo)
                @if ($empresa->suspendido_motivo)
                    <p class="text-xs bg-red-50 text-red-600 rounded-lg px-3 py-2 mb-3">Motivo: {{ $empresa->suspendido_motivo }}</p>
                @endif
            @endunless
            <form method="POST" action="{{ route('superadmin.empresas.suspender', $empresa) }}" class="space-y-3"
                  onsubmit="return confirm('{{ $empresa->activo ? '¿Suspender esta ferretería y bloquear a sus usuarios?' : '¿Reactivar esta ferretería?' }}')">
                @csrf @method('PATCH')
                @if ($empresa->activo)
                    <input type="text" name="motivo" placeholder="Motivo (opcional: falta de pago...)"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-red-400 outline-none">
                    <button class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2.5 rounded-lg transition">Suspender</button>
                @else
                    <button class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2.5 rounded-lg transition">Reactivar</button>
                @endif
            </form>
        </div>
    </div>
</div>

<!-- Suscripción y facturación -->
@php
    $limU = $empresa->limiteUsuarios(); $usoU = $empresa->users_count;
    $limP = $empresa->limiteProductos(); $usoP = $empresa->productos_count;
    $pctU = $limU ? min(100, round($usoU / $limU * 100)) : 0;
    $pctP = $limP ? min(100, round($usoP / $limP * 100)) : 0;
@endphp
<div class="mt-5 grid grid-cols-1 lg:grid-cols-3 gap-5">
    <!-- Uso del plan -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="font-bold text-plat mb-4">Uso del plan <span class="capitalize text-plat-accent">{{ $empresa->plan }}</span></h3>
        <div class="space-y-4">
            <div>
                <div class="flex justify-between text-sm mb-1"><span class="text-slate-500">Usuarios</span>
                    <span class="font-bold text-plat">{{ $usoU }} / {{ $limU ?? '∞' }}</span></div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full {{ $limU && $usoU >= $limU ? 'bg-red-500' : 'bg-plat-accent' }}" style="width: {{ $limU ? $pctU : 12 }}%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-sm mb-1"><span class="text-slate-500">Productos activos</span>
                    <span class="font-bold text-plat">{{ $usoP }} / {{ $limP ?? '∞' }}</span></div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full {{ $limP && $usoP >= $limP ? 'bg-red-500' : 'bg-plat-accent' }}" style="width: {{ $limP ? $pctP : 12 }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Registrar pago de suscripción -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="font-bold text-plat mb-1">Registrar pago de suscripción</h3>
        <p class="text-xs text-slate-500 mb-3">Cobra el plan y extiende el vencimiento.</p>
        <form method="POST" action="{{ route('superadmin.facturacion.store', $empresa) }}" class="space-y-2.5">
            @csrf
            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Monto ($)</label>
                    <input type="number" name="monto" step="0.01" min="0.01" value="{{ number_format($empresa->precioPlan(), 2, '.', '') }}" required
                           class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm focus:border-plat-accent outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Meses</label>
                    <input type="number" name="meses" min="1" max="36" value="1" required
                           class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm focus:border-plat-accent outline-none">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Fecha</label>
                    <input type="date" name="fecha_pago" value="{{ now()->format('Y-m-d') }}" required
                           class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm focus:border-plat-accent outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Método</label>
                    <select name="metodo" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm focus:border-plat-accent outline-none">
                        <option value="transferencia">Transferencia</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="deposito">Depósito</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="yape">Yape</option>
                        <option value="plin">Plin</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
            </div>
            <input type="text" name="referencia" maxlength="60" placeholder="Referencia (opcional)"
                   class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm focus:border-plat-accent outline-none">
            <button class="w-full bg-plat-accent hover:opacity-90 text-white text-sm font-bold py-2 rounded-lg shadow shadow-plat-accent/30">Registrar pago</button>
        </form>
    </div>

    <!-- Historial de pagos -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 font-bold text-plat">Pagos de suscripción</div>
        <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
            @forelse ($pagosSuscripcion as $pago)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-plat">$ {{ number_format($pago->monto, 2) }} · {{ $pago->meses }} {{ $pago->meses == 1 ? 'mes' : 'meses' }}</p>
                        <p class="text-[11px] text-slate-400">{{ $pago->fecha_pago->format('d/m/Y') }} · {{ ucfirst($pago->metodo) }} · hasta {{ $pago->periodo_hasta->format('d/m/Y') }}</p>
                    </div>
                    <span class="text-[11px] font-bold px-2 py-1 rounded-full bg-slate-100 text-slate-600 capitalize">{{ $pago->plan }}</span>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-slate-400 text-sm">Sin pagos de suscripción registrados.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Últimas ventas -->
<div class="mt-5 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 font-bold text-plat">Últimas ventas</div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <tbody class="divide-y divide-slate-100">
                @forelse ($ultimasVentas as $v)
                    <tr>
                        <td class="px-5 py-3 font-semibold text-plat">{{ $v->numero }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $v->cliente->nombre ?? 'Cliente Varios' }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $v->fecha->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3 text-right font-bold text-plat">{{ $m }} {{ number_format($v->total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td class="px-5 py-8 text-center text-slate-400">Esta ferretería aún no tiene ventas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
