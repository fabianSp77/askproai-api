<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if calls table exists first
        if (!\Illuminate\Support\Facades\Schema::hasTable('calls')) {
            return;
        }

        // Fix 1: Status inconsistencies (49 records with status='completed' but call_status='ended')
        $affected = DB::table('calls')
            ->where('status', 'completed')
            ->where('call_status', 'ended')
            ->update(['call_status' => 'completed']);

        Log::info("Fixed status inconsistencies for {$affected} calls");

        // Fix 2: NULL cost for calls that have customer_cost calculated (4 records)
        $affected = DB::table('calls')
            ->whereNull('cost')
            ->whereNotNull('customer_cost')
            ->update(['cost' => DB::raw('customer_cost / 100.0')]);

        Log::info("Fixed NULL cost for {$affected} calls with calculated customer_cost");

        // Fix 3: Ensure all status fields are consistent going forward
        // Any record where call_status differs from status, use status as source of truth
        $affected = DB::table('calls')
            ->whereColumn('status', '!=', 'call_status')
            ->update(['call_status' => DB::raw('status')]);

        Log::info("Synchronized status fields for {$affected} additional calls");

        // Fix 4: Calculate costs for calls without base_cost but with duration
        // (The 2 calls without duration are test calls and don't need costs)
        $callsNeedingCosts = DB::table('calls')
            ->whereNull('base_cost')
            ->whereNotNull('duration_sec')
            ->where('duration_sec', '>', 0)
            ->get();

        if ($callsNeedingCosts->count() > 0) {
            foreach ($callsNeedingCosts as $call) {
                $minutes = ceil($call->duration_sec / 60);
                $baseCost = 15 + (($minutes - 1) * 10); // €0.15 first minute, €0.10 per additional
                $customerCost = $baseCost; // Same as base for now

                DB::table('calls')
                    ->where('id', $call->id)
                    ->update([
                        'base_cost' => $baseCost,
                        'customer_cost' => $customerCost,
                        'cost_calculation_method' => 'migration_fix',
                        'cost' => $customerCost / 100.0
                    ]);
            }

            Log::info("Calculated costs for {$callsNeedingCosts->count()} calls");
        }

        // Log summary
        Log::info('Data inconsistencies fixed successfully', [
            'status_fixes' => DB::table('calls')->where('call_status', 'completed')->count(),
            'cost_fixes' => DB::table('calls')->whereNotNull('cost')->count(),
            'base_cost_calculated' => DB::table('calls')->whereNotNull('base_cost')->count(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration fixes data inconsistencies and cannot be meaningfully reversed
        Log::warning('Data consistency migration rollback requested but changes cannot be reversed');
    }
};