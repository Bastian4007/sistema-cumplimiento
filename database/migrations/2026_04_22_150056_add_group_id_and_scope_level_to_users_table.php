<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('group_id')
                ->nullable()
                ->after('company_id')
                ->constrained('groups')
                ->nullOnDelete();

            $table->string('scope_level')
                ->default('company')
                ->after('group_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('scope_level');
            $table->dropConstrainedForeignId('group_id');
        });
    }
};