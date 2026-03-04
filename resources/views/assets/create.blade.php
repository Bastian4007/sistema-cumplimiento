{{-- resources/views/assets/create.blade.php --}}
<x-layouts.vigia :title="'Crear un activo'">
    <x-slot name="breadcrumb">
        <a href="{{ route('assets.index') }}" class="text-gray-600 hover:underline">Activos y Actividades</a>
        <span class="text-gray-400">›</span>
        <span class="text-gray-700 font-medium">Crear un activo</span>
    </x-slot>

    {{-- Select2 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <div class="bg-white rounded-xl shadow p-6">
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

            {{-- Grid como el mock --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-3xl">
                {{-- Nombre --}}
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

                {{-- Responsable (Select2) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Selecciona un responsable</label>

                    <select
                        id="responsible_user_id"
                        name="responsible_user_id"
                        class="mt-1 w-full text-sm"
                    >
                        <option value="">-- Ninguno --</option>
                        @foreach($responsibles as $u)
                            <option
                                value="{{ $u->id }}"
                                @selected((string) old('responsible_user_id') === (string) $u->id)
                            >
                                {{ $u->name }} ({{ $u->email }})
                            </option>
                        @endforeach
                    </select>

                    @error('responsible_user_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Ubicación --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Selecciona ubicación</label>
                    <input
                        type="text"
                        name="location"
                        value="{{ old('location') }}"
                        class="mt-1 w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm"
                    />
                    @error('location')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tipo --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Selecciona un tipo</label>
                    <select
                        name="asset_type_id"
                        class="mt-1 w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm"
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

                {{-- Código (si lo quieres en el diseño, lo dejo abajo) --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Código (opcional)</label>
                    <input
                        type="text"
                        name="code"
                        value="{{ old('code') }}"
                        class="mt-1 w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm"
                    />
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Botón principal como el mock --}}
            <div class="mt-8 max-w-3xl">
                <button
                    type="submit"
                    class="w-full md:w-64 px-6 py-3 rounded-md bg-[#1A428A] text-white font-semibold hover:bg-[#15356d] transition"
                >
                    Crear un activo
                </button>

                <a
                    href="{{ route('assets.index') }}"
                    class="ml-3 inline-block px-6 py-3 rounded-md bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition"
                >
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    {{-- Select2 JS (requiere jQuery) --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            $('#responsible_user_id').select2({
                width: '100%',
                placeholder: 'Selecciona un responsable',
                allowClear: true
            });

            // Ajuste visual para que el select2 se parezca al input Tailwind
            const $container = $('#responsible_user_id').next('.select2').find('.select2-selection');
            $container.addClass('rounded-md border border-gray-300');
        });
    </script>
</x-layouts.vigia>