<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $obsoleteTypes = ['ATQ', 'Documentos', 'Muelles'];

        $typeIds = DB::table('asset_types')
            ->whereIn('name', $obsoleteTypes)
            ->pluck('id');

        if ($typeIds->isEmpty()) {
            return;
        }

        // 1. Borrar assets de prueba ligados a esos tipos
        DB::table('assets')
            ->whereIn('asset_type_id', $typeIds)
            ->delete();

        // 2. Borrar tipos obsoletos
        DB::table('asset_types')
            ->whereIn('id', $typeIds)
            ->delete();
    }

    public function down(): void
    {
        // No revertimos porque son datos de prueba eliminados intencionalmente.
    }
};