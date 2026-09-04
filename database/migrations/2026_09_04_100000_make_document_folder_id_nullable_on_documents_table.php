<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['document_folder_id']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('document_folder_id')->nullable()->change();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('document_folder_id')
                ->references('id')->on('document_folders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['document_folder_id']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('document_folder_id')->nullable(false)->change();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('document_folder_id')
                ->references('id')->on('document_folders')
                ->cascadeOnDelete();
        });
    }
};
