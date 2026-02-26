<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Activos
            </h2>

            @if(auth()->user()->isOperative())
                <a href="{{ route('assets.create') }}"
                   class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                    Nuevo Activo
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Filtros --}}
            <div class="flex items-center gap-3">

                <a href="{{ route('assets.index', ['status' => 'active']) }}"
                   class="px-3 py-1.5 text-sm rounded border
                   {{ $status === 'active' ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                    Activos
                </a>

                <a href="{{ route('assets.index', ['status' => 'inactive']) }}"
                   class="px-3 py-1.5 text-sm rounded border
                   {{ $status === 'inactive' ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                    Inactivos
                </a>

                <a href="{{ route('assets.index', ['status' => 'all']) }}"
                   class="px-3 py-1.5 text-sm rounded border
                   {{ $status === 'all' ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                    Todos
                </a>

            </div>

            {{-- Tabla --}}
            <div class="bg-white shadow sm:rounded-lg p-6">

                @if($assets->count())

                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b text-gray-600">
                                <th class="py-3">Nombre</th>
                                <th>Tipo</th>
                                <th>Responsable</th>
                                <th>Creado</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y">

                            @foreach($assets as $asset)
                                <tr class="{{ $asset->isInactive() ? 'bg-gray-50 opacity-60' : 'hover:bg-gray-50' }}">

                                    {{-- Nombre --}}
                                    <td class="py-3">
                                        <div class="flex items-center gap-2">
                                            <span>{{ $asset->name }}</span>

                                            @if($asset->isInactive())
                                                <span class="text-xs px-2 py-0.5 rounded bg-gray-200 text-gray-700">
                                                    INACTIVO
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Tipo --}}
                                    <td>
                                        {{ $asset->assetType->name ?? '-' }}
                                    </td>

                                    {{-- Responsable --}}
                                    <td>
                                        {{ $asset->responsible->name ?? '-' }}
                                    </td>

                                    {{-- Fecha de creación --}}
                                    <td>
                                        <div class="text-gray-900">
                                            {{ $asset->created_at?->format('Y-m-d') }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $asset->created_at?->diffForHumans() }}
                                        </div>
                                    </td>

                                    {{-- Acciones --}}
                                    <td class="text-right">
                                        <a href="{{ route('assets.show', $asset) }}"
                                           class="text-blue-600 hover:underline">
                                            Ver
                                        </a>
                                    </td>

                                </tr>
                            @endforeach

                        </tbody>
                    </table>

                    <div class="mt-6">
                        {{ $assets->links() }}
                    </div>

                @else
                    <p class="text-gray-500">No hay activos registrados.</p>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>