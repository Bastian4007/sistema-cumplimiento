<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asset_requirements', function (Blueprint $table) {
            $table->foreignId('current_document_id')
                ->nullable()
                ->after('completed_at')
                ->constrained('asset_requirement_documents')
                ->nullOnDelete();

            $table->index(['company_id', 'status']);
            $table->index(['asset_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('asset_requirements', function (Blueprint $table) {
            $table->dropForeign(['current_document_id']);
            $table->dropIndex(['company_id', 'status']);
            $table->dropIndex(['asset_id', 'status']);
            $table->dropColumn('current_document_id');
        });
    }
};