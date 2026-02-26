<x-app-layout>
    @php
        $assetInactive = ($asset->status ?? null) === 'inactive' || (method_exists($asset, 'isInactive') && $asset->isInactive());
        // Evita N+1: mejor si lo pasas ya precargado desde controller, pero así funciona
        $hasOfficialDocument = method_exists($requirement, 'documents')
            ? $requirement->documents()->exists()
            : false;
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Carpeta: {{ $requirement->template?->name ?? $requirement->type }}
                </h2>

                {{-- Badge del asset --}}
                <span class="text-xs px-3 py-1 rounded border
                    {{ $assetInactive
                        ? 'bg-gray-100 text-gray-700 border-gray-300'
                        : 'bg-green-50 text-green-700 border-green-200' }}">
                    {{ $assetInactive ? 'ASSET INACTIVO' : 'ASSET ACTIVO' }}
                </span>

                {{-- Badge documento oficial --}}
                <span class="text-xs px-3 py-1 rounded border
                    {{ $hasOfficialDocument
                        ? 'bg-green-50 text-green-700 border-green-200'
                        : 'bg-red-50 text-red-700 border-red-200' }}">
                    {{ $hasOfficialDocument ? 'Documento oficial OK' : 'Falta documento oficial' }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <x-action-link
                    :href="route('assets.requirements.history', [$asset, $requirement])"
                    variant="outline"
                    size="sm"
                >
                    Ver historial
                </x-action-link>
                
                {{-- Documentación oficial --}}
                <x-action-link
                    :href="route('assets.requirements.documents.index', [$asset, $requirement])"
                    :disabled="$assetInactive"
                    disabledText="Activo desactivado"
                    variant="outline"
                    size="sm"
                >
                    Documentación oficial
                </x-action-link>

                {{-- Completar requirement --}}
                @if(auth()->user()->isOperative())
                    @if($requirement->status !== \App\Enums\RequirementStatus::COMPLETED)

                        @if($assetInactive)
                            <x-action-button
                                :disabled="true"
                                disabledText="Activo desactivado"
                                type="button"
                                size="sm"
                            >
                                Completar requirement
                            </x-action-button>

                        @else
                            @if($requirement->canBeCompleted())
                                <form method="POST" action="{{ route('assets.requirements.complete', [$asset, $requirement]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-action-button size="sm">
                                        Completar requirement
                                    </x-action-button>
                                </form>
                            @else
                                <x-action-button
                                    :disabled="true"
                                    disabledText="Completa todas las tareas y sube evidencias"
                                    type="button"
                                    size="sm"
                                >
                                    Completar requirement
                                </x-action-button>
                            @endif
                        @endif

                    @else
                        <span class="text-sm px-3 py-2 rounded bg-gray-100 text-gray-700 border">
                            Completado
                        </span>
                    @endif
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto space-y-6">

        {{-- Banner si asset inactivo --}}
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

            @if(session('success'))
                <div class="mt-4 text-sm px-3 py-2 rounded border bg-green-50 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mt-4 text-sm px-3 py-2 rounded border bg-red-50 text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
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

                        {{-- info --}}
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

                        {{-- botones --}}
                        @if(auth()->user()->isOperative())
                            <div class="mt-4 pt-3 border-t flex flex-wrap items-center justify-end gap-2">
                                @php
                                    $hasDocs = ($task->documents_count ?? 0) > 0;
                                    $isCompleted = (bool) $task->completed_at;
                                @endphp

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
                                        <x-action-button :disabled="true" type="button" size="sm" disabledText="Sube evidencia primero">
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

                                <x-action-link
                                    :href="route('requirements.tasks.edit', [$requirement, $task])"
                                    :disabled="$assetInactive"
                                    disabledText="Activo desactivado"
                                    variant="outline"
                                    size="sm"
                                >
                                    Editar
                                </x-action-link>

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

        {{-- Historial --}}
        <div class="bg-white shadow sm:rounded-lg p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Historial</h3>

            <div class="space-y-3">
                @forelse($auditLogs ?? [] as $log)
                    <div class="border rounded-lg p-4 bg-gray-50">

                        <div class="text-xs text-gray-500">
                            {{ $log->created_at->format('Y-m-d H:i') }}
                            —
                            <strong>{{ optional($log->actor)->name ?? 'Sistema' }}</strong>
                        </div>

                        <div class="mt-1 font-medium text-gray-800">
                            @switch($log->action)
                                @case('task.created')
                                    Creó una tarea
                                    @break

                                @case('task.updated')
                                    Actualizó una tarea
                                    @break

                                @case('task.completed')
                                    Completó una tarea
                                    @break

                                @case('task.reopened')
                                    Reabrió una tarea
                                    @break

                                @case('task.deleted')
                                    Eliminó una tarea
                                    @break

                                @default
                                    {{ $log->action }}
                            @endswitch
                        </div>

                        @if($log->meta)
                            <details class="mt-2 text-sm">
                                <summary class="cursor-pointer text-blue-600">
                                    Ver detalle
                                </summary>
                                <pre class="mt-2 text-xs bg-white p-3 rounded border overflow-auto">
        {{ json_encode($log->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                                </pre>
                            </details>
                        @endif
                    </div>
                @empty
                    <div class="text-sm text-gray-500">
                        Sin movimientos todavía.
                    </div>
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