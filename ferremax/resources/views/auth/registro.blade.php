<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        navy: { DEFAULT: '#16344F', dark: '#0F2538', light: '#1E4366' },
                        teal: { brand: '#2A9D8F', dark: '#21867A' },
                        lime: { brand: '#A8D582' },
                    }
                }
            }
        }
    </script>
</head>
<body class="font-sans bg-navy-dark min-h-screen flex items-center justify-center p-4"
      style="background-image: radial-gradient(circle at 20% 20%, #1E4366 0%, #0F2538 60%);">

    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-2xl overflow-hidden grid md:grid-cols-5">

        <!-- Branding -->
        <div class="hidden md:flex md:col-span-2 flex-col justify-between p-10 bg-navy text-white relative overflow-hidden">
            <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-teal-brand opacity-20"></div>
            <div class="absolute -left-10 -bottom-10 w-48 h-48 rounded-full bg-lime-brand opacity-10"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-teal-brand flex items-center justify-center shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-extrabold tracking-tight">Ferre<span class="text-teal-brand">Max</span></h1>
                        <p class="text-xs text-slate-300">Sistema de Gestión para Ferreterías</p>
                    </div>
                </div>
                <h2 class="mt-10 text-3xl font-bold leading-snug">Crea tu cuenta<br>en 1 minuto.</h2>
                <p class="mt-4 text-slate-300 text-sm leading-relaxed">Tu ferretería tendrá su propio espacio aislado y seguro, con catálogos iniciales listos para empezar a vender.</p>
            </div>
            <ul class="space-y-3 text-sm text-slate-200 relative z-10">
                <li class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-teal-brand flex items-center justify-center text-xs">✓</span> POS, inventario, compras y reportes</li>
                <li class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-teal-brand flex items-center justify-center text-xs">✓</span> Usuarios ilimitados con roles</li>
                <li class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-teal-brand flex items-center justify-center text-xs">✓</span> Plan Básico gratuito para empezar</li>
            </ul>
        </div>

        <!-- Formulario -->
        <div class="md:col-span-3 p-8 sm:p-10">
            <div class="md:hidden flex items-center gap-2 mb-6">
                <div class="w-10 h-10 rounded-lg bg-teal-brand flex items-center justify-center text-white font-bold">F</div>
                <h1 class="text-xl font-extrabold text-navy">Ferre<span class="text-teal-brand">Max</span></h1>
            </div>

            <h2 class="text-2xl font-bold text-navy">Registra tu ferretería</h2>
            <p class="text-sm text-slate-500 mt-1">Completa los datos y empieza a usar el sistema de inmediato.</p>

            @if ($errors->any())
                <div class="mt-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                    <ul class="list-disc ml-4 space-y-0.5">
                        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('registro.store') }}" class="mt-6 space-y-5">
                @csrf

                <!-- Empresa -->
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-teal-brand mb-3">1 · Tu ferretería</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre comercial <span class="text-red-500">*</span></label>
                            <input type="text" name="empresa_nombre" value="{{ old('empresa_nombre') }}" required placeholder="Ferretería San Martín"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">RUC</label>
                            <input type="text" name="empresa_ruc" value="{{ old('empresa_ruc') }}" placeholder="20123456789"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Teléfono</label>
                            <input type="text" name="empresa_telefono" value="{{ old('empresa_telefono') }}"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Dirección</label>
                            <input type="text" name="empresa_direccion" value="{{ old('empresa_direccion') }}"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Moneda <span class="text-red-500">*</span></label>
                            <select name="moneda" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                                <option value="S/" @selected(old('moneda', 'S/') === 'S/')>S/ — Sol peruano</option>
                                <option value="$" @selected(old('moneda') === '$')>$ — Dólar / Peso</option>
                                <option value="€" @selected(old('moneda') === '€')>€ — Euro</option>
                                <option value="Bs" @selected(old('moneda') === 'Bs')>Bs — Boliviano</option>
                                <option value="Q" @selected(old('moneda') === 'Q')>Q — Quetzal</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Impuesto (%) <span class="text-red-500">*</span></label>
                            <input type="number" name="impuesto" value="{{ old('impuesto', 18) }}" required step="0.01" min="0" max="100"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                        </div>
                    </div>
                </div>

                <!-- Cuenta -->
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-teal-brand mb-3">2 · Tu cuenta de administrador</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Tu nombre <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Correo electrónico <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Contraseña <span class="text-red-500">*</span></label>
                            <input type="password" name="password" required minlength="6" placeholder="Mín. 6 caracteres"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Confirmar contraseña <span class="text-red-500">*</span></label>
                            <input type="password" name="password_confirmation" required minlength="6"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                        </div>
                    </div>
                </div>

                <label class="flex items-start gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="acepta" value="1" class="mt-0.5 rounded border-slate-300 text-teal-brand focus:ring-teal-brand" {{ old('acepta') ? 'checked' : '' }}>
                    <span>Acepto los términos de servicio y la política de privacidad de FerreMax.</span>
                </label>

                <button type="submit"
                        class="w-full bg-teal-brand hover:bg-teal-dark text-white font-bold py-3 rounded-lg shadow-lg shadow-teal-brand/30 transition">
                    Crear mi cuenta gratis
                </button>

                <p class="text-center text-sm text-slate-500">
                    ¿Ya tienes cuenta?
                    <a href="{{ route('login') }}" class="font-bold text-teal-brand hover:text-teal-dark">Inicia sesión</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>
