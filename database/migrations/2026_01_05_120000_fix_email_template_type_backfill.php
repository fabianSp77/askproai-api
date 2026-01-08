<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fix email_template_type backfill and make column NOT NULL.
 *
 * The original migration (2026_01_03_220600) had a bug:
 * It checked for email_template_type = 'standard' when backfilling admin keywords,
 * but the column was created as NULL, so the condition never matched.
 *
 * This migration:
 * 1. Properly backfills NULL configs with admin keywords → 'admin'
 * 2. Properly backfills NULL configs with 'visionary' → 'technical'
 * 3. Sets remaining NULLs → 'standard'
 * 4. Makes column NOT NULL with default 'standard'
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
        // Step 1: Log current state before fix
        $nullCount = DB::table('service_output_configurations')
            ->whereNull('email_template_type')
            ->count();

        Log::info("[Migration] fix_email_template_type_backfill: Found {$nullCount} configs with NULL email_template_type");

        // Step 2: Backfill technical (Visionary Data)
        $visionaryUpdated = DB::table('service_output_configurations')
            ->whereNull('email_template_type')
            ->whereRaw("LOWER(name) LIKE '%visionary%'")
            ->update(['email_template_type' => 'technical']);

        Log::info("[Migration] Updated {$visionaryUpdated} configs to 'technical' (Visionary)");

        // Step 3: Backfill admin (IT-Systemhaus keywords)
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

        $adminUpdated = 0;
        foreach ($adminKeywords as $keyword) {
            $updated = DB::table('service_output_configurations')
                ->whereNull('email_template_type')
                ->whereRaw("LOWER(name) LIKE ?", ['%' . $keyword . '%'])
                ->update(['email_template_type' => 'admin']);
            $adminUpdated += $updated;
        }

        Log::info("[Migration] Updated {$adminUpdated} configs to 'admin' (IT-Systemhaus keywords)");

        // Step 4: Backfill custom (has email_body_template)
        $customUpdated = DB::table('service_output_configurations')
            ->whereNull('email_template_type')
            ->whereNotNull('email_body_template')
            ->where('email_body_template', '!=', '')
            ->update(['email_template_type' => 'custom']);

        Log::info("[Migration] Updated {$customUpdated} configs to 'custom' (has email_body_template)");

        // Step 5: Set remaining NULLs to 'standard'
        $standardUpdated = DB::table('service_output_configurations')
            ->whereNull('email_template_type')
            ->update(['email_template_type' => 'standard']);

        Log::info("[Migration] Updated {$standardUpdated} configs to 'standard' (remaining NULLs)");

        // Step 6: Make column NOT NULL with default
        // Note: MySQL requires dropping and recreating ENUM columns to change nullability
        DB::statement("ALTER TABLE service_output_configurations
            MODIFY COLUMN email_template_type
            ENUM('standard', 'technical', 'admin', 'custom')
            NOT NULL
            DEFAULT 'standard'
            COMMENT 'Email template type: standard, technical (backup), admin (IT support), custom'");

        Log::info("[Migration] Made email_template_type NOT NULL with default 'standard'");

        // Step 7: Verify no NULLs remain
        $remainingNulls = DB::table('service_output_configurations')
            ->whereNull('email_template_type')
            ->count();

        if ($remainingNulls > 0) {
            Log::error("[Migration] WARNING: Still {$remainingNulls} configs with NULL email_template_type!");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to nullable column
        DB::statement("ALTER TABLE service_output_configurations
            MODIFY COLUMN email_template_type
            ENUM('standard', 'technical', 'admin', 'custom')
            NULL
            COMMENT 'Email template type: standard, technical (backup), admin (IT support), custom. NULL = legacy detection.'");
    }
};
