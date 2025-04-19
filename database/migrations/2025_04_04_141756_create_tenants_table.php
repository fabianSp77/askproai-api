<?php // database/migrations/2025_04_04_141756_create_tenants_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Anzeigename des Mandanten'); // Z.B. Firmenname

            // --- Entscheidung Tenant-ID (API-Key als Beispiel) ---
            $table->string('api_key')->unique()->comment('Eindeutiger API Key für diesen Tenant');
            // Alternative: $table->string('retell_agent_id')->unique()->nullable()->comment('Eindeutige Retell Agent ID');
            // --- Ende Entscheidung ---

            // --- Entscheidung Cal.com (Nur wenn JA in Phase 0 entschieden) ---
            // $table->text('calcom_api_key_encrypted')->nullable()->comment('Verschlüsselter Cal.com API Key'); // Verschlüsselung wichtig!
            // $table->unsignedBigInteger('calcom_event_type_id')->nullable()->comment('Standard Cal.com Event Type ID');
            // --- Ende Entscheidung ---

            $table->boolean('is_active')->default(true)->comment('Mandant aktiv/inaktiv');
            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
