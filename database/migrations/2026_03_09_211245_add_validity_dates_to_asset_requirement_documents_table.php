<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_requirement_documents', function (Blueprint $table) {
            $table->date('issued_at')->nullable()->after('original_name');
            $table->date('expires_at')->nullable()->after('issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('asset_requirement_documents', function (Blueprint $table) {
            $table->dropColumn(['issued_at', 'expires_at']);
        });
    }
};