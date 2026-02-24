<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar tarea
        </h2>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto">
        <div class="bg-white shadow sm:rounded-lg p-6">
            <form method="POST" action="{{ route('requirements.tasks.update', [$requirement, $task]) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700">Título</label>
                    <input
                        name="title"
                        value="{{ old('title', $task->title) }}"
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
                    >{{ old('description', $task->description) }}</textarea>
                    @error('description') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Fecha límite</label>
                    <input
                        type="date"
                        name="due_date"
                        value="{{ old('due_date', optional($task->due_date)->format('Y-m-d')) }}"
                        class="mt-1 w-full border rounded px-3 py-2"
                    >
                    @error('due_date') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="requires_document" value="0">
                    <input
                        type="checkbox"
                        name="requires_document"
                        value="1"
                        class="rounded"
                        {{ old('requires_document', $task->requires_document) ? 'checked' : '' }}
                    >
                    <label class="text-sm text-gray-700">Requiere evidencia (documento)</label>
                    @error('requires_document') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div class="flex gap-3 pt-2">
                    <button class="px-4 py-2 bg-gray-900 text-white rounded hover:bg-gray-800">
                        Guardar cambios
                    </button>

                    <a
                        class="px-4 py-2 border rounded hover:bg-gray-50"
                        href="{{ route('assets.requirements.show', [$requirement->asset_id, $requirement->id]) }}"
                    >
                        Volver
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>