<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Carpeta: {{ $requirement->template?->name ?? $requirement->type }}
            </h2>

            {{-- Acciones --}}
            @if(auth()->user()->isOperative())
                @if($requirement->status !== \App\Enums\RequirementStatus::COMPLETED)
                    @if($requirement->canBeCompleted())
                        <form method="POST" action="{{ route('assets.requirements.complete', [$asset, $requirement]) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="px-3 py-2 rounded bg-green-600 text-white text-sm hover:bg-green-700">
                                Completar requirement
                            </button>
                        </form>
                    @else
                        <div class="text-right">
                            <button type="button" disabled
                                class="px-3 py-2 rounded bg-gray-200 text-gray-500 text-sm cursor-not-allowed">
                                Completar requirement
                            </button>
                            <div class="text-xs text-gray-500 mt-1">
                                Completa todas las tareas y sube las evidencias.
                            </div>
                        </div>
                    @endif
                @else
                    <span class="text-sm px-3 py-2 rounded bg-gray-100 text-gray-700 border">
                        Completado
                    </span>
                @endif
            @endif
        </div>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto space-y-6">

        {{-- Resumen --}}
        <div class="bg-white shadow sm:rounded-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                <div class="space-y-1">
                    <div><strong>Activo:</strong> {{ $asset->name }}</div>
                    <div><strong>Vence:</strong> {{ $requirement->due_date?->format('Y-m-d') ?? 'Sin fecha' }}</div>
                    <div><strong>Riesgo:</strong> {{ $requirement->risk_level }}</div>
                    <div><strong>Estatus:</strong> {{ $requirement->computed_status }}</div>
                </div>

                <div class="space-y-1">
                    <div><strong>Progreso:</strong> {{ $requirement->progress }}%</div>
                    <div><strong>Tareas:</strong> {{ $requirement->tasks_done }}/{{ $requirement->tasks_total }}</div>

                    {{-- Recurrencia --}}
                    <div>
                        <strong>Recurrencia:</strong>
                        @if($requirement->isRecurrent())
                            <span class="inline-flex items-center px-2 py-0.5 rounded border text-xs bg-gray-50">
                                {{ $requirement->recurrenceLabel() }}
                            </span>
                            <span class="text-gray-500 ml-2">
                                Próximo: {{ $requirement->nextDueDate()?->toDateString() ?? '-' }}
                            </span>
                        @else
                            <span class="text-gray-500">No recurrente</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Flash success --}}
            @if (session('success'))
                <div class="mt-4 text-sm px-3 py-2 rounded border bg-green-50 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Errores --}}
            @if ($errors->any())
                <div class="mt-4 text-sm px-3 py-2 rounded border bg-red-50 text-red-800">
                    <div class="font-medium">Hubo errores:</div>
                    <ul class="list-disc ml-5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
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
                        
                        {{-- Parte superior: información --}}
                        <div>
                            <div class="font-medium text-gray-900">
                                {{ $task->title }}
                            </div>

                            <div class="text-sm text-gray-600 mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span>
                                    Status:
                                    <span class="px-2 py-0.5 rounded border text-xs bg-gray-50">
                                        {{ $task->status->value }}
                                    </span>
                                </span>

                                <span class="text-gray-400">|</span>

                                <span>Vence: {{ $task->due_date?->format('Y-m-d') ?? '-' }}</span>

                                <span class="text-gray-400">|</span>

                                <span>Evidencias: {{ $task->documents_count ?? 0 }}</span>

                                <span class="ml-2 text-xs px-2 py-0.5 rounded border bg-yellow-50">
                                    Requiere evidencia
                                </span>

                                @if(($task->documents_count ?? 0) > 0)
                                    <span class="text-xs px-2 py-0.5 rounded border bg-green-50 text-green-700 border-green-200">
                                        Evidencia OK
                                    </span>
                                @else
                                    <span class="text-xs px-2 py-0.5 rounded border bg-red-50 text-red-700 border-red-200">
                                        Falta evidencia
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Parte inferior: botones --}}
                        @if(auth()->user()->isOperative())
                            <div class="mt-4 pt-3 border-t flex flex-wrap items-center justify-end gap-2">

                                {{-- Evidencia --}}
                                @if(!$task->completed_at)
                                    @if(($task->documents_count ?? 0) === 0)
                                        <a href="{{ route('tasks.documents.index', $task) }}"
                                        class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                                            Subir evidencia
                                        </a>
                                    @else
                                        <a href="{{ route('tasks.documents.index', $task) }}"
                                        class="px-3 py-1.5 text-sm border rounded hover:bg-gray-50">
                                            Ver evidencias ({{ $task->documents_count }})
                                        </a>
                                    @endif

                                    {{-- Completar --}}
                                    @if(($task->documents_count ?? 0) > 0)
                                        <form method="POST" action="{{ route('requirements.tasks.complete', [$requirement, $task]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="px-3 py-1.5 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                                                Completar
                                            </button>
                                        </form>
                                    @else
                                        <button disabled
                                            class="px-3 py-1.5 text-sm bg-gray-200 text-gray-500 rounded cursor-not-allowed">
                                            Completar
                                        </button>
                                    @endif
                                @else
                                    <form method="POST" action="{{ route('requirements.tasks.reopen', [$requirement, $task]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="px-3 py-1.5 text-sm border rounded hover:bg-gray-50">
                                            Reabrir
                                        </button>
                                    </form>

                                    <a href="{{ route('tasks.documents.index', $task) }}"
                                    class="px-3 py-1.5 text-sm border rounded hover:bg-gray-50">
                                        Ver evidencias ({{ $task->documents_count ?? 0 }})
                                    </a>
                                @endif

                                {{-- Editar --}}
                                <a href="{{ route('requirements.tasks.edit', [$requirement, $task]) }}"
                                class="px-3 py-1.5 text-sm border rounded hover:bg-gray-50">
                                    Editar
                                </a>

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