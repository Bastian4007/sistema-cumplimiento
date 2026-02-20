<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        // NO-OP: ya se aplicó previamente (o se manejará con migrate:fresh si hiciera falta)
    }

    public function down(): void
    {
        // NO-OP
    }
};
