{{-- resources/views/components/layouts/vigia.blade.php --}}
@props(['title' => null])

<!DOCTYPE html>
<html lang="es">
    <head>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ? $title.' · Vigia' : 'Vigia' }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="bg-gray-50 text-gray-900">
        {{-- Topbar --}}
        <header class="bg-[#1A428A] text-white">
            <div class="mx-auto max-w-[1360px] px-6 py-3 flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/vigia.svg') }}" alt="VIGIA" class="h-8 w-auto">
                </a>

                <div class="flex items-center gap-3">
                    <div class="text-sm opacity-90">{{ auth()->user()?->name }}</div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="bg-white text-[#1A428A] text-sm font-semibold px-4 py-2 rounded-md shadow-sm">
                            Cerrar Sesión
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Page --}}
        <div class="mx-auto max-w-[1360px] px-6 py-6">

            <div class="grid grid-cols-12 gap-8">
                {{-- Sidebar --}}
                <aside class="col-span-12 lg:col-span-3 xl:col-span-2">
                    <div class="bg-white rounded-xl shadow p-4">
                        <div class="flex items-center gap-2 text-sm font-semibold text-[#1A428A] mb-3">
                            <span>☰</span>
                            <span>Menú</span>
                        </div>

                        <nav class="space-y-1">
                            <a href="{{ route('dashboard') }}"
                            class="block px-3 py-2 rounded-md text-sm
                            {{ request()->routeIs('dashboard') ? 'bg-gray-100 font-semibold' : 'hover:bg-gray-50' }}">
                                Tablero
                            </a>

                            <a href="{{ route('assets.index') }}"
                            class="block px-3 py-2 rounded-md text-sm
                            {{ request()->routeIs('assets.*') ? 'bg-gray-100 font-semibold' : 'hover:bg-gray-50' }}">
                                Activos y Actividades
                            </a>
                        </nav>
                    </div>
                </aside>

                {{-- Contenido --}}
                <div class="col-span-12 lg:col-span-9 xl:col-span-10">

                    {{-- ✅ Breadcrumb ahora va AQUÍ --}}
                    @isset($breadcrumb)
                        <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
                            <span class="inline-flex items-center gap-2">
                                <span class="text-gray-400">⌂</span>
                                {{ $breadcrumb }}
                            </span>
                        </div>
                    @endisset

                    {{-- Flash messages --}}
                    @if(session('success'))
                        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
                            {{ session('success') }}
                        </div>
                    @endif

                    {{-- Main content --}}
                    <main>
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    </body>
</html>