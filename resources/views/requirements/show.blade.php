<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Carpeta: {{ $requirement->template?->name ?? $requirement->type }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto space-y-6">

        {{-- Resumen --}}
        <div class="bg-white shadow sm:rounded-lg p-6">
            <div class="text-sm text-gray-700 space-y-1">
                <div><strong>Activo:</strong> {{ $asset->name }}</div>
                <div><strong>Vence:</strong> {{ $requirement->due_date?->format('Y-m-d') ?? 'Sin fecha' }}</div>
                <div><strong>Riesgo:</strong> {{ $requirement->risk_level }}</div>
                <div><strong>Estatus:</strong> {{ $requirement->computed_status }}</div>
                <div><strong>Progreso:</strong> {{ $requirement->progress }}%</div>
                <div><strong>Tareas:</strong> {{ $requirement->tasks_done }}/{{ $requirement->tasks_total }}</div>
            </div>
        </div>

        {{-- Tareas --}}
        <div class="bg-white shadow sm:rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800">Tareas</h3>

                @if(auth()->user()->isOperative())
                    <a href="{{ route('requirements.tasks.create', $requirement) }}"
                       class="px-3 py-2 rounded bg-gray-900 text-white text-sm hover:bg-gray-800">
                        + Nueva tarea
                    </a>
                @endif
            </div>

            <div class="space-y-3">
                @forelse($requirement->tasks as $task)
                    <div class="border rounded-lg p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="font-medium text-gray-900">{{ $task->title }}</div>
                                <div class="text-sm text-gray-600 mt-1">
                                    Status:
                                    <span class="px-2 py-0.5 rounded border text-xs">
                                        {{ $task->status->value }}
                                    </span>

                                    <span class="mx-2">|</span>

                                    Vence: {{ $task->due_date?->format('Y-m-d') ?? '-' }}

                                    <span class="mx-2">|</span>

                                    Evidencias: {{ $task->documents_count ?? 0 }}

                                    @if($task->requires_document)
                                        <span class="ml-2 text-xs px-2 py-0.5 rounded border bg-yellow-50">
                                            Requiere evidencia
                                        </span>
                                    @endif
                                </div>
                                 <a class="text-sm underline text-gray-700"
                                href="{{ route('tasks.documents.index', $task) }}">
                                Evidencias ({{ $task->documents_count ?? 0 }})
                                </a>
                            </div>

                            @if(auth()->user()->isOperative())
                                <div class="flex items-center gap-2">
                                    {{-- Editar --}}
                                    <a href="{{ route('requirements.tasks.edit', [$requirement, $task]) }}"
                                       class="px-3 py-1.5 text-sm border rounded hover:bg-gray-50">
                                        Editar
                                    </a>

                                    {{-- Completar / Reabrir (opción 2) --}}
                                    @if($task->completed_at)
                                        <form method="POST" action="{{ route('requirements.tasks.reopen', [$requirement, $task]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="px-3 py-1.5 text-sm border rounded hover:bg-gray-50">
                                                Reabrir
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('requirements.tasks.complete', [$requirement, $task]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="px-3 py-1.5 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                                                Completar
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Eliminar --}}
                                    <form method="POST" action="{{ route('requirements.tasks.destroy', [$requirement, $task]) }}"
                                          onsubmit="return confirm('¿Eliminar esta tarea?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-3 py-1.5 text-sm bg-red-600 text-white rounded hover:bg-red-700">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">No hay tareas todavía.</div>
                @endforelse
            </div>
        </div>

        <div>
            <a href="{{ route('assets.show', $asset) }}" class="text-sm underline text-gray-700">
                ← Volver al activo
            </a>
        </div>

    </div>
</x-app-layout>