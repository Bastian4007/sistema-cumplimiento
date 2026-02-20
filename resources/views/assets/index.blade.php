<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Activos
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(auth()->user()->isOperative())
                <div class="mb-4">
                    <a href="{{ route('assets.create') }}"
                       class="px-4 py-2 bg-blue-600 text-white rounded">
                        Nuevo Activo
                    </a>
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6">

                @if($assets->count())
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">Nombre</th>
                                <th>Tipo</th>
                                <th>Responsable</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assets as $asset)
                                <tr class="border-b">
                                    <td class="py-2">{{ $asset->name }}</td>
                                    <td>{{ $asset->assetType->name ?? '-' }}</td>
                                    <td>{{ $asset->responsible->name ?? '-' }}</td>
                                    <td>
                                        <a href="{{ route('assets.show', $asset) }}"
                                           class="text-blue-600">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $assets->links() }}
                    </div>

                @else
                    <p>No hay activos registrados.</p>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
