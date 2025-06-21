<?php

namespace App\Services\Setup;

use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Facades\Log;

class BranchSetupService
{
    /**
     * Create multiple branches efficiently
     */
    public function createBranches(Company $company, array $branchesData): array
    {
        $branchModels = [];
        
        foreach ($branchesData as $branchData) {
            $branchModels[] = new Branch([
                'company_id' => $company->id,
                'name' => $branchData['name'],
                'city' => $branchData['city'] ?? null,
                'address' => $branchData['address'] ?? null,
                'phone_number' => $branchData['phone_number'] ?? null,
                'email' => $branchData['email'] ?? null,
                'is_active' => true,
                'business_hours' => $this->getDefaultBusinessHours($company->industry),
                'settings' => [
                    'features' => $branchData['features'] ?? [],
                    'capacity' => $branchData['capacity'] ?? 10,
                    'timezone' => $branchData['timezone'] ?? 'Europe/Berlin',
                ]
            ]);
        }

        // Bulk save for performance
        $company->branches()->saveMany($branchModels);

        Log::info('Branches created via SetupOrchestrator', [
            'company_id' => $company->id,
            'count' => count($branchModels),
            'branches' => collect($branchModels)->pluck('name')
        ]);

        return $branchModels;
    }

    /**
     * Update existing branches
     */
    public function updateBranches(Company $company, array $branchesData): array
    {
        $updatedBranches = [];
        
        foreach ($branchesData as $branchData) {
            if (isset($branchData['id'])) {
                $branch = Branch::where('company_id', $company->id)
                    ->where('id', $branchData['id'])
                    ->first();
                    
                if ($branch) {
                    $branch->update([
                        'name' => $branchData['name'] ?? $branch->name,
                        'city' => $branchData['city'] ?? $branch->city,
                        'address' => $branchData['address'] ?? $branch->address,
                        'phone_number' => $branchData['phone_number'] ?? $branch->phone_number,
                        'email' => $branchData['email'] ?? $branch->email,
                    ]);
                    $updatedBranches[] = $branch;
                }
            }
        }
        
        return $updatedBranches;
    }

    /**
     * Get default business hours based on industry
     */
    private function getDefaultBusinessHours(string $industry): array
    {
        return match($industry) {
            'medical' => [
                'monday' => ['09:00-12:00', '14:00-18:00'],
                'tuesday' => ['09:00-12:00', '14:00-18:00'],
                'wednesday' => ['09:00-12:00', '14:00-18:00'],
                'thursday' => ['09:00-12:00', '14:00-18:00'],
                'friday' => ['09:00-12:00', '14:00-17:00'],
                'saturday' => [],
                'sunday' => []
            ],
            'beauty' => [
                'monday' => ['10:00-19:00'],
                'tuesday' => ['10:00-19:00'],
                'wednesday' => ['10:00-19:00'],
                'thursday' => ['10:00-20:00'],
                'friday' => ['10:00-20:00'],
                'saturday' => ['09:00-16:00'],
                'sunday' => []
            ],
            'handwerk' => [
                'monday' => ['08:00-17:00'],
                'tuesday' => ['08:00-17:00'],
                'wednesday' => ['08:00-17:00'],
                'thursday' => ['08:00-17:00'],
                'friday' => ['08:00-16:00'],
                'saturday' => [],
                'sunday' => []
            ],
            'legal' => [
                'monday' => ['09:00-13:00', '14:00-18:00'],
                'tuesday' => ['09:00-13:00', '14:00-18:00'],
                'wednesday' => ['09:00-13:00', '14:00-18:00'],
                'thursday' => ['09:00-13:00', '14:00-18:00'],
                'friday' => ['09:00-13:00', '14:00-16:00'],
                'saturday' => [],
                'sunday' => []
            ],
            default => [
                'monday' => ['09:00-17:00'],
                'tuesday' => ['09:00-17:00'],
                'wednesday' => ['09:00-17:00'],
                'thursday' => ['09:00-17:00'],
                'friday' => ['09:00-17:00'],
                'saturday' => [],
                'sunday' => []
            ]
        };
    }
}