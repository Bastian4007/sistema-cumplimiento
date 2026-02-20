<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Crear Activo
        </h2>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto">
        <div class="bg-white shadow sm:rounded-lg p-6">

            @if ($errors->any())
                <div class="mb-4 p-4 border border-red-300 bg-red-50 rounded">
                    <ul class="list-disc list-inside text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('assets.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700">Tipo</label>
                    <select name="asset_type_id" class="w-full border rounded p-2" required>
                        <option value="">-- Selecciona tipo --</option>
                        @foreach($assetTypes as $type)
                            <option value="{{ $type->id }}" @selected(old('asset_type_id') == $type->id)>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input type="text" name="name"
                           value="{{ old('name') }}"
                           class="w-full border rounded p-2"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Código</label>
                    <input type="text" name="code"
                           value="{{ old('code') }}"
                           class="w-full border rounded p-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Ubicación</label>
                    <input type="text" name="location"
                           value="{{ old('location') }}"
                           class="w-full border rounded p-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Responsable</label>
                    <select name="responsible_user_id" class="w-full border rounded p-2">
                        <option value="">-- Ninguno --</option>
                        @foreach($responsibles as $user)
                            <option value="{{ $user->id }}" @selected(old('responsible_user_id') == $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-2 flex gap-3">
                    <button type="submit" style="padding:10px 16px; background:#16a34a; color:white; border-radius:6px;">
                        Guardar
                    </button>

                    <a href="{{ route('assets.index') }}" class="px-4 py-2 bg-gray-200 rounded">
                        Cancelar
                    </a>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>