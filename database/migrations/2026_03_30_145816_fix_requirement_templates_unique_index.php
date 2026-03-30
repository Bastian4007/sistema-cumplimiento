<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requirement_templates', function (Blueprint $table) {
            $table->dropUnique('requirement_templates_name_unique');

            $table->unique(
                ['name', 'asset_type_id', 'compliance_scope'],
                'requirement_templates_name_asset_type_scope_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('requirement_templates', function (Blueprint $table) {
            $table->dropUnique('requirement_templates_name_asset_type_scope_unique');

            $table->unique('name', 'requirement_templates_name_unique');
        });
    }
};
