{{-- resources/views/assets/show.blade.php --}}
<x-layouts.vigia :title="$asset->name">

    <x-slot name="breadcrumb">
        <a href="{{ route('assets.index') }}" class="text-gray-600 hover:underline">Activos y Actividades</a>
        <span class="text-gray-400">›</span>
        <span class="text-gray-700 font-medium">{{ $asset->name }}</span>
    </x-slot>

    @php
        $assetInactive = ($asset->status ?? null) === \App\Models\Asset::STATUS_INACTIVE
            || (method_exists($asset, 'isInactive') && $asset->isInactive());
    @endphp

    <div class="bg-white rounded-xl shadow p-6 space-y-8">

        {{-- ================= HEADER ================= --}}
        <div class="flex items-start justify-between flex-wrap gap-6">
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-3xl font-bold text-[#1A428A]">
                        {{ $asset->name }}
                    </h1>

                    <span class="text-xs px-3 py-1 rounded border
                        {{ $assetInactive
                            ? 'bg-gray-100 text-gray-700 border-gray-300'
                            : 'bg-green-50 text-green-700 border-green-200' }}">
                        {{ $assetInactive ? 'INACTIVO' : 'ACTIVO' }}
                    </span>
                </div>

                @if(!empty($asset->code))
                    <div class="text-sm text-gray-500 mt-1">
                        Código: {{ $asset->code }}
                    </div>
                @endif
            </div>

            {{-- Botones solo Operativo --}}
            @if(auth()->user()->isOperative())
                <div class="flex items-center gap-3">
                    <a href="{{ route('assets.edit', $asset) }}"
                       class="px-5 py-2 rounded-md border border-[#1A428A] text-[#1A428A] font-semibold hover:bg-blue-50
                       {{ $assetInactive ? 'opacity-50 pointer-events-none' : '' }}">
                        Editar
                    </a>

                    @if($assetInactive)
                        <form method="POST" action="{{ route('assets.activate', $asset) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="px-6 py-2 rounded-md font-semibold text-white bg-[#1A428A] hover:bg-[#15356d]">
                                Activar
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('assets.deactivate', $asset) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                onclick="return confirm('¿Seguro que quieres desactivar este activo?');"
                                class="px-6 py-2 rounded-md font-semibold text-white bg-[#DB0000] hover:bg-red-700">
                                Desactivar
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        {{-- ================= RESUMEN ================= --}}
        <div class="bg-gray-50 border rounded-xl p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-700">

                <div class="space-y-2">
                    <div><span class="font-semibold text-gray-900">Tipo:</span> {{ $asset->assetType->name ?? '-' }}</div>
                    <div><span class="font-semibold text-gray-900">Código:</span> {{ $asset->code ?? '-' }}</div>
                </div>

                <div class="space-y-2">
                    <div><span class="font-semibold text-gray-900">Ubicación:</span> {{ $asset->location ?? '-' }}</div>
                    <div><span class="font-semibold text-gray-900">Responsable:</span> {{ $asset->responsible->name ?? '-' }}</div>
                </div>

            </div>
        </div>

        {{-- ================= REQUERIMIENTOS ================= --}}
        <div class="bg-white border rounded-xl overflow-hidden">

            <div class="p-6 border-b">
                <div class="font-semibold text-[#1A428A] text-lg">
                    Normativa
                </div>
                <div class="text-sm text-gray-500">
                    Visualiza el avance, riesgo y estado de cada carpeta de cumplimiento.
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-6 py-4 font-semibold">Carpeta</th>
                            <th class="text-left px-6 py-4 font-semibold whitespace-nowrap">Vence</th>
                            <th class="text-left px-6 py-4 font-semibold whitespace-nowrap">Riesgo</th>
                            <th class="text-left px-6 py-4 font-semibold whitespace-nowrap">Estatus</th>
                            <th class="text-left px-6 py-4 font-semibold whitespace-nowrap">Progreso</th>
                            <th class="text-left px-6 py-4 font-semibold whitespace-nowrap">Tareas</th>
                            <th class="text-right px-6 py-4 font-semibold whitespace-nowrap">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">

                        @forelse($asset->requirements as $req)
                            @php
                                $title = $req->template?->name ?? $req->type;
                                $due = $req->due_date?->format('Y-m-d') ?? '-';

                                $tasksTotal = (int) ($req->tasks_total ?? 0);
                                $tasksDone  = (int) ($req->tasks_done ?? 0);
                                $progress   = $tasksTotal > 0 ? (int) round(($tasksDone / $tasksTotal) * 100) : 0;

                                $riskVal = strtolower($req->risk_level ?? 'normal');
                                $statusLabel = strtoupper($req->computed_status ?? $req->status ?? 'pending');
                            @endphp

                            <tr class="hover:bg-gray-50">

                                <td class="px-6 py-4 font-semibold text-gray-800">
                                    {{ $title }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ $due }}
                                </td>

                                {{-- Riesgo --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($riskVal === 'danger')
                                        <span class="text-xs px-3 py-1 rounded border bg-red-50 text-red-700 border-red-200">DANGER</span>
                                    @elseif($riskVal === 'warning')
                                        <span class="text-xs px-3 py-1 rounded border bg-yellow-50 text-yellow-700 border-yellow-200">WARNING</span>
                                    @else
                                        <span class="text-xs px-3 py-1 rounded border bg-green-50 text-green-700 border-green-200">NORMAL</span>
                                    @endif
                                </td>

                                {{-- Estatus --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs px-3 py-1 rounded border bg-gray-50 text-gray-800">
                                        {{ $statusLabel }}
                                    </span>
                                </td>

                                {{-- Progreso --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-32 bg-gray-200 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 bg-gray-800"
                                                style="width: {{ $progress }}%">
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-600">{{ $progress }}%</div>
                                    </div>
                                </td>

                                {{-- Tareas --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ $tasksDone }}/{{ $tasksTotal }}
                                </td>

                                {{-- Acciones --}}
                                <td class="px-6 py-4 text-right whitespace-nowrap space-x-4">
                                    <a href="{{ route('assets.requirements.show', [$asset, $req]) }}"
                                       class="text-blue-600 hover:underline font-semibold">
                                        Abrir
                                    </a>

                                    <a href="{{ route('assets.requirements.documents.index', [$asset, $req]) }}"
                                       class="text-blue-600 hover:underline font-semibold">
                                        Documentos
                                    </a>
                                </td>

                            </tr>

                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                    No hay requerimientos todavía.
                                </td>
                            </tr>
                        @endforelse

                    </tbody>

                </table>
            </div>
        </div>

    </div>
</x-layouts.vigia>