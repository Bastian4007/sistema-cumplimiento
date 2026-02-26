<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Cumplimiento VIGIA' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 min-h-screen">
    {{-- Topbar --}}
    <header class="bg-[#1A428A] text-white">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/vigia.svg') }}" class="h-8" alt="VIGIA">
            </div>

            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="bg-white text-[#1A428A] px-5 py-2 rounded-md font-semibold shadow-sm hover:bg-gray-100 transition"
                    >
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 py-6 grid grid-cols-12 gap-6">
        {{-- Sidebar --}}
        <aside class="col-span-12 md:col-span-3 lg:col-span-2">
            <div class="bg-white rounded-xl shadow p-4">
                <div class="font-semibold text-[#1A428A] mb-3 flex items-center gap-2">
                    <span>☰</span> <span>Menú</span>
                </div>

                <nav class="space-y-2 text-sm">
                    <a href="{{ route('dashboard') }}"
                       class="block px-3 py-2 rounded-md hover:bg-gray-100 {{ request()->routeIs('dashboard') ? 'bg-gray-100 font-semibold' : '' }}">
                        Tablero
                    </a>

                    <a href="{{ route('assets.index') }}"
                    class="block px-3 py-2 rounded-md hover:bg-gray-100 {{ request()->routeIs('assets.*') ? 'bg-gray-100 font-semibold' : '' }}">
                        Bóveda
                    </a>
                </nav>
            </div>
        </aside>

        {{-- Content --}}
        <main class="col-span-12 md:col-span-9 lg:col-span-10">
            {{-- Breadcrumb / header opcional --}}
            @isset($breadcrumb)
                <div class="text-sm text-gray-600 mb-4 flex items-center gap-2">
                    <span>🏠</span> {!! $breadcrumb !!}
                </div>
            @endisset

            {{ $slot }}
        </main>
    </div>
</body>
</html>