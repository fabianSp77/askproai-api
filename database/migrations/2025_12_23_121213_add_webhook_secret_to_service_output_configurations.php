<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds webhook security and configuration fields:
     * - webhook_secret: HMAC-SHA256 signing key (encrypted at model level)
     * - webhook_enabled: Toggle to enable/disable webhook delivery
     * - webhook_include_transcript: Include call transcript in webhook payload
     */
    public function up(): void
    {
        // Skip if table doesn't exist
        if (!Schema::hasTable('service_output_configurations')) {
            return;
        }

        Schema::table('service_output_configurations', function (Blueprint $table) {
            $afterColumn = Schema::hasColumn('service_output_configurations', 'webhook_payload_template')
                ? 'webhook_payload_template' : 'id';

            // HMAC-SHA256 secret for webhook signing (encrypted via model cast)
            if (!Schema::hasColumn('service_output_configurations', 'webhook_secret')) {
                $table->text('webhook_secret')->nullable()->after($afterColumn);
            }

            // Toggle to quickly enable/disable webhook without changing other settings
            if (!Schema::hasColumn('service_output_configurations', 'webhook_enabled')) {
                $col = Schema::hasColumn('service_output_configurations', 'webhook_secret')
                    ? 'webhook_secret' : $afterColumn;
                $table->boolean('webhook_enabled')->default(true)->after($col);
            }

            // Include full call transcript in webhook payload
            if (!Schema::hasColumn('service_output_configurations', 'webhook_include_transcript')) {
                $col = Schema::hasColumn('service_output_configurations', 'webhook_enabled')
                    ? 'webhook_enabled' : $afterColumn;
                $table->boolean('webhook_include_transcript')->default(false)->after($col);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_output_configurations', function (Blueprint $table) {
            $table->dropColumn(['webhook_secret', 'webhook_enabled', 'webhook_include_transcript']);
        });
    }
};
