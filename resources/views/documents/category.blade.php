<x-layouts.vigia :title="$category->name">

    <x-slot name="breadcrumb">
        <a href="{{ route('documents.index') }}" class="text-gray-500 hover:underline">Documentos</a>
        <span class="mx-1 text-gray-400">›</span>
        @if($category->parent)
            <a href="{{ route('documents.folders.show', $category->parent) }}"
               class="text-gray-500 hover:underline">
                {{ $category->parent->name }}
            </a>
            <span class="mx-1 text-gray-400">›</span>
        @endif
        <span class="text-gray-700 font-medium">{{ $category->name }}</span>
    </x-slot>

    {{-- HEADER --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-[#1A428A]">{{ $category->name }}</h1>
            @if($category->parent)
                <p class="text-sm text-gray-500 mt-1">{{ $category->parent->name }}</p>
            @endif
        </div>

        <a href="{{ $category->parent ? route('documents.folders.show', $category->parent) : route('documents.index') }}"
           class="text-sm text-gray-500 hover:underline">
            ← Volver
        </a>
    </div>

    {{-- TABLA DE DOCUMENTOS --}}
    <div class="mt-6 bg-white border rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold">Nombre del Documento</th>
                        <th class="text-left px-4 py-3 font-semibold">Referencia / Oficio</th>
                        <th class="text-left px-4 py-3 font-semibold">Fecha</th>
                        <th class="text-left px-4 py-3 font-semibold">Vencimiento</th>
                        <th class="text-left px-4 py-3 font-semibold">Tipo</th>
                        <th class="text-left px-4 py-3 font-semibold">Responsable</th>
                        <th class="text-left px-4 py-3 font-semibold">Accesos Autorizados</th>
                        <th class="text-left px-4 py-3 font-semibold">Archivo</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($documents as $document)
                        @php
                            $version = $document->currentVersion;
                            $isExpired = $document->isExpired();
                            $isNearExpiration = !$isExpired && $document->isNearExpiration();
                        @endphp

                        <tr class="border-t hover:bg-gray-50">

                            {{-- Nombre --}}
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $document->name }}</div>
                                @if($document->is_required)
                                    <span class="inline-block mt-1 text-xs bg-blue-100 text-blue-700 rounded px-1.5 py-0.5">
                                        Requerido
                                    </span>
                                @endif
                            </td>

                            {{-- Referencia / Oficio --}}
                            <td class="px-4 py-3 text-gray-600">
                                {{ $document->reference ?? '—' }}
                            </td>

                            {{-- Fecha (issued_at de la versión actual) --}}
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                {{ $version?->issued_at?->format('d/m/Y') ?? '—' }}
                            </td>

                            {{-- Vencimiento --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($version?->valid_until)
                                    <span class="font-medium
                                        @if($isExpired) text-red-600
                                        @elseif($isNearExpiration) text-yellow-600
                                        @else text-gray-700
                                        @endif">
                                        {{ $version->valid_until->format('d/m/Y') }}
                                    </span>
                                    @if($isExpired)
                                        <div class="text-xs text-red-500">Vencido</div>
                                    @elseif($isNearExpiration)
                                        <div class="text-xs text-yellow-600">Por vencer</div>
                                    @endif
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>

                            {{-- Tipo de Documento --}}
                            <td class="px-4 py-3 text-gray-600">
                                {{ $document->document_type ?? '—' }}
                            </td>

                            {{-- Responsable --}}
                            <td class="px-4 py-3 text-gray-700">
                                {{ $document->responsible_name ?? '—' }}
                            </td>

                            {{-- Accesos Autorizados --}}
                            <td class="px-4 py-3 text-gray-500 text-xs max-w-xs">
                                @if($document->authorized_access_notes)
                                    <x-truncate :text="$document->authorized_access_notes" :length="80" />
                                @else
                                    —
                                @endif
                            </td>

                            {{-- Archivo --}}
                            <td class="px-4 py-3">
                                @if($version && $version->file_path)
                                    <span class="inline-flex items-center gap-1 text-xs bg-green-100 text-green-700 rounded px-2 py-1 font-medium">
                                        ✓ Disponible
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs bg-gray-100 text-gray-500 rounded px-2 py-1">
                                        Sin archivo
                                    </span>
                                @endif
                            </td>

                            {{-- Acción --}}
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('documents.document.show', [$category, $document]) }}"
                                   class="text-[#1A428A] font-semibold text-sm hover:underline">
                                    Gestionar →
                                </a>
                            </td>

                        </tr>
                    @empty
                        <tr class="border-t">
                            <td colspan="9" class="px-6 py-6 text-center text-gray-500">
                                No hay documentos en esta categoría.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

</x-layouts.vigia>
