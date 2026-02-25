@if(($asset->status ?? null) === 'inactive')
  <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 flex items-start gap-3">
    <div class="mt-0.5">⚠️</div>
    <div class="flex-1">
      <div class="font-semibold text-amber-900">Activo desactivado</div>
      <div class="text-sm text-amber-900/80">
        Este activo está desactivado. No se permiten cambios (crear requirements, tareas o subir evidencia).
      </div>
    </div>

    {{-- Opcional: botón activar si existe ruta/permiso --}}
    @can('activate', $asset)
      <form method="POST" action="{{ route('assets.activate', $asset) }}">
        @csrf
        <button class="px-3 py-2 rounded-md bg-amber-600 text-white text-sm hover:bg-amber-700">
          Activar
        </button>
      </form>
    @endcan
  </div>
@endif