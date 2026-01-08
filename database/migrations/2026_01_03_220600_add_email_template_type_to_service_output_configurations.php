<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add explicit email_template_type field to replace magic string detection.
 *
 * Template types:
 * - standard: Default ServiceCaseNotification (internal team emails)
 * - technical: BackupNotificationMail technical mode (for data backup services)
 * - admin: BackupNotificationMail admin mode (IT support with JSON attachment)
 * - custom: CustomTemplateEmail (uses email_body_template field)
 *
 * This migration also backfills existing configs based on their current names.
 *
 * @see App\Services\ServiceGateway\OutputHandlers\EmailOutputHandler
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add the email_template_type column (nullable for backward compatibility)
        // NULL = use legacy magic string detection
        // Explicit value = use the specified template type
        Schema::table('service_output_configurations', function (Blueprint $table) {
            $table->enum('email_template_type', ['standard', 'technical', 'admin', 'custom'])
                ->nullable()
                ->after('email_recipients')
                ->comment('Email template type: standard, technical (backup), admin (IT support), custom. NULL = legacy detection.');
        });

        // Backfill existing configs based on name patterns
        // This preserves current behavior for existing configs
        $this->backfillTemplateTypes();
    }

    /**
     * Backfill email_template_type based on existing config names.
     * Matches the same logic previously used in EmailOutputHandler.
     */
    private function backfillTemplateTypes(): void
    {
        // Technical: Visionary Data configs
        DB::table('service_output_configurations')
            ->whereRaw("LOWER(name) LIKE '%visionary%'")
            ->update(['email_template_type' => 'technical']);

        // Admin: IT-Systemhaus and support configs
        $adminKeywords = [
            'systemhaus',
            'it-support',
            'netzwerk support',
            'm365 support',
            'hardware support',
            'drucker support',
            'software support',
            'server support',
            'telefonie support',
            'security incident',
            'zugangsverwaltung',
        ];

        foreach ($adminKeywords as $keyword) {
            DB::table('service_output_configurations')
                ->whereRaw("LOWER(name) LIKE ?", ['%' . $keyword . '%'])
                ->where('email_template_type', 'standard') // Only update if still default
                ->update(['email_template_type' => 'admin']);
        }

        // Custom: Configs with email_body_template set
        DB::table('service_output_configurations')
            ->whereNotNull('email_body_template')
            ->where('email_body_template', '!=', '')
            ->update(['email_template_type' => 'custom']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_output_configurations', function (Blueprint $table) {
            $table->dropColumn('email_template_type');
        });
    }
};
