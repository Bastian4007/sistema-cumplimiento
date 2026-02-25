<x-app-layout>
    @php
        $assetInactive = ($asset->status ?? null) === 'inactive' || (method_exists($asset, 'isInactive') && $asset->isInactive());
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Carpeta: {{ $requirement->template?->name ?? $requirement->type }}
                </h2>

                {{-- Badge del asset (opcional pero útil) --}}
                <span class="text-xs px-3 py-1 rounded border
                    {{ $assetInactive
                        ? 'bg-gray-100 text-gray-700 border-gray-300'
                        : 'bg-green-50 text-green-700 border-green-200' }}">
                    {{ $assetInactive ? 'ASSET INACTIVO' : 'ASSET ACTIVO' }}
                </span>
            </div>

            {{-- Acciones --}}
            @if(auth()->user()->isOperative())
                @if($requirement->status !== \App\Enums\RequirementStatus::COMPLETED)

                    {{-- Si está inactivo: botón bloqueado con tooltip --}}
                    @if($assetInactive)
                        <div class="text-right">
                            <x-action-button
                                :disabled="true"
                                disabledText="Activo desactivado"
                                type="button"
                            >
                                Completar requirement
                            </x-action-button>
                            <div class="text-xs text-gray-500 mt-1">
                                Activa el activo para poder completar esta carpeta.
                            </div>
                        </div>

                    @else
                        {{-- Activo activo: tu lógica normal --}}
                        @if($requirement->canBeCompleted())
                            <form method="POST" action="{{ route('assets.requirements.complete', [$asset, $requirement]) }}">
                                @csrf
                                @method('PATCH')
                                <x-action-button>
                                    Completar requirement
                                </x-action-button>
                            </form>
                        @else
                            <div class="text-right">
                                <x-action-button
                                    :disabled="true"
                                    disabledText="Completa todas las tareas y sube evidencias"
                                    type="button"
                                >
                                    Completar requirement
                                </x-action-button>
                                <div class="text-xs text-gray-500 mt-1">
                                    Completa todas las tareas y sube las evidencias.
                                </div>
                            </div>
                        @endif
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

        {{-- Banner global si asset inactivo --}}
        @include('assets._inactive_banner', ['asset' => $asset])

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

            {{-- Flash error (por ejemplo 403 manejado con redirect back) --}}
            @if (session('error'))
                <div class="mt-4 text-sm px-3 py-2 rounded border bg-red-50 text-red-800">
                    {{ session('error') }}
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
                    <x-action-link
                    :href="route('requirements.tasks.create', $requirement)"
                    :disabled="$assetInactive"
                    disabledText="Activo desactivado"
                    variant="primary"
                    size="sm"
                    >
                    + Nueva tarea
                    </x-action-link>
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

                                @php
                                    $hasDocs = ($task->documents_count ?? 0) > 0;
                                    $isCompleted = (bool) $task->completed_at;
                                @endphp

                                {{-- Evidencia / Ver evidencias --}}
                                @if(!$isCompleted)
                                    @if(!$hasDocs)
                                        <x-action-link
                                        :href="route('tasks.documents.index', $task)"
                                        :disabled="$assetInactive"
                                        disabledText="Activo desactivado"
                                        variant="primary"
                                        size="sm"
                                        >
                                        Subir evidencia
                                        </x-action-link>
                                    @else
                                        <x-action-link
                                        :href="route('tasks.documents.index', $task)"
                                        :disabled="$assetInactive"
                                        disabledText="Activo desactivado"
                                        variant="outline"
                                        size="sm"
                                        >
                                        Ver evidencias ({{ $task->documents_count ?? 0 }})
                                        </x-action-link>
                                    @endif

                                    {{-- Completar --}}
                                    @if($hasDocs)
                                        <form method="POST" action="{{ route('requirements.tasks.complete', [$requirement, $task]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <x-action-button
                                            :disabled="$assetInactive"
                                            disabledText="Activo desactivado"
                                            size="sm"
                                            class="bg-green-600 text-white hover:bg-green-700"
                                        >
                                            Completar
                                        </x-action-button>
                                        </form>
                                    @else
                                        <x-action-button
                                            :disabled="true"
                                            disabledText="Sube evidencia primero"
                                            type="button"
                                        >
                                            Completar
                                        </x-action-button>
                                    @endif
                                @else
                                    <form method="POST" action="{{ route('requirements.tasks.reopen', [$requirement, $task]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <x-action-button
                                        :disabled="$assetInactive"
                                        disabledText="Activo desactivado"
                                        variant="outline"
                                        size="sm"
                                        >
                                        Reabrir
                                        </x-action-button>
                                    </form>
                                    <x-action-link
                                    :href="route('tasks.documents.index', $task)"
                                    :disabled="$assetInactive"
                                    disabledText="Activo desactivado"
                                    variant="outline"
                                    size="sm"
                                    >
                                    Ver evidencias ({{ $task->documents_count ?? 0 }})
                                    </x-action-link>
                                @endif
                                {{-- Editar --}}
                                <x-action-link
                                :href="route('requirements.tasks.edit', [$requirement, $task])"
                                :disabled="$assetInactive"
                                disabledText="Activo desactivado"
                                variant="outline"
                                size="sm"
                                >
                                Editar
                                </x-action-link>

                                {{-- Eliminar --}}
                                <form method="POST" action="{{ route('requirements.tasks.destroy', [$requirement, $task]) }}"
                                    onsubmit="return confirm('¿Eliminar esta tarea?')">
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