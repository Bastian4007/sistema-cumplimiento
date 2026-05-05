<x-layouts.vigia title="Documentos">

    <x-slot name="breadcrumb">
        <span class="text-gray-700 font-medium">Documentos</span>
    </x-slot>

    @php $user = auth()->user(); @endphp

    {{-- HEADER --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-[#1A428A]">Documentos</h1>
    </div>

    {{-- FILTROS --}}
    <form method="GET" action="{{ route('documents.index') }}"
          class="mt-4 flex flex-wrap items-end gap-3">

        @if($user->hasGroupScope())
            <div class="min-w-[180px]">
                <label class="block text-xs text-gray-500 mb-1">Empresa</label>
                <select name="company_id" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">Todas</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}"
                            @selected((string) request('company_id', $selectedCompanyId) === (string) $company->id)>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="flex-1 min-w-[200px] max-w-sm">
            <label class="block text-xs text-gray-500 mb-1">Buscar por nombre</label>
            <input type="text"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="Nombre de carpeta..."
                   class="w-full rounded-md border-gray-300 text-sm">
        </div>

        <button type="submit"
                class="px-5 py-2 rounded-md bg-[#1A428A] text-white text-sm font-semibold hover:bg-[#15356d]">
            Filtrar
        </button>

        <a href="{{ route('documents.index') }}"
           class="px-5 py-2 rounded-md border border-gray-300 bg-white text-sm text-gray-700 font-semibold hover:bg-gray-50">
            Limpiar
        </a>
    </form>

    {{-- GRID DE CARPETAS --}}
    @if($folders->isEmpty())
        <div class="mt-8 rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                </svg>
            </div>
            <p class="text-sm text-gray-500">No hay carpetas para este filtro.</p>
        </div>
    @else
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($folders as $folder)
                <a href="{{ route('documents.folders.show', $folder) }}"
                   class="group flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm
                          transition hover:border-[#1A428A] hover:shadow-md">

                    {{-- Icono + nombre --}}
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center
                                    rounded-lg bg-blue-50 group-hover:bg-blue-100 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#1A428A]"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-800 leading-snug group-hover:text-[#1A428A] transition">
                                {{ $folder->name }}
                            </p>
                            @if($user->hasGroupScope() && $folder->company)
                                <p class="mt-1 text-xs font-medium text-indigo-600">
                                    {{ $folder->company->name }}
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Pie: conteos + flecha --}}
                    <div class="mt-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100
                                         px-2.5 py-0.5 text-xs font-medium text-gray-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                {{ $folder->categories_count ?? 0 }}
                                {{ \Illuminate\Support\Str::plural('categoría', $folder->categories_count ?? 0) }}
                            </span>
                        </div>

                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="h-4 w-4 text-gray-400 group-hover:text-[#1A428A] transition"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>

                </a>
            @endforeach
        </div>

        <p class="mt-4 text-xs text-gray-400">
            {{ $folders->count() }} {{ \Illuminate\Support\Str::plural('carpeta', $folders->count()) }} encontradas
        </p>
    @endif

</x-layouts.vigia>
