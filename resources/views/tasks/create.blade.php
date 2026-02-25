<x-app-layout>
    @php
        $asset = $requirement->asset;
        $assetInactive = ($asset->status ?? null) === 'inactive' || (method_exists($asset, 'isInactive') && $asset->isInactive());
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Nueva tarea
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Carpeta: {{ $requirement->template?->name ?? $requirement->type }}
                </p>
            </div>

            {{-- Badge rápido --}}
            <span class="text-xs px-3 py-1 rounded border
                {{ $assetInactive
                    ? 'bg-gray-100 text-gray-700 border-gray-300'
                    : 'bg-green-50 text-green-700 border-green-200' }}">
                {{ $assetInactive ? 'ASSET INACTIVO' : 'ASSET ACTIVO' }}
            </span>
        </div>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto space-y-4">

        {{-- Banner global si asset inactivo --}}
        @include('assets._inactive_banner', ['asset' => $asset])

        <div class="bg-white shadow sm:rounded-lg p-6">
            @if($assetInactive)
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-gray-700">
                    Este activo está desactivado. Actívalo para poder crear nuevas tareas.
                </div>

                <div class="mt-4">
                    <a
                        class="inline-flex items-center px-4 py-2 border rounded hover:bg-gray-50 text-sm"
                        href="{{ route('assets.requirements.show', [$asset, $requirement]) }}"
                    >
                        ← Volver a la carpeta
                    </a>
                </div>
            @else
                <form method="POST" action="{{ route('requirements.tasks.store', $requirement) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Título</label>
                        <input
                            name="title"
                            value="{{ old('title') }}"
                            class="mt-1 w-full border rounded px-3 py-2"
                            required
                            maxlength="160"
                        >
                        @error('title') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Descripción</label>
                        <textarea
                            name="description"
                            class="mt-1 w-full border rounded px-3 py-2"
                            rows="4"
                            maxlength="2000"
                        >{{ old('description') }}</textarea>
                        @error('description') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha límite</label>
                        <input
                            type="date"
                            name="due_date"
                            value="{{ old('due_date') }}"
                            class="mt-1 w-full border rounded px-3 py-2"
                        >
                        @error('due_date') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="text-sm text-gray-600 bg-gray-50 border rounded p-3">
                        Todas las tareas requieren evidencia obligatoria.
                    </div>

                    <div class="flex gap-3 pt-2">
                        <x-action-button class="bg-gray-900 hover:bg-gray-800">
                            Guardar
                        </x-action-button>
                        <x-action-link
                        :href="route('assets.requirements.show', [$asset, $requirement])"
                        variant="outline"
                        size="md"
                        >
                        Cancelar
                        </x-action-link>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>