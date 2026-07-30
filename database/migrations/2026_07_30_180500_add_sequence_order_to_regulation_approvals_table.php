<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regulation_approvals', function (Blueprint $table) {
            // Orden en que se agregó cada aprobador dentro de su mismo paso — cuando un paso
            // "requires_all" tiene varios aprobadores, deben aprobar uno a la vez en este orden
            // (no en paralelo): solo el de sequence_order más bajo arranca en status "pending",
            // los demás arrancan en "waiting" hasta que le toque su turno.
            $table->unsignedTinyInteger('sequence_order')->default(0)->after('requires_all');
        });
    }

    public function down(): void
    {
        Schema::table('regulation_approvals', function (Blueprint $table) {
            $table->dropColumn('sequence_order');
        });
    }
};
