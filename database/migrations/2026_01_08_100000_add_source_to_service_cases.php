<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add source field to service_cases table for explicit origin tracking.
     *
     * ServiceNow-compatible sources:
     * - voice: Telefonanruf (Retell AI)
     * - email: E-Mail Import
     * - web: Web-Formular/Portal
     * - api: API-Erstellung
     * - manual: Manuell erfasst
     * - chat: Chat/Virtual Agent
     * - callback: Rückruf-Wunsch
     * - walk-in: Vor Ort
     */
    public function up(): void
    {
        Schema::table('service_cases', function (Blueprint $table) {
            $table->string('source', 30)
                ->default('voice')
                ->after('enrichment_status')
                ->comment('Case origin: voice, email, web, api, manual, chat, callback, walk-in');

            $table->index('source', 'idx_service_cases_source');
        });

        // All existing cases came from voice AI, so default is correct
        // No explicit backfill needed since default handles it
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_cases', function (Blueprint $table) {
            $table->dropIndex('idx_service_cases_source');
            $table->dropColumn('source');
        });
    }
};
