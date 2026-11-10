<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Plataforma') — FerreMax SaaS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { sans: ['Inter', 'sans-serif'] },
                colors: {
                    navy: { DEFAULT: '#16344F', dark: '#0F2538', light: '#1E4366' },
                    teal: { brand: '#2A9D8F', dark: '#21867A' },
                    plat: { DEFAULT: '#0B1120', light: '#131B2E', accent: '#6366F1' },
                }
            } }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
    </style>
</head>
<body class="font-sans bg-slate-100 text-slate-800">
<div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-plat text-slate-300 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-200">
        <div class="flex items-center gap-3 px-5 h-16 border-b border-white/10 shrink-0">
            <div class="w-9 h-9 rounded-lg bg-plat-accent flex items-center justify-center shadow">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" /></svg>
            </div>
            <div>
                <p class="font-extrabold text-white text-lg leading-none">Ferre<span class="text-plat-accent">Max</span></p>
                <p class="text-[10px] text-slate-400 mt-0.5 uppercase tracking-wider">Panel de plataforma</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            @php
                $item = 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-white/5 hover:text-white transition';
                $activo = 'bg-plat-accent text-white shadow-lg shadow-plat-accent/20 hover:bg-plat-accent';
            @endphp
            <a href="{{ route('superadmin.dashboard') }}" class="{{ $item }} {{ request()->routeIs('superadmin.dashboard') ? $activo : '' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25a2.25 2.25 0 01-2.25-2.25v-2.25z" /></svg>
                Dashboard
            </a>
            <a href="{{ route('superadmin.empresas.index') }}" class="{{ $item }} {{ request()->routeIs('superadmin.empresas.*') ? $activo : '' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                Ferreterías
            </a>
            <a href="{{ route('superadmin.usuarios.index') }}" class="{{ $item }} {{ request()->routeIs('superadmin.usuarios.*') ? $activo : '' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                Usuarios
            </a>
            <a href="{{ route('superadmin.facturacion.index') }}" class="{{ $item }} {{ request()->routeIs('superadmin.facturacion.*') ? $activo : '' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                Facturación
            </a>
            <a href="{{ route('superadmin.planes') }}" class="{{ $item }} {{ request()->routeIs('superadmin.planes') ? $activo : '' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                Planes y precios
            </a>
        </nav>

        <div class="border-t border-white/10 p-4 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-plat-accent text-white font-bold flex items-center justify-center text-sm">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-plat-accent font-bold uppercase tracking-wide">Super Admin</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Cerrar sesión" class="p-2 rounded-lg hover:bg-white/5 text-slate-400 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div id="overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <div class="flex-1 lg:ml-64 flex flex-col min-h-screen">
        <header class="h-16 bg-white border-b border-slate-200 flex items-center gap-3 px-4 sm:px-6 sticky top-0 z-20">
            <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg hover:bg-slate-100">
                <svg class="w-6 h-6 text-plat" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
            </button>
            <h1 class="font-bold text-plat">@yield('titulo', 'Plataforma')</h1>
            <span class="ml-auto hidden sm:inline-flex items-center gap-1.5 text-xs font-bold bg-plat-accent/10 text-plat-accent px-3 py-1.5 rounded-full">
                <span class="w-2 h-2 rounded-full bg-plat-accent"></span> Modo plataforma
            </span>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            @if (session('ok'))
                <div class="mb-4 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ session('ok') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 text-sm font-semibold rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                    </ul>
                </div>
            @endif
            @yield('contenido')
        </main>

        <footer class="px-6 py-4 text-xs text-slate-400 text-center">
            FerreMax SaaS · Panel de plataforma © {{ date('Y') }}
        </footer>
    </div>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    function toggleSidebar() {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }
    sidebar.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
        if (window.innerWidth < 1024 && !sidebar.classList.contains('-translate-x-full')) toggleSidebar();
    }));
</script>
@stack('scripts')
</body>
</html>
