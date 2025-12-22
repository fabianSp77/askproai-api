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
        Schema::table('service_output_configurations', function (Blueprint $table) {
            // Audio option in emails: 'none', 'link' (signed URL), 'attachment' (if <10MB)
            $table->string('email_audio_option', 20)->default('none')->after('email_subject_template');

            // Include transcript in backup emails
            $table->boolean('include_transcript')->default(true)->after('email_audio_option');

            // Include AI summary in backup emails
            $table->boolean('include_summary')->default(true)->after('include_transcript');
        });
    }

    public function down(): void
    {
        Schema::table('service_output_configurations', function (Blueprint $table) {
            $table->dropColumn(['email_audio_option', 'include_transcript', 'include_summary']);
        });
    }
};
