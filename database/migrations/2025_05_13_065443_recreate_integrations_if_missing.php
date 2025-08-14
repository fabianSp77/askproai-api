<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nur anlegen, wenn die Tabelle wirklich fehlt
        if (! Schema::hasTable('integrations')) {
            Schema::create('integrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kunde_id')
                    ->constrained('kunden')
                    ->cascadeOnDelete();
                $table->string('system');
                $table->json('zugangsdaten');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Nur wieder löschen, falls wir sie gerade angelegt haben
        if (Schema::hasTable('integrations')) {
            Schema::dropIfExists('integrations');
        }
    }
};
