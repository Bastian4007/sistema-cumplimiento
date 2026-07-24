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
            // HTML exacto (ya con el formato fijo aplicado) usado para construir el cuerpo del
            // .docx de esta versión. Permite que "Ver" muestre lo mismo que "Descargar" sin
            // tener que reconvertir el .docx a HTML (con PhpWord) y arriesgar diferencias.
            // Null en versiones subidas manualmente (no generadas/editadas dentro del sistema).
            $table->longText('body_html')->nullable()->after('change_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regulation_versions', function (Blueprint $table) {
            $table->dropColumn('body_html');
        });
    }
};
