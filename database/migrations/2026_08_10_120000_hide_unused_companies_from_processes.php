<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const HIDDEN_COMPANIES = ['SOM', 'Terrenos Vigia', 'MINIMA REAL ESTATE', 'GRUPO AVIDAM'];

    public function up(): void
    {
        DB::table('companies')
            ->whereIn('name', self::HIDDEN_COMPANIES)
            ->update(['show_in_processes' => false]);
    }

    public function down(): void
    {
        DB::table('companies')
            ->whereIn('name', self::HIDDEN_COMPANIES)
            ->update(['show_in_processes' => true]);
    }
};
