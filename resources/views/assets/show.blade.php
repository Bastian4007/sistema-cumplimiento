<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $asset->name }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto space-y-6">

        {{-- Información del activo --}}
        <div class="bg-white shadow sm:rounded-lg p-6">
            <div class="grid grid-cols-2 gap-6 text-sm text-gray-700">

                <div>
                    <div><strong>Tipo:</strong> {{ $asset->assetType->name }}</div>
                    <div><strong>Código:</strong> {{ $asset->code ?? '-' }}</div>
                    <div><strong>Ubicación:</strong> {{ $asset->location ?? '-' }}</div>
                    <div><strong>Responsable:</strong> {{ $asset->responsible->name ?? '-' }}</div>
                </div>

                @if(auth()->user()->isOperative())
                    <div class="flex items-start justify-end gap-4">
                        <a href="{{ route('assets.edit', $asset) }}"
                           class="px-4 py-2 bg-yellow-500 text-white rounded text-sm hover:bg-yellow-600">
                            Editar
                        </a>

                        <form method="POST"
                              action="{{ route('assets.destroy', $asset) }}"
                              onsubmit="return confirm('¿Eliminar este activo?')">
                            @csrf
                            @method('DELETE')
                            <button class="px-4 py-2 bg-red-600 text-white rounded text-sm hover:bg-red-700">
                                Eliminar
                            </button>
                        </form>
                    </div>
                @endif

            </div>
        </div>


        {{-- Obligaciones / Requerimientos --}}
        <div class="bg-white shadow sm:rounded-lg p-6">

            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800">
                    Obligaciones / Requerimientos
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-gray-600 border-b">
                        <tr>
                            <th class="py-3 pr-4">Carpeta</th>
                            <th class="py-3 pr-4">Vence</th>
                            <th class="py-3 pr-4">Riesgo</th>
                            <th class="py-3 pr-4">Estatus</th>
                            <th class="py-3 pr-4">Progreso</th>
                            <th class="py-3 pr-4">Tareas</th>
                            <th class="py-3 pr-4 text-right">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        @forelse($asset->requirements as $req)
                            <tr class="hover:bg-gray-50">

                                {{-- Nombre --}}
                                <td class="py-3 pr-4">
                                    <div class="font-medium text-gray-900">
                                        {{ $req->template?->name ?? $req->type }}
                                    </div>

                                    @if($req->isRecurrent())
                                        <div class="text-xs text-gray-500">
                                            Recurrencia: {{ $req->recurrenceLabel() }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Due Date --}}
                                <td class="py-3 pr-4">
                                    {{ $req->due_date?->format('Y-m-d') ?? '-' }}
                                </td>

                                {{-- Riesgo --}}
                                <td class="py-3 pr-4">
                                    <span class="px-2 py-0.5 rounded border text-xs
                                        @if($req->risk_level === 'danger')
                                            bg-red-50 text-red-700 border-red-200
                                        @elseif($req->risk_level === 'warning')
                                            bg-yellow-50 text-yellow-700 border-yellow-200
                                        @elseif($req->risk_level === 'expired')
                                            bg-gray-100 text-gray-700 border-gray-200
                                        @else
                                            bg-green-50 text-green-700 border-green-200
                                        @endif
                                    ">
                                        {{ strtoupper($req->risk_level) }}
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td class="py-3 pr-4">
                                    <span class="px-2 py-0.5 rounded border text-xs bg-gray-50">
                                        {{ strtoupper($req->computed_status) }}
                                    </span>
                                </td>

                                {{-- Progreso --}}
                                <td class="py-3 pr-4 w-40">
                                    <div class="flex items-center gap-2">
                                        <div class="w-full bg-gray-200 rounded h-2">
                                            <div class="bg-gray-900 h-2 rounded"
                                                 style="width: {{ $req->progress }}%">
                                            </div>
                                        </div>
                                        <span class="text-xs text-gray-600 w-10 text-right">
                                            {{ $req->progress }}%
                                        </span>
                                    </div>
                                </td>

                                {{-- Tareas --}}
                                <td class="py-3 pr-4">
                                    {{ $req->tasks_done ?? 0 }}/{{ $req->tasks_total ?? 0 }}
                                </td>

                                {{-- Acciones --}}
                                <td class="py-3 pr-4 text-right">
                                    <div class="flex items-center justify-end gap-4">

                                        <a href="{{ route('assets.requirements.show', [$asset, $req]) }}"
                                           class="text-sm underline text-gray-700 hover:text-gray-900">
                                            Abrir
                                        </a>

                                        {{-- Aquí después conectamos RequirementDocuments --}}
                                        <a href="#"
                                           class="text-sm underline text-gray-700 hover:text-gray-900">
                                            Documentos
                                        </a>

                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-gray-500 text-center">
                                    No hay requerimientos todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </div>
</x-app-layout>