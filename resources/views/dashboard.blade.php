{{-- resources/views/dashboard.blade.php --}}
<x-layouts.vigia :title="'Tablero'">
    <x-slot name="breadcrumb">
        <span class="text-gray-700 font-medium">Tablero</span>
    </x-slot>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold text-[#1A428A]">Tablero de cumplimiento</h1>
        </div>

        {{-- Cards --}}
        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white border rounded-lg shadow-sm p-4">
                <div class="text-sm font-semibold text-[#1A428A]">Activos</div>
                <div class="mt-2 text-2xl font-bold text-gray-800">{{ $stats['assets'] ?? 0 }}</div>
            </div>

            <div class="bg-white border rounded-lg shadow-sm p-4">
                <div class="text-sm font-semibold text-[#1A428A]">Tareas</div>
                <div class="mt-2 text-2xl font-bold text-gray-800">{{ $stats['tasks'] ?? 0 }}</div>
            </div>

            <div class="bg-[#FFB529] rounded-lg shadow-sm p-4 text-white">
                <div class="text-sm font-semibold">Próximas a vencer</div>
                <div class="mt-2 text-2xl font-bold">{{ $stats['due_soon'] ?? 0 }}</div>
            </div>

            <div class="bg-[#DB0000] rounded-lg shadow-sm p-4 text-white">
                <div class="text-sm font-semibold">Vencidas</div>
                <div class="mt-2 text-2xl font-bold">{{ $stats['overdue'] ?? 0 }}</div>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Próximos a vencer --}}
            <div>
                <div class="text-sm font-semibold text-[#FFB529] mb-3">Próximos a vencer</div>

                <div class="border rounded-lg overflow-hidden">
                    @forelse($upcoming as $r)
                        <div class="p-4 border-b last:border-b-0">
                            <div class="font-semibold text-gray-800">
                                {{ $r->template?->name ?? $r->type ?? 'Requerimiento' }}
                            </div>

                            <div class="text-xs text-gray-500">
                                Activo: {{ $r->asset?->name ?? '—' }}
                                · Tipo: {{ $r->asset?->assetType?->name ?? '—' }}
                                · Vence: {{ $r->due_date?->format('Y-m-d') ?? '—' }}
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-sm text-gray-500">
                            No hay requerimientos próximos a vencer.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Críticos --}}
            <div>
                <div class="text-sm font-semibold text-[#DB0000] mb-3">Críticos (vencidos / en riesgo)</div>

                <div class="border rounded-lg overflow-hidden">
                    @forelse($critical as $r)
                        <div class="p-4 border-b last:border-b-0">
                            <div class="flex items-center justify-between gap-4">
                                <div class="font-semibold text-gray-800">
                                    {{ $r->template?->name ?? $r->type ?? 'Requerimiento' }}
                                </div>

                                <span class="text-xs font-semibold px-2 py-1 rounded-full
                                    {{ $r->risk_level === 'expired'
                                        ? 'bg-red-100 text-red-700'
                                        : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ $r->risk_level === 'expired' ? 'Vencido' : 'En riesgo' }}
                                </span>
                            </div>

                            <div class="text-xs text-gray-500 mt-1">
                                Activo: {{ $r->asset?->name ?? '—' }}
                                · Tipo: {{ $r->asset?->assetType?->name ?? '—' }}
                                · Vence: {{ $r->due_date?->format('Y-m-d') ?? '—' }}
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-sm text-gray-500">
                            No hay requerimientos críticos.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layouts.vigia>