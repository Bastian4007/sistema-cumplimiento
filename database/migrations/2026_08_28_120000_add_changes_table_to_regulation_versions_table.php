<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('regulation_versions', function (Blueprint $table) {
            // Tabla comparativa generada con IA: {rows: [{modificacion, texto_incorporado}]}.
            // Null si la edición no traía justificación, o si la IA no pudo generarla — nunca
            // bloquea el guardado de la versión.
            $table->json('changes_table')->nullable()->after('change_justification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regulation_versions', function (Blueprint $table) {
            $table->dropColumn('changes_table');
        });
    }
};
