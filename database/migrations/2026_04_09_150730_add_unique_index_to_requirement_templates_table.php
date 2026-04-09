<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requirement_templates', function (Blueprint $table) {
            $table->unique(
                ['name', 'asset_type_id', 'compliance_scope'],
                'requirement_templates_unique_name_asset_scope'
            );
        });
    }

    public function down(): void
    {
        Schema::table('requirement_templates', function (Blueprint $table) {
            $table->dropUnique('requirement_templates_unique_name_asset_scope');
        });
    }
};