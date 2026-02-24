<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard de cumplimiento
        </h2>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto space-y-6">

        {{-- KPIs --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="bg-white p-4 rounded shadow">
                <div class="text-sm text-gray-500">Total</div>
                <div class="text-2xl font-semibold">{{ $metrics['kpis']['total'] }}</div>
            </div>

            <div class="bg-white p-4 rounded shadow">
                <div class="text-sm text-gray-500">Expired</div>
                <div class="text-2xl font-semibold">{{ $metrics['kpis']['expired'] }}</div>
            </div>

            <div class="bg-white p-4 rounded shadow">
                <div class="text-sm text-gray-500">Danger</div>
                <div class="text-2xl font-semibold">{{ $metrics['kpis']['danger'] }}</div>
            </div>

            <div class="bg-white p-4 rounded shadow">
                <div class="text-sm text-gray-500">Warning</div>
                <div class="text-2xl font-semibold">{{ $metrics['kpis']['warning'] }}</div>
            </div>
        </div>

        {{-- Próximos a vencer --}}
        <div class="bg-white p-6 rounded shadow">
            <h3 class="font-semibold mb-4">Próximos a vencer</h3>

            <div class="space-y-3">
                @forelse($upcoming as $r)
                    <div class="border rounded p-4 flex items-center justify-between">
                        <div>
                            <div class="font-medium">
                                {{ $r->template?->name ?? $r->type }}
                            </div>
                            <div class="text-sm text-gray-500">
                                Activo: {{ $r->asset?->name }}
                                · Tipo: {{ $r->asset?->assetType?->name ?? '—' }}
                            </div>
                        </div>

                        <div class="text-right text-sm">
                            <div>Vence: {{ $r->due_date?->format('Y-m-d') }}</div>
                            <div class="text-gray-500">Riesgo: {{ $r->risk_level }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">No hay vencimientos próximos.</div>
                @endforelse
            </div>
        </div>

        {{-- Críticos --}}
        <div class="bg-white p-6 rounded shadow">
            <h3 class="font-semibold mb-4">Críticos (danger / expired)</h3>

            <div class="space-y-3">
                @forelse($critical as $r)
                    <div class="border rounded p-4 flex items-center justify-between">
                        <div>
                            <div class="font-medium">
                                {{ $r->template?->name ?? $r->type }}
                            </div>
                            <div class="text-sm text-gray-500">
                                Activo: {{ $r->asset?->name }}
                                · Tipo: {{ $r->asset?->assetType?->name ?? '—' }}
                            </div>
                        </div>

                        <div class="text-right text-sm">
                            <div>Vence: {{ $r->due_date?->format('Y-m-d') }}</div>
                            <div class="font-semibold">Riesgo: {{ $r->risk_level }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">No hay críticos.</div>
                @endforelse
            </div>
        </div>

    </div>
</x-app-layout>