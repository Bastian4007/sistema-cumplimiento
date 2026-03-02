<x-layouts.vigia :title="'Carpeta: ' . ($requirement->template?->name ?? $requirement->type)">
    <x-slot name="breadcrumb">
        <a href="{{ route('assets.index') }}" class="text-gray-600 hover:underline">Bóveda</a>
        <span class="text-gray-400">›</span>
        <a href="{{ route('assets.show', $asset) }}" class="text-gray-600 hover:underline">{{ $asset->name }}</a>
        <span class="text-gray-400">›</span>
        <span class="text-gray-700 font-medium">{{ $requirement->template?->name ?? $requirement->type }}</span>
    </x-slot>

    @php
        $assetInactive = ($asset->status ?? null) === \App\Models\Asset::STATUS_INACTIVE
            || (method_exists($asset, 'isInactive') && $asset->isInactive());

        $hasOfficialDocument = method_exists($requirement, 'documents')
            ? $requirement->documents()->exists()
            : false;
    @endphp

    <div class="bg-white rounded-xl shadow p-6">

        {{-- Header: Titulo + badges + acciones --}}
        <div class="flex items-start justify-between gap-6">
            <div>
                <h1 class="text-2xl font-bold text-[#1A428A]">
                    {{ $asset->name }} - {{ $requirement->template?->name ?? $requirement->type }}
                </h1>

                <div class="mt-2 flex items-center gap-2 flex-wrap">
                    <span class="text-xs px-3 py-1 rounded border
                        {{ $assetInactive ? 'bg-gray-100 text-gray-700 border-gray-300' : 'bg-green-50 text-green-700 border-green-200' }}">
                        {{ $assetInactive ? 'ASSET INACTIVO' : 'ASSET ACTIVO' }}
                    </span>

                    <span class="text-xs px-3 py-1 rounded border
                        {{ $hasOfficialDocument ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' }}">
                        {{ $hasOfficialDocument ? 'Documento oficial OK' : 'Falta documento oficial' }}
                    </span>
                </div>
            </div>

            {{-- Acciones superiores (como el mock) --}}
            <div class="flex items-center gap-2">
                <a href="{{ route('assets.requirements.history', [$asset, $requirement]) }}"
                   class="px-4 py-2 rounded-md border bg-white text-[#1A428A] border-[#1A428A] font-semibold hover:bg-blue-50">
                    Ver historial
                </a>

                <a href="{{ route('assets.requirements.documents.index', [$asset, $requirement]) }}"
                   class="px-4 py-2 rounded-md border bg-white text-[#1A428A] border-[#1A428A] font-semibold hover:bg-blue-50
                   {{ $assetInactive ? 'opacity-50 pointer-events-none' : '' }}">
                    Documentación oficial
                </a>

                {{-- Completar requirement --}}
                @if(auth()->user()->isOperative())
                    @if($requirement->status !== \App\Enums\RequirementStatus::COMPLETED)
                        @if($assetInactive || !$requirement->canBeCompleted())
                            <button type="button" disabled
                                class="px-4 py-2 rounded-md border bg-gray-100 text-gray-500 border-gray-300 font-semibold cursor-not-allowed">
                                Completar
                            </button>
                        @else
                            <form method="POST" action="{{ route('assets.requirements.complete', [$asset, $requirement]) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    class="px-4 py-2 rounded-md bg-[#1A428A] text-white font-semibold hover:bg-[#15356d]">
                                    Completar
                                </button>
                            </form>
                        @endif
                    @else
                        <span class="px-4 py-2 rounded-md border bg-gray-50 text-gray-700 border-gray-200 font-semibold">
                            Completado
                        </span>
                    @endif
                @endif
            </div>
        </div>

        {{-- Card resumen (como el mock) --}}
        <div class="mt-6 bg-gray-50 border rounded-xl p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                <div class="space-y-1">
                    <div><strong>Tipo:</strong> {{ $asset->assetType->name ?? '-' }}</div>
                    <div><strong>Vence:</strong> {{ $requirement->due_date?->format('Y-m-d') ?? 'Sin fecha' }}</div>
                    <div><strong>Riesgo:</strong> {{ $requirement->risk_level }}</div>
                    <div><strong>Estatus:</strong> {{ $requirement->computed_status }}</div>
                </div>

                <div class="space-y-1">
                    <div><strong>Progreso:</strong> {{ $requirement->progress }}%</div>
                    <div><strong>Tareas:</strong> {{ $requirement->tasks_done }}/{{ $requirement->tasks_total }}</div>
                    <div><strong>Recurrencia:</strong>
                        @if($requirement->isRecurrent())
                            {{ $requirement->recurrenceLabel() }}
                        @else
                            No recurrente
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Tareas --}}
        <div class="mt-8 bg-white border rounded-xl overflow-hidden">
            <div class="p-5 border-b flex items-center justify-between">
                <div class="font-semibold text-[#1A428A]">Tareas</div>

                @if(auth()->user()->isOperative())
                    <a href="{{ route('requirements.tasks.create', $requirement) }}"
                       class="px-4 py-2 rounded-md bg-[#1A428A] text-white font-semibold hover:bg-[#15356d]
                       {{ $assetInactive ? 'opacity-50 pointer-events-none' : '' }}">
                        Añadir tarea
                    </a>
                @endif
            </div>

            {{-- ✅ SCROLL INTERNO: solo el listado crece, no la página --}}
            <div class="p-5">
                <div class="max-h-[520px] overflow-y-auto pr-2 space-y-4
                    {{-- Si quieres scroll bonito, crea la clase custom-scroll en tu CSS y descomenta esto:
                    custom-scroll
                    --}}
                ">
                    @forelse($requirement->tasks as $task)
                        @php
                            $hasDocs = ($task->documents_count ?? 0) > 0;
                            $isCompleted = (bool) $task->completed_at;
                        @endphp

                        <div class="border rounded-xl p-4 bg-white">
                            <div class="font-semibold text-gray-900">{{ $task->title }}</div>

                            <div class="text-sm text-gray-600 mt-2 flex flex-wrap items-center gap-x-3 gap-y-2">
                                <span>Status:
                                    <span class="px-2 py-0.5 rounded border text-xs bg-gray-50">
                                        {{ $task->status->value }}
                                    </span>
                                </span>

                                <span class="text-gray-300">|</span>
                                <span>Vence: {{ $task->due_date?->format('Y-m-d') ?? '-' }}</span>

                                <span class="text-gray-300">|</span>
                                <span>Evidencias: {{ $task->documents_count ?? 0 }}</span>

                                <span class="text-xs px-2 py-0.5 rounded border bg-yellow-50">
                                    Requiere evidencia
                                </span>

                                @if($hasDocs)
                                    <span class="text-xs px-2 py-0.5 rounded border bg-green-50 text-green-700 border-green-200">
                                        Evidencia OK
                                    </span>
                                @else
                                    <span class="text-xs px-2 py-0.5 rounded border bg-red-50 text-red-700 border-red-200">
                                        Falta evidencia
                                    </span>
                                @endif
                            </div>

                            {{-- Botones de la tarea --}}
                            @if(auth()->user()->isOperative())
                                <div class="mt-4 pt-3 border-t flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('tasks.documents.index', $task) }}"
                                       class="px-4 py-2 rounded-md border font-semibold
                                       {{ $assetInactive ? 'opacity-50 pointer-events-none bg-gray-100 text-gray-500 border-gray-300' : 'bg-white text-[#1A428A] border-[#1A428A] hover:bg-blue-50' }}">
                                        {{ $hasDocs ? 'Ver evidencias (' . ($task->documents_count ?? 0) . ')' : 'Subir evidencia' }}
                                    </a>

                                    @if(!$isCompleted)
                                        <form method="POST" action="{{ route('requirements.tasks.complete', [$requirement, $task]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="px-4 py-2 rounded-md font-semibold
                                                {{ ($assetInactive || !$hasDocs) ? 'bg-gray-100 text-gray-500 border border-gray-300 cursor-not-allowed' : 'bg-[#1A428A] text-white hover:bg-[#15356d]' }}"
                                                {{ ($assetInactive || !$hasDocs) ? 'disabled' : '' }}>
                                                Completar
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('requirements.tasks.reopen', [$requirement, $task]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="px-4 py-2 rounded-md border bg-white text-[#1A428A] border-[#1A428A] font-semibold hover:bg-blue-50
                                                {{ $assetInactive ? 'opacity-50 pointer-events-none' : '' }}">
                                                Reabrir
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('requirements.tasks.edit', [$requirement, $task]) }}"
                                       class="px-4 py-2 rounded-md border bg-white text-[#1A428A] border-[#1A428A] font-semibold hover:bg-blue-50
                                       {{ $assetInactive ? 'opacity-50 pointer-events-none' : '' }}">
                                        Editar
                                    </a>

                                    <form method="POST" action="{{ route('requirements.tasks.destroy', [$requirement, $task]) }}"
                                          onsubmit="return confirm('¿Eliminar esta tarea?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="px-4 py-2 rounded-md bg-[#DB0000] text-white font-semibold hover:bg-red-700
                                            {{ $assetInactive ? 'opacity-50 pointer-events-none' : '' }}">
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
        </div>
    </div>
</x-layouts.vigia>