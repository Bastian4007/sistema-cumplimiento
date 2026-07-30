<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renombra la empresa "INMUEBLES" a "DAVAL" — es un cambio de nombre visible únicamente,
 * no una fusión: ya existe una empresa "DAVAL" separada (grupo DAVAL) y se deja intacta.
 * Quedan dos filas distintas con el mismo nombre en grupos distintos, a propósito.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('companies')->where('name', 'INMUEBLES')->update(['name' => 'DAVAL']);
    }

    public function down(): void
    {
        DB::table('companies')->where('name', 'DAVAL')->where('group_id', function ($query) {
            $query->select('id')->from('groups')->where('name', 'VIGIA')->limit(1);
        })->update(['name' => 'INMUEBLES']);
    }
};
