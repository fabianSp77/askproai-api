<?php

namespace App\Services\Booking\Strategies;

use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Strategy that distributes bookings evenly across branches
 * Helps prevent overloading specific locations
 */
class LoadBalancedStrategy implements BranchSelectionStrategyInterface
{
    /**
     * Select branches ordered by current load (least busy first)
     */
    public function selectBranches(array $branches, Customer $customer, array $serviceRequirements): array
    {
        if (empty($branches)) {
            return [];
        }
        
        // Calculate load for each branch
        $branchesWithLoad = array_map(function ($branch) {
            return [
                'branch' => $branch,
                'load_score' => $this->calculateBranchLoad($branch)
            ];
        }, $branches);
        
        // Sort by load score (lower is less busy)
        usort($branchesWithLoad, function ($a, $b) {
            return $a['load_score'] <=> $b['load_score'];
        });
        
        // Extract just the branches
        $sortedBranches = array_column($branchesWithLoad, 'branch');
        
        Log::info('LoadBalancedStrategy: Branches sorted by load', [
            'customer_id' => $customer->id,
            'branch_loads' => array_map(function ($item) {
                return [
                    'branch' => $item['branch']->name,
                    'load' => $item['load_score']
                ];
            }, $branchesWithLoad)
        ]);
        
        return $sortedBranches;
    }
    
    /**
     * Calculate current load for a branch
     * Lower score = less busy
     */
    private function calculateBranchLoad($branch): float
    {
        $now = Carbon::now();
        $endOfWeek = $now->copy()->endOfWeek();
        
        // Count appointments for the next 7 days
        $upcomingAppointments = Appointment::where('branch_id', $branch->id)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('starts_at', [$now, $endOfWeek])
            ->count();
        
        // Count staff members to normalize
        $staffCount = $branch->staff()->where('active', true)->count() ?: 1;
        
        // Calculate appointments per staff member
        $loadScore = $upcomingAppointments / $staffCount;
        
        // Factor in branch capacity if available
        if (isset($branch->settings['daily_capacity'])) {
            $dailyCapacity = $branch->settings['daily_capacity'];
            $daysRemaining = $now->diffInDays($endOfWeek) + 1;
            $totalCapacity = $dailyCapacity * $daysRemaining;
            
            // Adjust load score based on capacity utilization
            if ($totalCapacity > 0) {
                $utilizationRate = $upcomingAppointments / $totalCapacity;
                $loadScore = $loadScore * (1 + $utilizationRate);
            }
        }
        
        return round($loadScore, 2);
    }
    
    /**
     * Get strategy name
     */
    public function getName(): string
    {
        return 'load_balanced';
    }
}