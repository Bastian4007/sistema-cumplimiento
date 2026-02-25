<x-app-layout>
    @php
        $assetInactive = ($asset->status ?? null) === 'inactive' || (method_exists($asset, 'isInactive') && $asset->isInactive());
    @endphp

    <x-slot name="header">
        <div class="space-y-1">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Evidencias: {{ $task->title }}
                    </h2>
                    <div class="text-sm text-gray-500">
                        Activo: {{ $asset->name }} · Carpeta: {{ $requirement->template?->name ?? $requirement->type }}
                    </div>
                </div>

                {{-- Badge rápido --}}
                <span class="text-xs px-3 py-1 rounded border
                    {{ $assetInactive
                        ? 'bg-gray-100 text-gray-700 border-gray-300'
                        : 'bg-green-50 text-green-700 border-green-200' }}">
                    {{ $assetInactive ? 'ASSET INACTIVO' : 'ASSET ACTIVO' }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto space-y-6">

        {{-- Banner global si asset inactivo --}}
        @include('assets._inactive_banner', ['asset' => $asset])

        @if(session('status'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded p-3">
                {{ session('status') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 rounded p-3">
                {{ session('error') }}
            </div>
        @endif

        {{-- Subir --}}
        @if(auth()->user()->isOperative())
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Subir documento</h3>

                @if($assetInactive)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-gray-700 text-sm">
                        Este activo está desactivado. Actívalo para subir evidencias.
                    </div>
                @else
                    <form method="POST"
                          action="{{ route('tasks.documents.store', $task) }}"
                          enctype="multipart/form-data"
                          class="space-y-3">
                        @csrf

                        <input type="file" name="file" class="block w-full" required>
                        @error('file') <div class="text-sm text-red-600">{{ $message }}</div> @enderror

                        <x-action-button class="bg-gray-900 hover:bg-gray-800">
                            Subir
                        </x-action-button>
                    </form>
                @endif
            </div>
        @endif

        {{-- Lista --}}
        <div class="bg-white shadow sm:rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800">Documentos</h3>
                <div class="text-sm text-gray-500">{{ $task->documents->count() }} archivo(s)</div>
            </div>

            <div class="space-y-3">
                @forelse($task->documents as $doc)
                    <div class="border rounded-lg p-4 flex items-center justify-between gap-4">
                        <div>
                            <div class="font-medium text-gray-900">
                                {{ basename($doc->file_path) }}
                            </div>
                            <div class="text-sm text-gray-500">
                                Subido por: {{ $doc->uploader?->name ?? '—' }} · {{ $doc->created_at->format('Y-m-d H:i') }}
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            {{-- Ver (preview) --}}
                            <x-action-link
                                :href="route('tasks.documents.preview', [$task, $doc])"
                                :disabled="$assetInactive"
                                disabledText="Activo desactivado"
                                class="bg-white text-gray-800 border border-gray-300 hover:bg-gray-50"
                                target="_blank"
                            >
                                Ver
                            </x-action-link>

                            {{-- Descargar --}}
                            <x-action-link
                                :href="route('documents.download', $doc)"
                                :disabled="$assetInactive"
                                disabledText="Activo desactivado"
                                class="bg-white text-gray-800 border border-gray-300 hover:bg-gray-50"
                            >
                                Descargar
                            </x-action-link>

                            {{-- Eliminar --}}
                            @if(auth()->user()->isOperative())
                                <form method="POST" action="{{ route('documents.destroy', $doc) }}"
                                      onsubmit="return confirm('¿Eliminar este documento?')">
                                    @csrf
                                    @method('DELETE')

                                    <x-action-button
                                        :disabled="$assetInactive"
                                        disabledText="Activo desactivado"
                                        class="bg-red-600 hover:bg-red-700"
                                    >
                                        Eliminar
                                    </x-action-button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">Aún no hay documentos.</div>
                @endforelse
            </div>
        </div>

        <div>
            <a class="text-sm underline text-gray-700"
               href="{{ route('assets.requirements.show', [$asset, $requirement]) }}">
                ← Volver a la carpeta
            </a>
        </div>

    </div>
</x-app-layout>