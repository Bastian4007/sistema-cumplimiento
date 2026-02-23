<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Carpeta: {{ $requirement->template?->name ?? $requirement->type }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto space-y-6">
        <div class="bg-white shadow sm:rounded-lg p-6">
            <div class="text-sm text-gray-600">
                <div><strong>Activo:</strong> {{ $asset->name }}</div>
                <div><strong>Vence:</strong> {{ $requirement->due_date?->format('Y-m-d') ?? 'Sin fecha' }}</div>
                <div><strong>Riesgo:</strong> {{ $requirement->risk_level }}</div>
                <div><strong>Estatus:</strong> {{ $requirement->computed_status }}</div>
                <div><strong>Progreso:</strong> {{ $requirement->progress }}%</div>
                <div><strong>Tareas:</strong> {{ $requirement->tasks_done }}/{{ $requirement->tasks_total }}</div>
            </div>
        </div>

        <div class="bg-white shadow sm:rounded-lg p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Tareas</h3>

            <div class="space-y-3">
                @forelse($requirement->tasks as $task)
                    <div class="border rounded-lg p-4">
                        <div class="font-medium">{{ $task->title }}</div>
                        <div class="text-sm text-gray-500">
                            Status: {{ $task->status->value }} |
                            Vence: {{ $task->due_date?->format('Y-m-d') ?? '-' }} |
                            Evidencias: {{ $task->documents_count ?? 0 }}
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">No hay tareas todavía.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>