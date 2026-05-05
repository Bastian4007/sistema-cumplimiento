<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_folders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('group_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('document_folders')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('level', 50); // folder | category
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['group_id', 'company_id']);
            $table->index(['parent_id']);
            $table->index(['level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_folders');
    }
};