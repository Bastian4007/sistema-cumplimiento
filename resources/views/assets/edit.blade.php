{{-- resources/views/assets/edit.blade.php --}}
<x-layouts.vigia :title="'Editar: ' . $asset->name">
    <x-slot name="breadcrumb">
        <a href="{{ route('assets.index') }}" class="text-gray-600 hover:underline">Bóveda</a>
        <span class="text-gray-400">›</span>
        <a href="{{ route('assets.show', $asset) }}" class="text-gray-600 hover:underline">{{ $asset->name }}</a>
        <span class="text-gray-400">›</span>
        <span class="text-gray-700 font-medium">Editar</span>
    </x-slot>

    {{-- Select2 CSS (si ya lo metes global en el layout, quítalo de aquí) --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <div class="bg-white rounded-xl shadow p-6">

        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold text-[#1A428A]">Editar activo</h1>

            <a href="{{ route('assets.show', $asset) }}"
               class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50">
                Volver
            </a>
        </div>

        @if ($errors->any())
            <div class="mt-6 p-4 border border-red-300 bg-red-50 rounded-lg">
                <div class="font-semibold text-red-700 mb-2">Revisa los siguientes campos:</div>
                <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('assets.update', $asset) }}" class="mt-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Nombre --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Nombre</label>
                    <input type="text"
                           name="name"
                           value="{{ old('name', $asset->name) }}"
                           class="mt-1 w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tipo --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Tipo</label>
                    <select name="asset_type_id"
                            class="mt-1 w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm"
                            required>
                        <option value="">-- Selecciona tipo --</option>
                        @foreach($assetTypes as $type)
                            <option value="{{ $type->id }}"
                                @selected(old('asset_type_id', $asset->asset_type_id) == $type->id)>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('asset_type_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Ubicación --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Ubicación</label>
                    <input type="text"
                           name="location"
                           value="{{ old('location', $asset->location) }}"
                           class="mt-1 w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm">
                    @error('location')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Código --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Código</label>
                    <input type="text"
                           name="code"
                           value="{{ old('code', $asset->code) }}"
                           class="mt-1 w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm">
                    <p class="mt-1 text-xs text-gray-500">
                        Si lo dejas igual, se mantiene. (Luego lo podemos hacer automático.)
                    </p>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Responsable (Select2) --}}
                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700">Responsable</label>

                    <select id="responsible_user_id"
                            name="responsible_user_id"
                            class="mt-1 w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm">
                        <option value="">-- Ninguno --</option>
                        @foreach($responsibles as $u)
                            <option value="{{ $u->id }}"
                                @selected((int) old('responsible_user_id', $asset->responsible_user_id) === (int) $u->id)>
                                {{ $u->name }} ({{ $u->email ?? '' }})
                            </option>
                        @endforeach
                    </select>

                    @error('responsible_user_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-8 flex items-center gap-3">
                <button type="submit"
                        class="px-6 py-2 rounded-md bg-[#1A428A] text-white font-semibold hover:bg-[#15356d] transition">
                    Actualizar
                </button>

                <a href="{{ route('assets.show', $asset) }}"
                   class="px-6 py-2 rounded-md bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    {{-- Select2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Select2 para buscar por nombre/correo
            $('#responsible_user_id').select2({
                width: '100%',
                placeholder: '-- Ninguno --',
                allowClear: true
            });
        });
    </script>
</x-layouts.vigia>