<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('requirement_templates', function (Blueprint $table) {
            $table->string('priority')->nullable()->after('subtype');
            $table->string('responsible_area')->nullable()->after('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requirement_templates', function (Blueprint $table) {
            $table->dropColumn(['priority', 'responsible_area']);
        });
    }
};
