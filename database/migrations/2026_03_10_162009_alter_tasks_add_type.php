<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('type')
                ->default('manual')
                ->after('description');

            $table->index(['asset_requirement_id', 'type']);
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['asset_requirement_id', 'type']);
            $table->dropIndex(['status', 'due_date']);
            $table->dropColumn('type');
        });
    }
};