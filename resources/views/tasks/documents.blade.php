<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Evidencias: {{ $task->title }}
            </h2>
            <div class="text-sm text-gray-500">
                Activo: {{ $asset->name }} · Carpeta: {{ $requirement->template?->name ?? $requirement->type }}
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto space-y-6">

        @if(session('status'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded p-3">
                {{ session('status') }}
            </div>
        @endif

        {{-- Subir --}}
        @if(auth()->user()->isOperative())
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Subir documento</h3>

                <form method="POST" action="{{ route('tasks.documents.store', $task) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf

                    <input type="file" name="file" class="block w-full" required>
                    @error('file') <div class="text-sm text-red-600">{{ $message }}</div> @enderror

                    <button class="px-4 py-2 bg-gray-900 text-white rounded hover:bg-gray-800">
                        Subir
                    </button>
                </form>
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
                            <a href="{{ route('tasks.documents.preview', [$task, $doc])}}"
                            target="_blank"
                            class="px-3 py-1.5 text-sm border rounded hover:bg-gray-50">
                                Ver
                            </a>

                            <a href="{{ route('documents.download', $doc) }}"
                               class="px-3 py-1.5 text-sm border rounded hover:bg-gray-50">
                                Descargar
                            </a>

                            @if(auth()->user()->isOperative())
                                <form method="POST" action="{{ route('documents.destroy', $doc) }}"
                                      onsubmit="return confirm('¿Eliminar este documento?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="px-3 py-1.5 text-sm bg-red-600 text-white rounded hover:bg-red-700">
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