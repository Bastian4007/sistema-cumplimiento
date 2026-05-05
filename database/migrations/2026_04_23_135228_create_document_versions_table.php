<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('version_number')->default(1);
            $table->boolean('is_current')->default(false);

            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->date('issued_at')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['document_id', 'is_current']);
            $table->index(['document_id', 'version_number']);
            $table->index(['valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};