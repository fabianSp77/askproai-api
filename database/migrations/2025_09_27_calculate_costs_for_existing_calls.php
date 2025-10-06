<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Call;
use App\Services\CostCalculator;
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

        // Calculate costs for all existing calls that have a duration
        $costCalculator = new CostCalculator();

        // Process in chunks to avoid memory issues
        Call::whereNotNull('duration_sec')
            ->where('duration_sec', '>', 0)
            ->whereNull('base_cost') // Only process calls without calculated costs
            ->chunkById(100, function ($calls) use ($costCalculator) {
                foreach ($calls as $call) {
                    try {
                        $costCalculator->updateCallCosts($call);
                        Log::info('Calculated costs for call', ['call_id' => $call->id]);
                    } catch (\Exception $e) {
                        Log::error('Failed to calculate costs for call', [
                            'call_id' => $call->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

        Log::info('Completed cost calculation for existing calls');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset cost calculations
        Call::query()->update([
            'base_cost' => null,
            'reseller_cost' => null,
            'customer_cost' => null,
            'cost_calculation_method' => null,
            'cost_breakdown' => null,
        ]);
    }
};