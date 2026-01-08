<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ServiceCase;

/**
 * Fix Migration: Convert German string values in others_affected to boolean
 *
 * The Retell agent was sending German strings ("ja"/"nein") instead of boolean values.
 * This migration converts existing string values to proper JSON booleans.
 *
 * Uses PHP-based approach for MariaDB compatibility (CAST AS JSON not supported).
 *
 * @see app/Http/Controllers/ServiceDeskHandler.php - parseGermanBoolean()
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip in testing environment (SQLite doesn't support JSON functions)
        if (app()->environment('testing')) {
            return;
        }

        $neinCount = 0;
        $jaCount = 0;

        // Find cases with "nein" string and convert to false
        ServiceCase::withoutGlobalScopes()
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(ai_metadata, '$.others_affected')) = 'nein'")
            ->chunkById(100, function ($cases) use (&$neinCount) {
                foreach ($cases as $case) {
                    $metadata = $case->ai_metadata ?? [];
                    $metadata['others_affected'] = false;
                    $case->ai_metadata = $metadata;
                    $case->saveQuietly();
                    $neinCount++;
                }
            });

        // Find cases with "ja" string and convert to true
        ServiceCase::withoutGlobalScopes()
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(ai_metadata, '$.others_affected')) = 'ja'")
            ->chunkById(100, function ($cases) use (&$jaCount) {
                foreach ($cases as $case) {
                    $metadata = $case->ai_metadata ?? [];
                    $metadata['others_affected'] = true;
                    $case->ai_metadata = $metadata;
                    $case->saveQuietly();
                    $jaCount++;
                }
            });

        Log::info('[Migration] others_affected boolean fix completed', [
            'nein_to_false' => $neinCount,
            'ja_to_true' => $jaCount,
            'total_fixed' => $neinCount + $jaCount,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback - boolean values are the correct format
        Log::info('[Migration] others_affected rollback skipped (boolean is correct format)');
    }
};
