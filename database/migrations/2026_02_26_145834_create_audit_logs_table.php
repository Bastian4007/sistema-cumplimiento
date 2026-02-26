<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Multiempresa
            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            // Usuario que ejecuta la acción
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('action', 80)->index();

            // Polymorphic target
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->index(['auditable_type', 'auditable_id']);

            // Contexto jerárquico (para queries rápidas)
            $table->foreignId('asset_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('requirement_id')
                ->nullable()
                ->constrained('asset_requirements')
                ->cascadeOnDelete();

            $table->foreignId('task_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
