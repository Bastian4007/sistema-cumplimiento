<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $asset->name }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto">
        <div class="bg-white shadow sm:rounded-lg p-6 space-y-3">

            <p><strong>Tipo:</strong> {{ $asset->assetType->name }}</p>
            <p><strong>Código:</strong> {{ $asset->code ?? '-' }}</p>
            <p><strong>Ubicación:</strong> {{ $asset->location ?? '-' }}</p>
            <p><strong>Responsable:</strong> {{ $asset->responsible->name ?? '-' }}</p>

            @if(auth()->user()->isOperative())
                <div class="mt-4 flex gap-4">
                    <a href="{{ route('assets.edit', $asset) }}"
                       class="px-4 py-2 bg-yellow-500 text-white rounded">
                        Editar
                    </a>

                    <form method="POST"
                          action="{{ route('assets.destroy', $asset) }}">
                        @csrf
                        @method('DELETE')
                        <button class="px-4 py-2 bg-red-600 text-white rounded">
                            Eliminar
                        </button>
                    </form>
                </div>
            @endif

        </div>
    </div>
    
    <div class="mt-8">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Obligaciones / Requerimientos</h3>

            @if(auth()->user()->isOperative())
                <a href="#"
                class="px-3 py-2 rounded bg-gray-900 text-white text-sm hover:bg-gray-800">
                    + Nueva carpeta
                </a>
            @endif
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($asset->requirements as $req)
                @php
                    $status = $req->computed_status;
                    $risk = $req->risk_level;
                    $progress = $req->progress;
                @endphp

                <div class="rounded-xl border bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-medium text-gray-900">
                                {{ $req->template?->name ?? $req->type }}
                            </div>
                            <div class="text-sm text-gray-500">
                                Vence: {{ $req->due_date?->format('Y-m-d') ?? 'Sin fecha' }}
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-2">
                            {{-- Status --}}
                            <span class="text-xs px-2 py-1 rounded-full border
                                @if($status === 'completed') bg-green-50 border-green-200 text-green-700
                                @elseif($status === 'expired') bg-red-50 border-red-200 text-red-700
                                @elseif($status === 'in_progress') bg-blue-50 border-blue-200 text-blue-700
                                @else bg-gray-50 border-gray-200 text-gray-700
                                @endif
                            ">
                                {{ strtoupper($status) }}
                            </span>

                            {{-- Risk --}}
                            <span class="text-xs px-2 py-1 rounded-full border
                                @if($risk === 'danger' || $risk === 'expired') bg-red-50 border-red-200 text-red-700
                                @elseif($risk === 'warning') bg-yellow-50 border-yellow-200 text-yellow-700
                                @else bg-gray-50 border-gray-200 text-gray-700
                                @endif
                            ">
                                RIESGO: {{ strtoupper($risk) }}
                            </span>
                        </div>
                    </div>

                    {{-- Progreso --}}
                    <div class="mt-4">
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>Progreso</span>
                            <span>{{ $progress }}%</span>
                        </div>
                        <div class="mt-1 h-2 w-full rounded bg-gray-100">
                            <div class="h-2 rounded bg-gray-900" style="width: {{ $progress }}%"></div>
                        </div>

                        <div class="mt-2 text-xs text-gray-500">
                            Tareas: {{ $req->tasks_done ?? 0 }}/{{ $req->tasks_total ?? 0 }}
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <a class="text-sm text-gray-900 underline"
                        href="{{ route('assets.requirements.show', [$asset, $req]) }}">
                            Abrir carpeta →
                        </a>

                        {{-- Opcional: puedes ocultar "ver tareas" porque carpeta ya lista tareas --}}
                        <a class="text-sm text-gray-700 underline"
                        href="{{ route('requirements.tasks.create', $req) }}">
                            + Nueva tarea
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-sm text-gray-500">
                    Este activo aún no tiene requerimientos.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
