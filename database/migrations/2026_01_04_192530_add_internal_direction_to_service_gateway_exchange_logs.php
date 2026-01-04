<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'internal' to direction ENUM.
     *
     * The direction column tracks communication flow:
     * - outbound: We send to external systems (webhooks, APIs)
     * - inbound: External systems send to us (webhooks received)
     * - internal: Internal processing events (enrichment, audio processing)
     *
     * This fixes: SQLSTATE[01000]: Warning: 1265 Data truncated for column 'direction'
     */
    public function up(): void
    {
        // MySQL requires ALTER COLUMN to modify ENUM values
        DB::statement("ALTER TABLE service_gateway_exchange_logs MODIFY COLUMN direction ENUM('outbound', 'inbound', 'internal') NOT NULL DEFAULT 'outbound'");
    }

    /**
     * Remove 'internal' from direction ENUM.
     *
     * WARNING: This will fail if any rows have direction='internal'.
     * Delete those rows first or update them to 'outbound'.
     */
    public function down(): void
    {
        // First update any 'internal' rows to 'outbound' to prevent data loss
        DB::statement("UPDATE service_gateway_exchange_logs SET direction = 'outbound' WHERE direction = 'internal'");

        // Then revert the ENUM
        DB::statement("ALTER TABLE service_gateway_exchange_logs MODIFY COLUMN direction ENUM('outbound', 'inbound') NOT NULL DEFAULT 'outbound'");
    }
};
