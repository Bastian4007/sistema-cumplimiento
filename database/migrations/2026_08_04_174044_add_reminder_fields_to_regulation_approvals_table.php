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
        Schema::table('regulation_approvals', function (Blueprint $table) {
            // Cuántos recordatorios de "sigue pendiente" ya se mandaron (tope de 3, cada 7 días
            // desde que quedó en pending) y cuándo se mandó el último — evita reenviar de más si
            // el comando corre varias veces antes de cumplirse el siguiente umbral de 7 días.
            $table->unsignedTinyInteger('reminders_sent')->default(0)->after('decided_at');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('reminders_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regulation_approvals', function (Blueprint $table) {
            $table->dropColumn(['reminders_sent', 'last_reminder_sent_at']);
        });
    }
};
