<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_type_requirement_templates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_type_id')->constrained('asset_types')->cascadeOnDelete();
            $table->foreignId('requirement_template_id')->constrained('requirement_templates')->cascadeOnDelete();

            $table->boolean('applies_to_requirements')->default(true);
            $table->boolean('applies_to_obligations')->default(false);

            $table->string('requirement_type')->nullable();
            $table->unsignedInteger('default_days')->default(365);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->unique(['company_id', 'asset_type_id', 'requirement_template_id'], 'atr_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_type_requirement_templates');
    }
};
