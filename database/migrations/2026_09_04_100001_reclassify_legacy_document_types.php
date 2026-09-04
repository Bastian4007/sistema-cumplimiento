<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('documents')
            ->whereRaw('LOWER(document_type) IN (?, ?)', ['original', 'copia'])
            ->update(['document_type' => 'Otros']);
    }

    public function down(): void
    {
        // Irreversible: el valor original (Original/Copia) no se conserva.
    }
};
