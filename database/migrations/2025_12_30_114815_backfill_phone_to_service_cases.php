<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backfill Migration: Add phone numbers to ServiceCases from Call records
 *
 * This migration populates the customer_phone field in service_cases.ai_metadata
 * for existing cases where:
 * - The case has a linked call (call_id is not null)
 * - The call has a from_number
 * - The case's ai_metadata.customer_phone is null or missing
 *
 * The phone source is marked as 'call_record_backfill' for audit purposes.
 *
 * @see app/Http/Controllers/ServiceDeskHandler.php - buildAiMetadata()
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

        // Count affected rows for logging
        // Check for NULL, empty string, JSON null, or missing customer_phone in ai_metadata
        // Note: JSON_EXTRACT returns string 'null' for JSON null values in MySQL
        $affectedCount = DB::table('service_cases as sc')
            ->join('calls as c', 'sc.call_id', '=', 'c.id')
            ->whereNotNull('c.from_number')
            ->where('c.from_number', '!=', '')
            ->whereNotIn(DB::raw('LOWER(c.from_number)'), ['anonymous', 'unknown', 'private', 'withheld'])
            ->where(function ($query) {
                $query->whereNull('sc.ai_metadata')
                    ->orWhereRaw("JSON_TYPE(JSON_EXTRACT(sc.ai_metadata, '$.customer_phone')) = 'NULL'")
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(sc.ai_metadata, '$.customer_phone')) = ''");
            })
            ->count();

        Log::info('[Migration] Backfilling phone numbers to service_cases', [
            'affected_cases' => $affectedCount,
        ]);

        if ($affectedCount === 0) {
            Log::info('[Migration] No cases need backfilling');
            return;
        }

        // Perform the backfill update using MySQL JSON functions
        // This updates ai_metadata to include:
        // - customer_phone: the from_number from the call
        // - customer_phone_source: 'call_record_backfill' (audit marker)
        // - call_from_number: the original from_number (audit)
        DB::statement("
            UPDATE service_cases sc
            INNER JOIN calls c ON sc.call_id = c.id
            SET sc.ai_metadata = JSON_SET(
                COALESCE(sc.ai_metadata, '{}'),
                '$.customer_phone', c.from_number,
                '$.customer_phone_source', 'call_record_backfill',
                '$.call_from_number', c.from_number
            )
            WHERE c.from_number IS NOT NULL
              AND c.from_number != ''
              AND LOWER(c.from_number) NOT IN ('anonymous', 'unknown', 'private', 'withheld')
              AND (
                  sc.ai_metadata IS NULL
                  OR JSON_TYPE(JSON_EXTRACT(sc.ai_metadata, '$.customer_phone')) = 'NULL'
                  OR JSON_UNQUOTE(JSON_EXTRACT(sc.ai_metadata, '$.customer_phone')) = ''
              )
        ");

        Log::info('[Migration] Backfill completed', [
            'updated_cases' => $affectedCount,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * Note: We don't remove the backfilled data as it's additive and non-destructive.
     * The 'call_record_backfill' source marker clearly indicates this data was added
     * by this migration and can be identified/removed if needed.
     */
    public function down(): void
    {
        // Optional: Remove backfilled phone data
        // This is commented out by default as the data is useful and non-destructive
        /*
        DB::statement("
            UPDATE service_cases
            SET ai_metadata = JSON_REMOVE(
                ai_metadata,
                '$.customer_phone',
                '$.customer_phone_source',
                '$.call_from_number'
            )
            WHERE JSON_UNQUOTE(JSON_EXTRACT(ai_metadata, '$.customer_phone_source')) = 'call_record_backfill'
        ");
        */

        Log::info('[Migration] Backfill rollback skipped (data preserved)');
    }
};
