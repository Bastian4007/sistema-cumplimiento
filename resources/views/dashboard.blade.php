<x-layouts.vigia :title="'Tablero'">
    <x-slot name="breadcrumb">
        <span class="text-gray-700 font-medium">Tablero</span>
    </x-slot>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold text-[#1A428A]">Tablero de cumplimiento</h1>

            <div class="w-64">
                <select class="w-full rounded-md border-gray-300 focus:border-blue-600 focus:ring-blue-600 text-sm">
                    <option>Todas las empresas</option>
                </select>
            </div>
        </div>

        {{-- Cards --}}
        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white border rounded-lg shadow-sm p-4">
                <div class="text-sm font-semibold text-[#1A428A]">Activos</div>
                <div class="mt-2 text-2xl font-bold text-gray-800">{{ $stats['assets'] ?? 0 }}</div>
            </div>

            <div class="bg-white border rounded-lg shadow-sm p-4">
                <div class="text-sm font-semibold text-[#1A428A]">Tareas</div>
                <div class="mt-2 text-2xl font-bold text-gray-800">{{ $stats['tasks'] ?? 0 }}</div>
            </div>

            <div class="bg-[#FFB529] rounded-lg shadow-sm p-4 text-white">
                <div class="text-sm font-semibold">Próximas a vencer</div>
                <div class="mt-2 text-2xl font-bold">{{ $stats['due_soon'] ?? 0 }}</div>
            </div>

            <div class="bg-[#DB0000] rounded-lg shadow-sm p-4 text-white">
                <div class="text-sm font-semibold">Vencidas</div>
                <div class="mt-2 text-2xl font-bold">{{ $stats['overdue'] ?? 0 }}</div>
            </div>
        </div>

        {{-- Lista --}}
        <div class="mt-8">
            <div class="text-sm font-semibold text-[#FFB529] mb-3">Próximos a vencer</div>

            <div class="border rounded-lg overflow-hidden">
                {{-- Aquí luego metemos el loop real --}}
                <div class="p-4 border-b">
                    <div class="font-semibold text-gray-800">Bitácora de mantenimiento</div>
                    <div class="text-xs text-gray-500">Activo: TEST1 · Tipo: ATQ</div>
                </div>
                <div class="p-4 border-b">
                    <div class="font-semibold text-gray-800">Licencia de operación</div>
                    <div class="text-xs text-gray-500">Activo: TEST1 · Tipo: ATQ</div>
                </div>
                <div class="p-4">
                    <div class="font-semibold text-gray-800">Permiso ambiental anual</div>
                    <div class="text-xs text-gray-500">Activo: Registro ante el SAT · Tipo: Muelles</div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.vigia>