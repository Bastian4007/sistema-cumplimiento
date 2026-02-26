<x-app-layout>
    @php
        $assetInactive = ($asset->status ?? null) === 'inactive' || (method_exists($asset, 'isInactive') && $asset->isInactive());
    @endphp

    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Evidencias: {{ $task->title }}
                </h2>
                <div class="text-sm text-gray-500">
                    Activo: {{ $asset->name }} · Carpeta: {{ $requirement->template?->name ?? $requirement->type }}
                </div>
            </div>

            <span class="text-xs px-3 py-1 rounded border {{ $assetInactive ? 'bg-gray-100 text-gray-700 border-gray-300' : 'bg-green-50 text-green-700 border-green-200' }}">
                {{ $assetInactive ? 'ASSET INACTIVO' : 'ASSET ACTIVO' }}
            </span>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto space-y-6">

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded p-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 rounded p-3 text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- Subir --}}
        @if(auth()->user()->isOperative())
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Subir documento</h3>

                @if($assetInactive)
                    <div class="rounded border bg-gray-50 p-3 text-sm text-gray-700">
                        Este activo está desactivado. No puedes subir evidencias.
                    </div>
                @else
                    <form method="POST" action="{{ route('tasks.documents.store', $task) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf

                        <input type="file" name="file" class="block w-full" required>
                        @error('file') <div class="text-sm text-red-600">{{ $message }}</div> @enderror

                        <button class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded text-sm hover:bg-gray-800">
                            Subir
                        </button>
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
                        <div class="min-w-0">
                            <div class="font-medium text-gray-900 truncate">
                                {{ $doc->original_name ?? basename($doc->file_path) }}
                            </div>
                            <div class="text-sm text-gray-500">
                                Subido por: {{ $doc->uploader?->name ?? '—' }} · {{ $doc->created_at->format('Y-m-d H:i') }}
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <a
                                href="{{ route('tasks.documents.preview', [$task, $doc]) }}"
                                target="_blank"
                                class="inline-flex items-center px-3 py-1.5 text-sm border rounded hover:bg-gray-50"
                            >
                                Ver
                            </a>

                            <a
                                href="{{ route('documents.download', $doc) }}"
                                class="inline-flex items-center px-3 py-1.5 text-sm border rounded hover:bg-gray-50"
                            >
                                Descargar
                            </a>

                            @if(auth()->user()->isOperative())
                                <form method="POST" action="{{ route('documents.destroy', $doc) }}"
                                      onsubmit="return confirm('¿Eliminar este documento?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="inline-flex items-center px-3 py-1.5 text-sm bg-red-600 text-white rounded hover:bg-red-700">
                                        Eliminar
                                    </button>
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