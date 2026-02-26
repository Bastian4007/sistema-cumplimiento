<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Documento oficial: {{ $requirement->template?->name ?? $requirement->type }}
            </h2>
            <div class="text-sm text-gray-500">
                Activo: {{ $asset->name }}
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto space-y-6">

        @include('assets._inactive_banner', ['asset' => $asset])

        @if(session('status'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded p-3">
                {{ session('status') }}
            </div>
        @endif

        {{-- Subir --}}
        @if(auth()->user()->isOperative())
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Subir documento oficial</h3>

                @if($assetInactive)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-gray-700 text-sm">
                        Este activo está desactivado. Actívalo para subir documentación oficial.
                    </div>
                @else
                    <form method="POST"
                          action="{{ route('assets.requirements.documents.store', [$asset, $requirement]) }}"
                          enctype="multipart/form-data"
                          class="space-y-3">
                        @csrf

                        <input type="file" name="file" class="block w-full" required>
                        @error('file') <div class="text-sm text-red-600">{{ $message }}</div> @enderror

                        <x-action-button variant="primary" size="md">
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
                <div class="text-sm text-gray-500">{{ $requirement->documents->count() }} archivo(s)</div>
            </div>

            <div class="space-y-3">
                @forelse($requirement->documents as $doc)
                    <div class="border rounded-lg p-4 flex items-center justify-between gap-4">
                        <div>
                            <div class="font-medium text-gray-900">
                                {{ $doc->original_name ?? basename($doc->file_path) }}
                            </div>
                            <div class="text-sm text-gray-500">
                                Subido por: {{ $doc->uploader?->name ?? '—' }} · {{ $doc->created_at->format('Y-m-d H:i') }}
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <x-action-link
                                :href="route('assets.requirements.documents.preview', [$asset, $requirement, $doc])"
                                :disabled="$assetInactive"
                                disabledText="Activo desactivado"
                                variant="outline"
                                size="sm"
                                target="_blank"
                            >
                                Ver
                            </x-action-link>

                            <x-action-link
                                :href="route('assets.requirements.documents.download', [$asset, $requirement, $doc])"
                                :disabled="$assetInactive"
                                disabledText="Activo desactivado"
                                variant="outline"
                                size="sm"
                            >
                                Descargar
                            </x-action-link>

                            @if(auth()->user()->isOperative())
                                <form method="POST"
                                      action="{{ route('assets.requirements.documents.destroy', [$asset, $requirement, $doc]) }}"
                                      onsubmit="return confirm('¿Eliminar este documento?')">
                                    @csrf
                                    @method('DELETE')

                                    <x-action-button
                                        :disabled="$assetInactive"
                                        disabledText="Activo desactivado"
                                        variant="danger"
                                        size="sm"
                                    >
                                        Eliminar
                                    </x-action-button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">Aún no hay documentos oficiales.</div>
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