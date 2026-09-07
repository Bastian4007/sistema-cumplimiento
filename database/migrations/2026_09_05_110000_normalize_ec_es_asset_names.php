<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El tipo de activo (EC/ES) quedaba escrito a mano dentro de "name" al importar
     * por CSV (ver EC_seeder.php / ES_seeder.php), causando prefijos duplicados
     * ("EC EC ...") en unos activos y ausentes en otros (los creados/editados a
     * mano desde la pantalla). Ahora el prefijo se calcula al mostrar
     * (Asset::getDisplayNameAttribute()), así que aquí se limpia el dato guardado.
     */
    public function up(): void
    {
        $typeIds = DB::table('asset_types')->whereIn('name', ['EC', 'ES'])->pluck('id', 'name');

        foreach ($typeIds as $typeName => $typeId) {
            DB::table('assets')
                ->where('asset_type_id', $typeId)
                ->select('id', 'name')
                ->orderBy('id')
                ->get()
                ->each(function ($asset) use ($typeName) {
                    $clean = trim(preg_replace('/^(?:' . preg_quote($typeName, '/') . '\s+)+/i', '', $asset->name));

                    if ($clean !== '' && $clean !== $asset->name) {
                        DB::table('assets')->where('id', $asset->id)->update(['name' => $clean]);
                    }
                });
        }
    }

    public function down(): void
    {
        // Irreversible: el texto original con el prefijo no se conserva.
    }
};
