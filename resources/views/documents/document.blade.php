<x-layouts.vigia :title="$document->name">

    <x-slot name="breadcrumb">
        <a href="{{ route('documents.index') }}" class="text-gray-600 hover:underline">Documentos</a>
        <span class="text-gray-400">›</span>

        @if($category->parent)
            <a href="{{ route('documents.folders.show', $category->parent) }}" class="text-gray-600 hover:underline">
                {{ $category->parent->name }}
            </a>
            <span class="text-gray-400">›</span>
        @endif

        <a href="{{ route('documents.categories.show', $category) }}" class="text-gray-600 hover:underline">
            {{ $category->name }}
        </a>
        <span class="text-gray-400">›</span>

        <span class="text-gray-700 font-medium">{{ $document->name }}</span>
    </x-slot>

    <div class="bg-white rounded-xl shadow p-6">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-6">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold text-[#1A428A]">
                    {{ $document->name }}
                </h1>

                <div class="text-sm text-gray-500">
                    Categoría:
                    <span class="font-semibold text-gray-700">{{ $category->name }}</span>

                    @if($category->parent)
                        · Carpeta:
                        <span class="font-semibold text-gray-700">{{ $category->parent->name }}</span>
                    @endif

                    @if(auth()->user()->hasGroupScope() && $document->company)
                        · Empresa:
                        <span class="font-semibold text-gray-700">{{ $document->company->name }}</span>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2 mt-2">
                    @if($document->document_type)
                        <span class="inline-flex items-center text-xs px-3 py-1 rounded border bg-gray-50 text-gray-700 border-gray-200">
                            {{ $document->document_type }}
                        </span>
                    @endif

                    @if($document->is_required)
                        <span class="inline-flex items-center text-xs px-3 py-1 rounded border bg-blue-50 text-blue-700 border-blue-200">
                            Requerido
                        </span>
                    @endif

                    @if($document->reference)
                        <span class="inline-flex items-center text-xs px-3 py-1 rounded border bg-gray-50 text-gray-600 border-gray-200">
                            Ref: {{ $document->reference }}
                        </span>
                    @endif
                </div>
            </div>

            <a href="{{ route('documents.categories.show', $category) }}"
               class="shrink-0 px-4 py-2 rounded-md border bg-white text-[#1A428A] border-[#1A428A] font-semibold hover:bg-blue-50">
                Volver
            </a>
        </div>

        {{-- Alerts --}}
        <div class="mt-6 space-y-3">
            @if(session('success') || session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-green-800 text-sm">
                    {{ session('success') ?? session('status') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-red-800 text-sm">
                    {{ session('error') }}
                </div>
            @endif
        </div>

        {{-- Columnas: subir + versión actual --}}
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Subir / Reemplazar --}}
            <div class="bg-white border rounded-xl overflow-hidden">
                <div class="p-5 border-b">
                    <div class="font-semibold text-[#1A428A]">
                        {{ $currentVersion ? 'Subir nueva versión' : 'Subir documento' }}
                    </div>
                    <div class="text-sm text-gray-500">
                        Sube un archivo. Quedará registrado como una nueva versión y el histórico se conserva.
                    </div>
                </div>

                <div class="p-5">
                    @if(!(auth()->user()->isAdmin() || auth()->user()->isOperative()))
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-gray-700 text-sm">
                            No tienes permisos para subir documentos.
                        </div>
                    @else
                        <form method="POST"
                              action="{{ route('documents.document.versions.store', [$category, $document]) }}"
                              enctype="multipart/form-data"
                              class="space-y-4">
                            @csrf

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Archivo</label>
                                <input type="file"
                                       name="file"
                                       class="block w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm"
                                       required>
                                @error('file')
                                    <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-gray-500 mt-1">PDF, JPG o PNG. Máximo 10 MB.</div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Fecha de emisión
                                    </label>
                                    <input type="date"
                                           name="issued_at"
                                           value="{{ old('issued_at', $currentVersion?->issued_at?->format('Y-m-d')) }}"
                                           class="block w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm">
                                    @error('issued_at')
                                        <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Fecha de vencimiento
                                    </label>
                                    <input type="date"
                                           name="valid_until"
                                           value="{{ old('valid_until', $currentVersion?->valid_until?->format('Y-m-d')) }}"
                                           class="block w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm">
                                    @error('valid_until')
                                        <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <button type="submit"
                                    class="px-4 py-2 rounded-md bg-[#1A428A] text-white font-semibold hover:bg-[#15356d]">
                                {{ $currentVersion ? 'Subir nueva versión' : 'Subir documento' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Versión actual --}}
            <div class="bg-white border rounded-xl overflow-hidden">
                <div class="p-5 border-b">
                    <div class="font-semibold text-[#1A428A]">Versión actual</div>
                    <div class="text-sm text-gray-500">
                        La versión vigente del documento. Las anteriores permanecen en el historial.
                    </div>
                </div>

                <div class="p-5">
                    @if($currentVersion)
                        <div class="border rounded-xl p-4 flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="font-semibold text-gray-900 truncate">
                                    {{ $currentVersion->original_name ?? basename($currentVersion->file_path) }}
                                </div>
                                <div class="text-sm text-gray-500 mt-1">
                                    <span class="block">Subido por: {{ $currentVersion->uploader?->name ?? '—' }}</span>
                                    <span class="block">{{ $currentVersion->created_at?->format('d/m/Y H:i') }}</span>
                                    @if($currentVersion->issued_at)
                                        <span class="block">Emisión: {{ $currentVersion->issued_at->format('d/m/Y') }}</span>
                                    @endif
                                    @if($currentVersion->valid_until)
                                        <span class="block {{ $currentVersion->isExpired() ? 'text-red-600 font-medium' : ($currentVersion->isNearExpiration() ? 'text-yellow-600 font-medium' : '') }}">
                                            Vigente hasta: {{ $currentVersion->valid_until->format('d/m/Y') }}
                                            @if($currentVersion->isExpired()) <span class="text-xs">(Vencido)</span>
                                            @elseif($currentVersion->isNearExpiration()) <span class="text-xs">(Por vencer)</span>
                                            @endif
                                        </span>
                                    @endif
                                    <span class="block">Versión: {{ $currentVersion->version_number }} · <span class="text-green-700 font-medium">Actual</span></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ route('document-versions.preview', $currentVersion) }}"
                                   target="_blank"
                                   class="px-3 py-2 rounded-md border font-semibold text-sm bg-white text-[#1A428A] border-[#1A428A] hover:bg-blue-50">
                                    Ver
                                </a>
                                <a href="{{ route('document-versions.download', $currentVersion) }}"
                                   class="px-3 py-2 rounded-md border font-semibold text-sm bg-white text-[#1A428A] border-[#1A428A] hover:bg-blue-50">
                                    Descargar
                                </a>
                                @if(auth()->user()->isAdmin() || auth()->user()->isOperative())
                                    <button type="button"
                                            onclick="openDeleteModal(
                                                '{{ route('document-versions.destroy', [$category, $document, $currentVersion]) }}',
                                                @js($currentVersion->original_name ?? basename($currentVersion->file_path)),
                                                '{{ $currentVersion->version_number }}'
                                            )"
                                            class="px-3 py-2 rounded-md font-semibold text-sm bg-[#DB0000] text-white hover:bg-red-700">
                                        Eliminar
                                    </button>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-gray-700 text-sm">
                            Aún no hay archivo subido para este documento.
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Historial de versiones --}}
        <div class="mt-8 bg-white border rounded-xl overflow-hidden">
            <div class="p-5 border-b">
                <div class="font-semibold text-[#1A428A]">Histórico documental</div>
                <div class="text-sm text-gray-500">
                    Se conservan todas las versiones del documento registradas.
                </div>
            </div>

            <div class="p-5">
                @if($versionHistory->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($versionHistory as $v)
                            <div class="border rounded-xl p-4 flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="font-semibold text-gray-900 truncate">
                                        {{ $v->original_name ?? basename($v->file_path) }}
                                    </div>
                                    <div class="text-sm text-gray-500 mt-1">
                                        <span class="block">
                                            Versión: {{ $v->version_number }}
                                            ·
                                            @if($v->is_current)
                                                <span class="text-green-700 font-medium">Actual</span>
                                            @else
                                                <span class="font-medium">{{ ucfirst($v->status ?? 'Reemplazada') }}</span>
                                            @endif
                                        </span>
                                        <span class="block">Subido por: {{ $v->uploader?->name ?? '—' }}</span>
                                        <span class="block">Fecha de carga: {{ $v->created_at?->format('d/m/Y H:i') }}</span>
                                        @if($v->issued_at)
                                            <span class="block">Emisión: {{ $v->issued_at->format('d/m/Y') }}</span>
                                        @endif
                                        @if($v->valid_until)
                                            <span class="block {{ $v->isExpired() ? 'text-red-600 font-medium' : ($v->isNearExpiration() ? 'text-yellow-600 font-medium' : '') }}">
                                                Vigente hasta: {{ $v->valid_until->format('d/m/Y') }}
                                                @if($v->isExpired()) <span class="text-xs">(Vencido)</span>
                                                @elseif($v->isNearExpiration()) <span class="text-xs">(Por vencer)</span>
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <a href="{{ route('document-versions.preview', $v) }}"
                                       target="_blank"
                                       class="px-3 py-2 rounded-md border font-semibold text-sm bg-white text-[#1A428A] border-[#1A428A] hover:bg-blue-50">
                                        Ver
                                    </a>
                                    <a href="{{ route('document-versions.download', $v) }}"
                                       class="px-3 py-2 rounded-md border font-semibold text-sm bg-white text-[#1A428A] border-[#1A428A] hover:bg-blue-50">
                                        Descargar
                                    </a>
                                    @if(auth()->user()->isAdmin() || auth()->user()->isOperative())
                                        <button type="button"
                                                onclick="openDeleteModal(
                                                    '{{ route('document-versions.destroy', [$category, $document, $v]) }}',
                                                    @js($v->original_name ?? basename($v->file_path)),
                                                    '{{ $v->version_number }}'
                                                )"
                                                class="px-3 py-2 rounded-md font-semibold text-sm bg-[#DB0000] text-white hover:bg-red-700">
                                            Eliminar
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-gray-700 text-sm">
                        Aún no hay versiones registradas en el historial.
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- Modal de confirmación de eliminación --}}
    <div id="deleteModal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-lg rounded-xl bg-white shadow-2xl">
            <div class="p-6 border-b">
                <h3 class="text-lg font-bold text-gray-900">Confirmar eliminación</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Vas a eliminar una versión del historial. Esta acción debe usarse solo para archivos cargados por error.
                </p>
            </div>

            <div class="p-6 space-y-4">
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-[#1A428A]">
                    Esta acción eliminará el archivo seleccionado permanentemente.
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                    <div><span class="font-semibold">Archivo:</span> <span id="deleteFileName">—</span></div>
                    <div class="mt-1"><span class="font-semibold">Versión:</span> <span id="deleteVersionNumber">—</span></div>
                </div>

                <div>
                    <label for="delete_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                        Escribe <span class="font-bold">ELIMINAR</span> para confirmar
                    </label>
                    <input id="delete_confirmation"
                           type="text"
                           class="block w-full rounded-md border-gray-300 focus:border-red-600 focus:ring-red-600 text-sm"
                           placeholder="ELIMINAR"
                           oninput="validateDeleteConfirmation()">
                </div>

                <form id="deleteForm" method="POST" class="flex items-center justify-end gap-3">
                    @csrf
                    @method('DELETE')

                    <button type="button"
                            onclick="closeDeleteModal()"
                            class="px-4 py-2 rounded-md border border-gray-300 bg-white text-gray-700 font-semibold hover:bg-gray-50">
                        Cancelar
                    </button>

                    <button id="deleteSubmitButton"
                            type="submit"
                            disabled
                            class="px-4 py-2 rounded-md bg-[#1A428A] text-white font-semibold opacity-50 cursor-not-allowed disabled:opacity-50 disabled:cursor-not-allowed">
                        Confirmar eliminación
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openDeleteModal(actionUrl, fileName, versionNumber) {
            const modal = document.getElementById('deleteModal');
            const form  = document.getElementById('deleteForm');
            const input = document.getElementById('delete_confirmation');
            const btn   = document.getElementById('deleteSubmitButton');

            form.action = actionUrl;
            document.getElementById('deleteFileName').textContent = fileName || '—';
            document.getElementById('deleteVersionNumber').textContent = versionNumber || '—';
            input.value = '';
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            input.focus();
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function validateDeleteConfirmation() {
            const input = document.getElementById('delete_confirmation');
            const btn   = document.getElementById('deleteSubmitButton');
            const valid = input.value.trim() === 'ELIMINAR';
            btn.disabled = !valid;
            btn.classList.toggle('opacity-50', !valid);
            btn.classList.toggle('cursor-not-allowed', !valid);
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeDeleteModal();
        });
    </script>

</x-layouts.vigia>
