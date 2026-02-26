<x-layouts.vigia :title="'Bóveda'">
    <x-slot name="breadcrumb">
        <span class="text-gray-700 font-medium">Bóveda</span>
    </x-slot>

    <div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-[#1A428A]">Lista de activos</h1>

    <a href="{{ route('assets.create') }}"
        class="bg-[#1A428A] text-white px-4 py-2 rounded-md font-semibold hover:bg-[#15356d]">
        + Nuevo activo
    </a>
    </div>

        <form method="GET" action="{{ route('assets.index') }}" class="mt-6">
            <div class="bg-gray-50 border rounded-lg p-4 flex flex-col md:flex-row md:items-center gap-3">
                <div class="font-semibold text-gray-700 w-28">Filtros</div>

                <div class="flex-1">
                    <select name="status"
                            class="w-full md:w-72 rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm">
                        <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>Todos</option>
                        <option value="active" {{ ($status ?? 'all') === 'active' ? 'selected' : '' }}>Activos</option>
                        <option value="inactive" {{ ($status ?? 'all') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                    </select>
                </div>

                <button type="submit"
                        class="w-full md:w-44 bg-[#1A428A] text-white rounded-md py-2 font-semibold hover:bg-[#15356d] transition">
                    Filtrar
                </button>
            </div>
        </form>

        {{-- Tabla --}}
        <div class="mt-6 bg-white border rounded-lg shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-6 py-3 font-semibold">Nombre</th>
                        <th class="text-left px-6 py-3 font-semibold">Tipo</th>
                        <th class="text-left px-6 py-3 font-semibold">Responsable</th>
                        <th class="text-left px-6 py-3 font-semibold">Creado</th>
                        <th class="text-left px-6 py-3 font-semibold">Fecha creación</th>
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

        <div class="mt-6">
            {{ $assets->links() }}
        </div>
    </div>
</x-layouts.vigia>