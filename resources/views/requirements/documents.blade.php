{{-- resources/views/requirements/documents.blade.php --}}

<x-layouts.vigia
    :title="'Documento oficial: ' . ($requirement->template?->name ?? $requirement->type)"
    :nav-context="$navContext"
>

    <x-slot name="breadcrumb">
        <a href="{{ route('assets.index') }}" class="text-gray-600 hover:underline">
            Activos y Actividades
        </a>

        <span class="text-gray-400">›</span>

        <a href="{{ route('assets.show', $asset) }}" class="text-gray-600 hover:underline">
            {{ $asset->name }}
        </a>

        <span class="text-gray-400">›</span>

        <a href="{{ route('assets.requirements.show', [$asset, $requirement]) }}"
           class="text-gray-600 hover:underline">
            {{ $requirement->template?->name ?? $requirement->type }}
        </a>

        <span class="text-gray-400">›</span>

        <span class="text-gray-700 font-medium">
            Documento oficial
        </span>
    </x-slot>


    @php
        $assetInactive = $assetInactive ?? (
            ($asset->status ?? null) === \App\Models\Asset::STATUS_INACTIVE
            || (method_exists($asset, 'isInactive') && $asset->isInactive())
        );

        $doc = $requirement->documents?->sortByDesc('created_at')->first();
    @endphp


    <div class="bg-white rounded-xl shadow p-6">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-6">

            <div class="space-y-1">
                <h1 class="text-2xl font-bold text-[#1A428A]">
                    Documento oficial
                </h1>

                <div class="text-sm text-gray-500">
                    Carpeta:
                    <span class="font-semibold text-gray-700">
                        {{ $requirement->template?->name ?? $requirement->type }}
                    </span>

                    · Activo:

                    <span class="font-semibold text-gray-700">
                        {{ $asset->name }}
                    </span>
                </div>

                @if($assetInactive)
                    <div class="mt-2 inline-flex items-center text-xs px-3 py-1 rounded border bg-gray-100 text-gray-700 border-gray-300">
                        Activo desactivado
                    </div>
                @else
                    <div class="mt-2 inline-flex items-center text-xs px-3 py-1 rounded border bg-green-50 text-green-700 border-green-200">
                        Activo activo
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('assets.requirements.show', [$asset, $requirement]) }}"
                   class="px-4 py-2 rounded-md border bg-white text-[#1A428A] border-[#1A428A] font-semibold hover:bg-blue-50">
                    Volver
                </a>
            </div>

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


        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Subir / Reemplazar --}}
            <div class="bg-white border rounded-xl overflow-hidden">

                <div class="p-5 border-b">
                    <div class="font-semibold text-[#1A428A]">
                        {{ $doc ? 'Reemplazar documento' : 'Subir documento' }}
                    </div>

                    <div class="text-sm text-gray-500">
                        Sube un archivo y se guardará como el documento oficial de esta carpeta.
                    </div>
                </div>


                <div class="p-5">

                    @if(!auth()->user()->isOperative())

                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-gray-700 text-sm">
                            No tienes permisos para subir documentación oficial.
                        </div>

                    @elseif($assetInactive)

                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-gray-700 text-sm">
                            Este activo está desactivado. Actívalo para subir documentación oficial.
                        </div>

                    @else

                        <form method="POST"
                              action="{{ route('assets.requirements.documents.store', [$asset, $requirement]) }}"
                              enctype="multipart/form-data"
                              class="space-y-4">

                            @csrf

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Archivo
                                </label>

                                <input type="file"
                                       name="file"
                                       class="block w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm"
                                       required>

                                @error('file')
                                    <div class="text-sm text-red-600 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div class="text-xs text-gray-500 mt-1">
                                    Recomendado: PDF. Tamaño máximo: 10MB.
                                </div>
                            </div>

                            <button type="submit"
                                class="px-4 py-2 rounded-md bg-[#1A428A] text-white font-semibold hover:bg-[#15356d]">
                                {{ $doc ? 'Reemplazar' : 'Subir' }}
                            </button>

                        </form>

                    @endif

                </div>

            </div>


            {{-- Documento actual --}}
            <div class="bg-white border rounded-xl overflow-hidden">

                <div class="p-5 border-b">
                    <div class="font-semibold text-[#1A428A]">
                        Documento actual
                    </div>

                    <div class="text-sm text-gray-500">
                        Solo se conserva un documento oficial por carpeta.
                    </div>
                </div>


                <div class="p-5">

                    @if($doc)

                        <div class="border rounded-xl p-4 flex items-start justify-between gap-4">

                            <div class="min-w-0">
                                <div class="font-semibold text-gray-900 truncate">
                                    {{ $doc->original_name ?? basename($doc->file_path) }}
                                </div>

                                <div class="text-sm text-gray-500 mt-1">

                                    <span class="block">
                                        Subido por:
                                    </span>

                                    <span class="block">
                                        {{ $doc->uploader?->name ?? '—' }}
                                    </span>

                                    <span class="block">
                                        {{ optional($doc->created_at)->format('Y-m-d H:i') }}
                                    </span>

                                </div>
                            </div>


                            <div class="flex items-center gap-2 shrink-0">

                                <a href="{{ route('assets.requirements.documents.preview', [$asset, $requirement, $doc]) }}"
                                   target="_blank"
                                   class="px-3 py-2 rounded-md border font-semibold text-sm
                                   {{ $assetInactive ? 'bg-gray-100 text-gray-500 border-gray-300 pointer-events-none' : 'bg-white text-[#1A428A] border-[#1A428A] hover:bg-blue-50' }}">
                                    Ver
                                </a>

                                <a href="{{ route('assets.requirements.documents.download', [$asset, $requirement, $doc]) }}"
                                   class="px-3 py-2 rounded-md border font-semibold text-sm
                                   {{ $assetInactive ? 'bg-gray-100 text-gray-500 border-gray-300 pointer-events-none' : 'bg-white text-[#1A428A] border-[#1A428A] hover:bg-blue-50' }}">
                                    Descargar
                                </a>

                                @if(auth()->user()->isOperative())

                                    <form method="POST"
                                          action="{{ route('assets.requirements.documents.destroy', [$asset, $requirement, $doc]) }}"
                                          onsubmit="return confirm('¿Eliminar este documento oficial?')">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                            class="px-3 py-2 rounded-md font-semibold text-sm
                                            {{ $assetInactive ? 'bg-gray-100 text-gray-500 border border-gray-300 pointer-events-none' : 'bg-[#DB0000] text-white hover:bg-red-700' }}">
                                            Eliminar
                                        </button>

                                    </form>

                                @endif

                            </div>

                        </div>

                    @else

                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-gray-700 text-sm">
                            Aún no hay documento oficial.
                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

</x-layouts.vigia>