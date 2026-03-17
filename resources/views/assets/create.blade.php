{{-- resources/views/assets/create.blade.php --}}
<x-layouts.vigia :title="'Crear un activo'">
    <x-slot name="breadcrumb">
        <a href="{{ route('assets.index') }}" class="text-gray-600 hover:underline">Activos y Actividades</a>
        <span class="text-gray-400">›</span>
        <span class="text-gray-700 font-medium">Crear un activo</span>
    </x-slot>
    @php
        $selectClass = "mt-1 w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm";
    @endphp

    {{-- Select2 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <div class="bg-white rounded-xl shadow p-6 max-w-5xl">
        <h1 class="text-2xl font-semibold text-[#1A428A]">Crear un activo</h1>

        @if ($errors->any())
            <div class="mt-4 p-4 border border-red-300 bg-red-50 rounded-lg">
                <ul class="list-disc list-inside text-red-700 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('assets.store') }}" class="mt-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre de activo</label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="mt-1 w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm"
                        required
                    />
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Selecciona un responsable</label>
                    <select
                        id="responsible_user_id"
                        name="responsible_user_id"
                        class="{{ $selectClass }}"
                        required>
                        <option value="">-- Selecciona un responsable --</option>

                        @foreach($responsibles as $u)
                            <option value="{{ $u->id }}" @selected((string) old('responsible_user_id') === (string) $u->id)>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('responsible_user_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Selecciona ubicación</label>
                    <select
                        name="location"
                        class="{{ $selectClass }}"
                        required
                    >
                        <option value="">-- Selecciona ubicación --</option>

                        @foreach($mexicoStates as $state)
                            <option value="{{ $state }}" @selected(old('location') === $state)>
                                {{ $state }}
                            </option>
                        @endforeach
                    </select>

                    @error('location')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Selecciona un tipo</label>
                    <select
                        name="asset_type_id"
                        class="{{ $selectClass }}"
                        required
                    >
                        <option value="">-- Selecciona tipo --</option>

                        @foreach($assetTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) old('asset_type_id') === (string) $type->id)>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('asset_type_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Fecha de inicio de operaciones</label>
                    <input
                        type="date"
                        name="compliance_start_date"
                        value="{{ old('compliance_start_date') }}"
                        class="mt-1 w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm"
                        required
                    />
                    @error('compliance_start_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Fecha límite para cumplir los requerimientos</label>
                    <input
                        type="date"
                        name="compliance_due_date"
                        value="{{ old('compliance_due_date') }}"
                        class="mt-1 w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm"
                        required
                    >
                    @error('compliance_due_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="vault_location" class="block text-sm font-medium text-gray-700">
                        Bóveda documental
                    </label>
                    <input
                        type="text"
                        name="vault_location"
                        id="vault_location"
                        value="{{ old('vault_location') }}"
                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-[#1A428A] focus:ring-[#1A428A] text-sm"
                        placeholder="Ej. Bóveda A - Estante 3"
                    >
                    @error('vault_location')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('assets.index') }}"
                class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50">
                    Cancelar
                </a>

                <button type="submit"
                        class="px-4 py-2 rounded-md bg-[#1A428A] text-white font-semibold hover:bg-[#15356d]">
                    Crear activo
                </button>
            </div>
        </form>
    </div>

    {{-- Select2 JS (requiere jQuery) --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
    new TomSelect('#responsible_user_id', {
        create: false,
        placeholder: '-- Selecciona un responsable --',
        allowEmptyOption: true,
        closeAfterSelect: true,
        sortField: { field: "text", direction: "asc" }
    });
    });
    </script>
</x-layouts.vigia>