<?php

namespace App\Services\Booking\Strategies;

use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Support\Facades\Log;

/**
 * Strategy that selects branches based on geographic proximity to customer
 */
class NearestLocationStrategy implements BranchSelectionStrategyInterface
{
    /**
     * Select branches ordered by proximity to customer
     */
    public function selectBranches(array $branches, Customer $customer, array $serviceRequirements): array
    {
        if (empty($branches)) {
            return [];
        }
        
        // If customer has a preferred branch, prioritize it
        if ($customer->preferred_branch_id) {
            $branches = $this->prioritizePreferredBranch($branches, $customer->preferred_branch_id);
        }
        
        // If customer has location data, sort by distance
        if ($customer->postal_code || $customer->city) {
            $branches = $this->sortByDistance($branches, $customer);
        }
        
        Log::info('NearestLocationStrategy: Selected branches', [
            'customer_id' => $customer->id,
            'branch_count' => count($branches),
            'first_branch' => $branches[0]->name ?? 'none'
        ]);
        
        return $branches;
    }
    
    /**
     * Prioritize customer's preferred branch
     */
    private function prioritizePreferredBranch(array $branches, string $preferredBranchId): array
    {
        $preferred = null;
        $others = [];
        
        foreach ($branches as $branch) {
            if ($branch->id === $preferredBranchId) {
                $preferred = $branch;
            } else {
                $others[] = $branch;
            }
        }
        
        if ($preferred) {
            array_unshift($others, $preferred);
            return $others;
        }
        
        return $branches;
    }
    
    /**
     * Sort branches by distance from customer
     */
    private function sortByDistance(array $branches, Customer $customer): array
    {
        // Calculate distance scores for each branch
        $branchesWithDistance = array_map(function ($branch) use ($customer) {
            return [
                'branch' => $branch,
                'distance_score' => $this->calculateDistanceScore($branch, $customer)
            ];
        }, $branches);
        
        // Sort by distance score (lower is closer)
        usort($branchesWithDistance, function ($a, $b) {
            return $a['distance_score'] <=> $b['distance_score'];
        });
        
        // Extract just the branches
        return array_column($branchesWithDistance, 'branch');
    }
    
    /**
     * Calculate distance score between branch and customer
     * Lower score = closer distance
     */
    private function calculateDistanceScore(Branch $branch, Customer $customer): float
    {
        // Simple scoring based on postal code and city matching
        $score = 100.0; // Base score
        
        // Exact postal code match
        if ($customer->postal_code && $branch->postal_code === $customer->postal_code) {
            return 0.0; // Perfect match
        }
        
        // Same postal code area (first 3 digits in Germany)
        if ($customer->postal_code && $branch->postal_code) {
            $customerArea = substr($customer->postal_code, 0, 3);
            $branchArea = substr($branch->postal_code, 0, 3);
            
            if ($customerArea === $branchArea) {
                $score = 10.0; // Same area
            } elseif (abs((int)$customerArea - (int)$branchArea) <= 10) {
                $score = 30.0; // Nearby area
            }
        }
        
        // Same city
        if ($customer->city && $branch->city) {
            if (strtolower($customer->city) === strtolower($branch->city)) {
                $score = min($score, 20.0); // Same city (but different postal code)
            }
        }
        
        // TODO: Implement real geocoding/distance calculation
        // This would use Google Maps API or similar to calculate actual distances
        
        return $score;
    }
    
    /**
     * Get strategy name
     */
    public function getName(): string
    {
        return 'nearest_location';
    }
}