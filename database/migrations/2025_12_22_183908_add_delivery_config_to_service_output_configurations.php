<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add delivery configuration fields to service_output_configurations
 *
 * Enables per-category control over:
 * - Whether to wait for enrichment before delivery
 * - Timeout for enrichment wait
 * - TTL for presigned audio URLs
 *
 * @see /root/.claude/plans/zippy-skipping-lobster.md
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_output_configurations', function (Blueprint $table) {
            // Wait for enrichment before delivery
            $table->boolean('wait_for_enrichment')
                ->default(false)
                ->after('retry_on_failure')
                ->comment('If true, delay delivery until case is enriched with transcript/audio');

            // Timeout in seconds for waiting on enrichment
            $table->unsignedInteger('enrichment_timeout_seconds')
                ->default(180)
                ->after('wait_for_enrichment')
                ->comment('Max seconds to wait for enrichment before delivering with partial data');

            // TTL for presigned audio URLs in webhook payload
            $table->unsignedSmallInteger('audio_url_ttl_minutes')
                ->default(60)
                ->after('enrichment_timeout_seconds')
                ->comment('Minutes before presigned audio URL expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_output_configurations', function (Blueprint $table) {
            $table->dropColumn([
                'wait_for_enrichment',
                'enrichment_timeout_seconds',
                'audio_url_ttl_minutes',
            ]);
        });
    }
};
