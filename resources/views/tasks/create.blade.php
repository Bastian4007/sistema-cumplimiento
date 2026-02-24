<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nueva tarea
        </h2>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto">
        <div class="bg-white shadow sm:rounded-lg p-6">
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
                    <button class="px-4 py-2 bg-gray-900 text-white rounded hover:bg-gray-800">
                        Guardar
                    </button>

                    <a
                        class="px-4 py-2 border rounded hover:bg-gray-50"
                        href="{{ route('assets.requirements.show', [$requirement->asset_id, $requirement->id]) }}"
                    >
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>