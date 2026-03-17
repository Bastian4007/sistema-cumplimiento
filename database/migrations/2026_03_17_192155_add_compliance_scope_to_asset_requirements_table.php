<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_requirements', function (Blueprint $table) {
            $table->string('compliance_scope')
                ->default('project')
                ->after('type');
        });

        DB::table('asset_requirements')
            ->whereNull('compliance_scope')
            ->update(['compliance_scope' => 'project']);
    }

    public function down(): void
    {
        Schema::table('asset_requirements', function (Blueprint $table) {
            $table->dropColumn('compliance_scope');
        });
    }
};