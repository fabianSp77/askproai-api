<?php

namespace App\Services;

use App\Models\MasterService;
use App\Models\Branch;
use App\Models\BranchServiceOverride;

class MasterServiceManager
{
    public function getEffectiveServicesForBranch(Branch $branch): array
    {
        $masterServices = $branch->company->masterServices()
            ->where('is_active', true)
            ->get();
            
        $overrides = $branch->serviceOverrides()
            ->with('masterService')
            ->get()
            ->keyBy('master_service_id');
            
        $effectiveServices = [];
        
        foreach ($masterServices as $masterService) {
            $override = $overrides->get($masterService->id);
            
            $effectiveService = [
                'id' => $masterService->id,
                'name' => $masterService->name,
                'description' => $masterService->description,
                'duration_minutes' => $override?->duration_minutes ?? $masterService->duration_minutes,
                'price' => $override?->price ?? $masterService->price,
                'is_active' => $override ? $override->is_active : $masterService->is_active,
                'is_overridden' => $override !== null,
            ];
            
            if ($effectiveService['is_active']) {
                $effectiveServices[] = $effectiveService;
            }
        }
        
        return $effectiveServices;
    }
    
    public function deployServiceToBranches(MasterService $service, array $branchIds): void
    {
        foreach ($branchIds as $branchId) {
            // Create or update override
            BranchServiceOverride::updateOrCreate(
                [
                    'branch_id' => $branchId,
                    'master_service_id' => $service->id,
                ],
                [
                    'is_active' => true,
                ]
            );
        }
    }
}
