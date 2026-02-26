<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Historial
            @isset($task)
                - Tarea: {{ $task->title }}
            @endisset
        </h2>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto space-y-4">

        <div class="bg-white shadow sm:rounded-lg p-6">

            <div class="space-y-4">
                @forelse($logs as $log)
                    <div class="border rounded-lg p-4 bg-gray-50">

                        <div class="text-xs text-gray-500">
                            {{ $log->created_at->format('Y-m-d H:i') }}
                            —
                            <strong>{{ optional($log->actor)->name ?? 'Sistema' }}</strong>
                        </div>

                        <div class="mt-1 font-medium text-gray-800">
                            {{ ucfirst(str_replace('.', ' ', $log->action)) }}
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
                        Sin movimientos.
                    </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $logs->links() }}
            </div>

        </div>

        <div>
            <a href="{{ route('assets.requirements.show', [$asset, $requirement]) }}"
               class="text-sm underline text-gray-700">
                ← Volver al requirement
            </a>
        </div>

    </div>

</x-app-layout>