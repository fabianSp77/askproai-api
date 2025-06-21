<?php

namespace App\Services\Booking\Strategies;

use App\Models\Customer;
use Illuminate\Support\Facades\Log;

/**
 * Strategy that selects branches based on earliest availability
 * Useful for urgent bookings where time is more important than location
 */
class FirstAvailableStrategy implements BranchSelectionStrategyInterface
{
    /**
     * Select all branches without specific ordering
     * The availability check will determine which has the earliest slot
     */
    public function selectBranches(array $branches, Customer $customer, array $serviceRequirements): array
    {
        Log::info('FirstAvailableStrategy: Returning all branches for availability check', [
            'customer_id' => $customer->id,
            'branch_count' => count($branches)
        ]);
        
        // Return all branches - the orchestrator will check availability for each
        return $branches;
    }
    
    /**
     * Get strategy name
     */
    public function getName(): string
    {
        return 'first_available';
    }
}