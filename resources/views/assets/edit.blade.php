<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Activo
        </h2>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto">
        <div class="bg-white shadow sm:rounded-lg p-6">

            <form method="POST" action="{{ route('assets.update', $asset) }}">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label>Tipo</label>
                    <select name="asset_type_id" class="w-full border rounded p-2">
                        @foreach($assetTypes as $type)
                            <option value="{{ $type->id }}"
                                @selected($asset->asset_type_id == $type->id)>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label>Nombre</label>
                    <input type="text" name="name"
                           value="{{ $asset->name }}"
                           class="w-full border rounded p-2"
                           required>
                </div>

                <div class="mb-4">
                    <label>Código</label>
                    <input type="text" name="code"
                           value="{{ $asset->code }}"
                           class="w-full border rounded p-2">
                </div>

                <div class="mb-4">
                    <label>Ubicación</label>
                    <input type="text" name="location"
                           value="{{ $asset->location }}"
                           class="w-full border rounded p-2">
                </div>

                <div class="mb-4">
                    <label>Responsable</label>
                    <select name="responsible_user_id" class="w-full border rounded p-2">
                        <option value="">-- Ninguno --</option>
                        @foreach($responsibles as $user)
                            <option value="{{ $user->id }}"
                                @selected($asset->responsible_user_id == $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button class="px-4 py-2 bg-blue-600 text-white rounded">
                    Actualizar
                </button>

            </form>
        </div>
    </div>
</x-app-layout>
