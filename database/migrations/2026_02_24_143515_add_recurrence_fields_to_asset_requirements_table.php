<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asset_requirements', function (Blueprint $table) {

            $table->unsignedInteger('recurrence_interval')
                ->nullable()
                ->after('due_date');

            $table->string('recurrence_unit')
                ->nullable()
                ->after('recurrence_interval'); // day|week|month|year

            $table->date('recurrence_anchor')
                ->nullable()
                ->after('recurrence_unit');

            $table->index(['recurrence_interval', 'recurrence_unit']);
        });
    }

    public function down(): void
    {
        Schema::table('asset_requirements', function (Blueprint $table) {

            $table->dropIndex(['recurrence_interval', 'recurrence_unit']);

            $table->dropColumn([
                'recurrence_interval',
                'recurrence_unit',
                'recurrence_anchor'
            ]);
        });
    }
};