<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add audio and transcript configuration to service_output_configurations.
 *
 * Allows per-company configuration of:
 * - email_audio_option: none | link | attachment
 * - include_transcript: whether to include call transcript in emails
 * - include_summary: whether to include AI summary in emails
 */
return new class extends Migration
{
    public function up(): void
    {
        // Skip if table doesn't exist
        if (!Schema::hasTable('service_output_configurations')) {
            return;
        }

        Schema::table('service_output_configurations', function (Blueprint $table) {
            // Determine placement column (fallback if email_subject_template doesn't exist)
            $afterColumn = Schema::hasColumn('service_output_configurations', 'email_subject_template')
                ? 'email_subject_template'
                : 'id';

            // Audio option in emails: 'none', 'link' (signed URL), 'attachment' (if <10MB)
            if (!Schema::hasColumn('service_output_configurations', 'email_audio_option')) {
                $table->string('email_audio_option', 20)->default('none')->after($afterColumn);
            }

            // Include transcript in backup emails
            if (!Schema::hasColumn('service_output_configurations', 'include_transcript')) {
                $afterCol = Schema::hasColumn('service_output_configurations', 'email_audio_option')
                    ? 'email_audio_option' : $afterColumn;
                $table->boolean('include_transcript')->default(true)->after($afterCol);
            }

            // Include AI summary in backup emails
            if (!Schema::hasColumn('service_output_configurations', 'include_summary')) {
                $afterCol = Schema::hasColumn('service_output_configurations', 'include_transcript')
                    ? 'include_transcript' : $afterColumn;
                $table->boolean('include_summary')->default(true)->after($afterCol);
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_output_configurations', function (Blueprint $table) {
            $table->dropColumn(['email_audio_option', 'include_transcript', 'include_summary']);
        });
    }
};
