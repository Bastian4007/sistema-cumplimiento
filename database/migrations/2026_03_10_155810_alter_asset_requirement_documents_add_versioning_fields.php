<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asset_requirement_documents', function (Blueprint $table) {
            $table->timestamp('uploaded_at')->nullable()->after('expires_at');

            $table->boolean('is_current')->default(false)->after('uploaded_at');
            $table->string('status')->default('active')->after('is_current');
            $table->unsignedInteger('version_number')->default(1)->after('status');

            $table->foreignId('replaced_by_document_id')
                ->nullable()
                ->after('version_number')
                ->constrained('asset_requirement_documents')
                ->nullOnDelete();

            $table->text('notes')->nullable()->after('replaced_by_document_id');

            $table->index(['asset_requirement_id', 'is_current']);
            $table->index(['asset_requirement_id', 'version_number']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('asset_requirement_documents', function (Blueprint $table) {
            $table->dropForeign(['replaced_by_document_id']);

            $table->dropIndex(['asset_requirement_id', 'is_current']);
            $table->dropIndex(['asset_requirement_id', 'version_number']);
            $table->dropIndex(['company_id', 'status']);

            $table->dropColumn([
                'uploaded_at',
                'is_current',
                'status',
                'version_number',
                'replaced_by_document_id',
                'notes',
            ]);
        });
    }
};