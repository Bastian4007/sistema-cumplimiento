<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('requires_document')->default(true)->change();
        });

        DB::table('tasks')->update(['requires_document' => true]);
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('requires_document')->default(false)->change();
        });
    }
};
