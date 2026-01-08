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
        // Skip if table doesn't exist
        if (!Schema::hasTable('service_output_configurations')) {
            return;
        }

        Schema::table('service_output_configurations', function (Blueprint $table) {
            $afterColumn = Schema::hasColumn('service_output_configurations', 'retry_on_failure')
                ? 'retry_on_failure' : 'id';

            // Wait for enrichment before delivery
            if (!Schema::hasColumn('service_output_configurations', 'wait_for_enrichment')) {
                $table->boolean('wait_for_enrichment')
                    ->default(false)
                    ->after($afterColumn)
                    ->comment('If true, delay delivery until case is enriched with transcript/audio');
            }

            // Timeout in seconds for waiting on enrichment
            if (!Schema::hasColumn('service_output_configurations', 'enrichment_timeout_seconds')) {
                $col = Schema::hasColumn('service_output_configurations', 'wait_for_enrichment')
                    ? 'wait_for_enrichment' : $afterColumn;
                $table->unsignedInteger('enrichment_timeout_seconds')
                    ->default(180)
                    ->after($col)
                    ->comment('Max seconds to wait for enrichment before delivering with partial data');
            }

            // TTL for presigned audio URLs in webhook payload
            if (!Schema::hasColumn('service_output_configurations', 'audio_url_ttl_minutes')) {
                $col = Schema::hasColumn('service_output_configurations', 'enrichment_timeout_seconds')
                    ? 'enrichment_timeout_seconds' : $afterColumn;
                $table->unsignedSmallInteger('audio_url_ttl_minutes')
                    ->default(60)
                    ->after($col)
                    ->comment('Minutes before presigned audio URL expires');
            }
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
