<?php

namespace App\Services\Booking\Strategies;

use App\Models\Customer;

/**
 * Interface for branch selection strategies
 * 
 * Different strategies can be implemented to select branches based on various criteria:
 * - Geographic proximity
 * - Service availability
 * - Load balancing
 * - Customer preferences
 */
interface BranchSelectionStrategyInterface
{
    /**
     * Select suitable branches based on strategy criteria
     * 
     * @param array $branches Available branches
     * @param Customer $customer The customer making the booking
     * @param array $serviceRequirements Service requirements including skills, duration, etc.
     * @return array Ordered list of branches (best match first)
     */
    public function selectBranches(array $branches, Customer $customer, array $serviceRequirements): array;
    
    /**
     * Get strategy name for logging/debugging
     * 
     * @return string
     */
    public function getName(): string;
}