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
            // Motivo de negocio del cambio (por qué), separado de change_description (qué cambió).
            // Nullable a nivel de columna para no romper versiones históricas; se exige en el
            // formulario/validación para cualquier versión nueva a partir de ahora.
            $table->text('change_justification')->nullable()->after('change_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regulation_versions', function (Blueprint $table) {
            $table->dropColumn('change_justification');
        });
    }
};
