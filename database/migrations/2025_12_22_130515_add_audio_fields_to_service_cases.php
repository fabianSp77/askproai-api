<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add audio storage fields to service_cases table.
 *
 * Stores S3 object key (NOT URL!) and expiration date for 60-day retention.
 * Recording URLs from external providers (Retell) are NEVER persisted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_cases', function (Blueprint $table) {
            // S3 object key (e.g., "audio/company_1/case_123/2025-12-22_130515.mp3")
            // NEVER store external recording URLs here!
            $table->string('audio_object_key')->nullable()->after('output_status');

            // Auto-expiration date (60 days after creation)
            $table->timestamp('audio_expires_at')->nullable()->after('audio_object_key');

            // Index for cleanup job
            $table->index(['audio_expires_at'], 'service_cases_audio_expires_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('service_cases', function (Blueprint $table) {
            $table->dropIndex('service_cases_audio_expires_at_index');
            $table->dropColumn(['audio_object_key', 'audio_expires_at']);
        });
    }
};
