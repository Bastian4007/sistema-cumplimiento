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
        Schema::table('regulations', function (Blueprint $table) {
            // Diferencia un proceso/procedimiento normal de un documento que existe sobre todo
            // para ser referenciado como anexo de otros (formatos, tabuladores, etc. — ej.
            // F-SAV-001). No reemplaza la relación regulation_annexes (qué anexos trae CADA
            // documento) — esto solo clasifica qué ES este documento en sí.
            $table->boolean('is_annex')->default(false)->after('document_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regulations', function (Blueprint $table) {
            $table->dropColumn('is_annex');
        });
    }
};
