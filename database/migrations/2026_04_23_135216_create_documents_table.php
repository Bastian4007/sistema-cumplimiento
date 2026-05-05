<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('group_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('document_folder_id')
                ->constrained('document_folders')
                ->cascadeOnDelete();

            $table->string('name'); // nombre esperado del documento
            $table->string('document_type')->nullable(); // legal, técnico, permiso, contrato, etc.
            $table->string('reference')->nullable(); // oficio, folio, número, etc.
            $table->text('authorized_access_notes')->nullable();
            $table->string('responsible_name')->nullable();

            $table->date('issued_at')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();

            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['group_id', 'company_id']);
            $table->index(['document_folder_id']);
            $table->index(['valid_until']);
            $table->index(['is_required', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};