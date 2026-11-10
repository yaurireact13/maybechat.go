@extends('layouts.app')

@section('titulo', 'Usuarios y Roles')

@section('contenido')
@include('partials.limite_plan', ['recurso' => 'usuarios'])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-extrabold text-navy">Usuarios y Roles</h1>
        <p class="text-sm text-slate-500">{{ $usuarios->count() }} {{ $usuarios->count() == 1 ? 'usuario' : 'usuarios' }} en {{ auth()->user()->empresa->nombre }}</p>
    </div>
    <button onclick="abrirModal()"
            class="inline-flex items-center justify-center gap-2 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-4 py-2.5 rounded-lg shadow-lg shadow-teal-brand/30 transition w-full sm:w-fit">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Nuevo Usuario
    </button>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
    @foreach ($usuarios as $u)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 {{ !$u->activo ? 'opacity-60' : '' }}">
            <div class="flex items-start gap-3">
                <div class="w-11 h-11 rounded-full bg-lime-brand text-navy font-extrabold flex items-center justify-center text-lg shrink-0">
                    {{ strtoupper(substr($u->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-slate-700 truncate">{{ $u->name }} @if($u->id === auth()->id())<span class="text-[10px] text-teal-brand font-bold">(tú)</span>@endif</p>
                    <p class="text-xs text-slate-400 truncate">{{ $u->email }}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full capitalize
                            @if($u->rol === 'admin') bg-navy text-white
                            @elseif($u->rol === 'vendedor') bg-teal-brand/10 text-teal-dark
                            @elseif($u->rol === 'almacenero') bg-amber-100 text-amber-700
                            @else bg-slate-100 text-slate-600 @endif">
                            {{ $u->rol_nombre }}
                        </span>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full {{ $u->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">
                            {{ $u->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex gap-2 mt-4 pt-4 border-t border-slate-100">
                <button type="button"
                        data-registro="{{ json_encode($u->only(["id","name","email","rol"])) }}"
                                        onclick="abrirModal(JSON.parse(this.dataset.registro))"
                        class="flex-1 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 px-3 py-2 rounded-lg transition">
                    Editar
                </button>
                @if ($u->id !== auth()->id())
                    <form method="POST" action="{{ route('usuarios.alternar', $u) }}" class="flex-1"
                          onsubmit="return confirm('¿{{ $u->activo ? 'Desactivar' : 'Activar' }} a {{ $u->name }}?')">
                        @csrf @method('PATCH')
                        <button type="submit" class="w-full text-xs font-bold px-3 py-2 rounded-lg transition
                            {{ $u->activo ? 'text-red-600 bg-red-50 hover:bg-red-100' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                            {{ $u->activo ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach
</div>

<!-- Permisos por rol (generado desde config/permisos.php: lo que ves es lo que se aplica) -->
@php
    $roles = config('permisos.roles');
    $permisos = config('permisos.permisos');
@endphp
<section class="mt-6 bg-white rounded-xl border border-slate-200 p-5" aria-labelledby="tituloPermisos">
    <h3 id="tituloPermisos" class="font-bold text-navy text-sm">Permisos por rol</h3>
    <p class="text-xs text-slate-400 mt-0.5 mb-4">Lo que puede hacer cada usuario según el rol que le asignes.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs text-slate-500 mb-4">
        @foreach ($roles as $clave => $rol)
            <div class="bg-slate-50 rounded-lg p-3"><b class="text-navy block mb-1">{{ $rol['nombre'] }}</b>{{ $rol['descripcion'] }}</div>
        @endforeach
    </div>

    <div class="overflow-x-auto -mx-5 px-5">
        <table class="w-full text-xs min-w-[520px]">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-200">
                    <th scope="col" class="py-2 pr-3 font-bold">Módulo / acción</th>
                    @foreach ($roles as $clave => $rol)
                        <th scope="col" class="py-2 px-2 font-bold text-center">{{ $rol['nombre'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($permisos as $clave => [$etiqueta, $rolesConAcceso])
                    <tr>
                        <th scope="row" class="py-2 pr-3 font-semibold text-slate-600 text-left">{{ $etiqueta }}</th>
                        @foreach ($roles as $rolClave => $rol)
                            @php $tiene = $rolClave === 'admin' || in_array($rolClave, $rolesConAcceso, true); @endphp
                            <td class="py-2 px-2 text-center">
                                @if ($tiene)
                                    <span class="inline-flex w-5 h-5 items-center justify-center rounded-full bg-teal-brand/15 text-teal-dark font-bold" aria-label="Sí">✓</span>
                                @else
                                    <span class="text-slate-300" aria-label="No">—</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<!-- Modal -->
<div id="modalUsuario" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="cerrarModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <h2 id="tituloModal" class="text-lg font-extrabold text-navy mb-4">Nuevo Usuario</h2>
        <form method="POST" id="formUsuario" action="{{ route('usuarios.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" id="metodoForm" value="POST">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre completo <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="f_name" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Correo electrónico <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="f_email" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Rol <span class="text-red-500">*</span></label>
                    <select name="rol" id="f_rol" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                        @foreach (config('permisos.roles') as $clave => $rol)
                            <option value="{{ $clave }}" data-descripcion="{{ $rol['descripcion'] }}">{{ $rol['nombre'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Contraseña <span id="passReq" class="text-red-500">*</span></label>
                    <input type="password" name="password" id="f_password" minlength="6" placeholder="Mín. 6 caracteres"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                    <p id="passHint" class="hidden text-[10px] text-slate-400 mt-1">Déjala vacía para no cambiarla.</p>
                </div>
            </div>
            <p id="rolDescripcion" class="text-xs text-slate-500 bg-slate-50 rounded-lg px-3 py-2" aria-live="polite"></p>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="cerrarModal()"
                        class="flex-1 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-600 text-sm font-bold hover:bg-slate-50 transition">Cancelar</button>
                <button type="submit" class="flex-1 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-4 py-2.5 rounded-lg transition">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const URL_STORE = @json(route('usuarios.store'));
    const URL_UPDATE = @json(url('/usuarios')) + '/';

    function abrirModal(u = null) {
        document.getElementById('tituloModal').textContent = u ? 'Editar Usuario' : 'Nuevo Usuario';
        document.getElementById('metodoForm').value = u ? 'PUT' : 'POST';
        document.getElementById('formUsuario').action = u ? URL_UPDATE + u.id : URL_STORE;
        document.getElementById('f_name').value = u ? u.name : '';
        document.getElementById('f_email').value = u ? u.email : '';
        document.getElementById('f_rol').value = u ? u.rol : 'vendedor';
        mostrarDescripcionRol();
        const pass = document.getElementById('f_password');
        pass.value = '';
        pass.required = !u;
        document.getElementById('passReq').style.display = u ? 'none' : '';
        document.getElementById('passHint').classList.toggle('hidden', !u);
        document.getElementById('modalUsuario').classList.remove('hidden');
        document.getElementById('f_name').focus();
    }

    function mostrarDescripcionRol() {
        const sel = document.getElementById('f_rol');
        const opt = sel.options[sel.selectedIndex];
        document.getElementById('rolDescripcion').textContent = opt ? opt.dataset.descripcion : '';
    }
    document.getElementById('f_rol').addEventListener('change', mostrarDescripcionRol);

    function cerrarModal() { document.getElementById('modalUsuario').classList.add('hidden'); }
    @if ($errors->any()) abrirModal(); @endif
</script>
@endpush
