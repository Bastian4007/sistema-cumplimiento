<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $asset->name }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto">
        <div class="bg-white shadow sm:rounded-lg p-6 space-y-3">

            <p><strong>Tipo:</strong> {{ $asset->assetType->name }}</p>
            <p><strong>Código:</strong> {{ $asset->code ?? '-' }}</p>
            <p><strong>Ubicación:</strong> {{ $asset->location ?? '-' }}</p>
            <p><strong>Responsable:</strong> {{ $asset->responsible->name ?? '-' }}</p>

            @if(auth()->user()->isOperative())
                <div class="mt-4 flex gap-4">
                    <a href="{{ route('assets.edit', $asset) }}"
                       class="px-4 py-2 bg-yellow-500 text-white rounded">
                        Editar
                    </a>

                    <form method="POST"
                          action="{{ route('assets.destroy', $asset) }}">
                        @csrf
                        @method('DELETE')
                        <button class="px-4 py-2 bg-red-600 text-white rounded">
                            Eliminar
                        </button>
                    </form>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
