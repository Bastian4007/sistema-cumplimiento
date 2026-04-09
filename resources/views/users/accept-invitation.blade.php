<x-layouts.vigia :title="'Activar cuenta'">
    <div class="bg-white rounded-xl shadow p-6 max-w-2xl">
        <h1 class="text-2xl font-semibold text-[#1A428A]">Activar cuenta</h1>

        <div class="mt-4 space-y-1 text-sm text-gray-600">
            <div><strong>Nombre:</strong> {{ $user->name }}</div>
            <div><strong>Correo:</strong> {{ $user->email }}</div>
            <div><strong>Empresa:</strong> {{ $user->company->name ?? '-' }}</div>
            <div><strong>Rol:</strong> {{ $user->role->name ?? '-' }}</div>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-300 bg-red-50 p-4">
                <ul class="list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('invitation.store', $user->invite_token) }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Contraseña
                </label>
                <input
                    type="password"
                    name="password"
                    class="w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm"
                    required
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Confirmar contraseña
                </label>
                <input
                    type="password"
                    name="password_confirmation"
                    class="w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm"
                    required
                >
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="px-4 py-2 rounded-md bg-[#1A428A] text-white font-semibold hover:bg-[#15356d]"
                >
                    Activar cuenta
                </button>
            </div>
        </form>
    </div>
</x-layouts.vigia>