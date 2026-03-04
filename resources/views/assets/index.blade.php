<x-layouts.vigia :title="'Bóveda'">
    <x-slot name="breadcrumb">
        <span class="text-gray-700 font-medium">Activos y Actividades</span>
    </x-slot>

    <div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-[#1A428A]">Lista de activos</h1>

    <a href="{{ route('assets.create') }}"
        class="bg-[#1A428A] text-white px-4 py-2 rounded-md font-semibold hover:bg-[#15356d]">
        + Nuevo activo
    </a>
    </div>

        <form method="GET"
            action="{{ route('assets.index') }}"
            class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">

            {{-- Estatus --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Estatus</label>
                <select name="status"
                        class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">Todos</option>
                    <option value="active" @selected(request('status')==='active')>Activos</option>
                    <option value="inactive" @selected(request('status')==='inactive')>Inactivos</option>
                </select>
            </div>

            {{-- Tipo --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Tipo</label>
                <select name="asset_type_id"
                        class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">Todos</option>

                    @foreach($assetTypes as $type)
                        <option value="{{ $type->id }}"
                            @selected((string)request('asset_type_id') === (string)$type->id)>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Ubicación --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Ubicación</label>
                <select name="location"
                        class="w-full rounded-md border-gray-300 text-sm">

                    <option value="">Todas</option>

                    @foreach($locations as $loc)
                        <option value="{{ $loc }}"
                            {{ request('location') === $loc ? 'selected' : '' }}>
                            {{ $loc }}
                        </option>
                    @endforeach

                </select>
            </div>

            {{-- Buscar --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Buscar</label>
                <input type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Nombre del activo..."
                    class="w-full rounded-md border-gray-300 text-sm">
            </div>

            {{-- Limpiar --}}
            <div>
                <a href="{{ route('assets.index') }}"
                class="w-full inline-flex justify-center px-4 py-2 rounded-md border bg-white text-gray-700 font-semibold hover:bg-gray-50">
                    Limpiar
                </a>
            </div>

            {{-- Filtrar --}}
            <div>
                <button type="submit"
                        class="w-full px-4 py-2 rounded-md bg-[#1A428A] text-white font-semibold hover:bg-[#15356d]">
                    Filtrar
                </button>
            </div>

        </form>

        {{-- Tabla --}}
        <div class="mt-6 bg-white border rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-6 py-3 font-semibold">Nombre</th>
                            <th class="text-left px-6 py-3 font-semibold">Tipo</th>
                            <th class="text-left px-6 py-3 font-semibold">Responsable</th>
                            <th class="text-left px-6 py-3 font-semibold">Creado</th>
                            <th class="text-left px-6 py-3 font-semibold">Ubicación</th>
                            <th class="text-left px-6 py-3 font-semibold">Fecha de Creación</th>
                            <th class="text-right px-6 py-3 font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assets as $asset)
                            <tr class="border-t">
                                <td class="px-6 py-3">
                                    <div class="font-semibold text-gray-800">{{ $asset->name }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ strtoupper($asset->status ?? '') }}
                                    </div>
                                </td>

                                <td class="px-6 py-3 text-gray-700">
                                    {{ $asset->type->name ?? '-' }}
                                </td>

                                <td class="px-6 py-3 text-gray-700">
                                    {{ $asset->responsibleUser->name ?? '-' }}
                                </td>

                                <td class="px-6 py-3 text-gray-700">
                                    {{ $asset->creator->name ?? 'Sistema' }}
                                </td>

                                <td class="px-6 py-3 text-gray-600 text-sm">
                                        {{ $asset->location ?? '-' }}
                                </td>

                                <td class="px-6 py-3 text-gray-600 text-sm">
                                    {{ $asset->created_at?->format('Y-m-d') }}
                                </td>

                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('assets.show', $asset) }}"
                                    class="text-blue-600 hover:underline font-semibold">
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t">
                                <td colspan="4" class="px-6 py-6 text-center text-gray-500">
                                    No hay activos para este filtro.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $assets->links() }}
        </div>
    </div>
</x-layouts.vigia>