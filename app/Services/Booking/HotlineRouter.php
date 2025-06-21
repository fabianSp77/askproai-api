<?php

namespace App\Services\Booking;

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HotlineRouter
{
    /**
     * Route hotline call to appropriate branch
     */
    public function routeHotlineCall(array $callData): array
    {
        $phoneNumber = $callData['to'] ?? $callData['to_number'] ?? null;
        
        if (!$phoneNumber) {
            return $this->createErrorResponse('Keine Zielnummer gefunden');
        }
        
        // Check if this is a hotline number
        $phoneRecord = PhoneNumber::where('number', $phoneNumber)
            ->where('type', 'hotline')
            ->where('active', true)
            ->first();
            
        if (!$phoneRecord) {
            return $this->createErrorResponse('Keine Hotline-Konfiguration gefunden');
        }
        
        $routingConfig = $phoneRecord->routing_config ?? [];
        $routingStrategy = $routingConfig['strategy'] ?? 'menu';
        
        return match($routingStrategy) {
            'menu' => $this->handleMenuRouting($phoneRecord, $callData),
            'geographic' => $this->handleGeographicRouting($phoneRecord, $callData),
            'load_balanced' => $this->handleLoadBalancedRouting($phoneRecord, $callData),
            'business_hours' => $this->handleBusinessHoursRouting($phoneRecord, $callData),
            default => $this->handleDefaultRouting($phoneRecord, $callData)
        };
    }
    
    /**
     * Handle DTMF/Voice menu routing
     */
    public function handleMenuRouting($phoneRecord, array $callData): array
    {
        $routingConfig = $phoneRecord->routing_config ?? [];
        $menuOptions = $routingConfig['menu_options'] ?? [];
        
        // Get user selection from call data
        $userSelection = $callData['dtmf_input'] ?? $callData['menu_selection'] ?? null;
        
        if (!$userSelection) {
            // Return menu prompt
            return [
                'action' => 'present_menu',
                'menu' => $this->buildMenuPrompt($menuOptions),
                'company_id' => $phoneRecord->company_id
            ];
        }
        
        // Find selected branch
        $selectedOption = $menuOptions[$userSelection] ?? null;
        
        if (!$selectedOption) {
            return [
                'action' => 'invalid_selection',
                'message' => 'Ungültige Auswahl. Bitte versuchen Sie es erneut.',
                'menu' => $this->buildMenuPrompt($menuOptions),
                'company_id' => $phoneRecord->company_id
            ];
        }
        
        $branch = Branch::find($selectedOption['branch_id']);
        
        if (!$branch || !$branch->active) {
            return $this->createErrorResponse('Ausgewählte Filiale nicht verfügbar');
        }
        
        return [
            'action' => 'route_to_branch',
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'company_id' => $phoneRecord->company_id,
            'routing_method' => 'menu_selection'
        ];
    }
    
    /**
     * Build menu prompt for voice response
     */
    private function buildMenuPrompt(array $menuOptions): array
    {
        $prompt = "Willkommen bei unserem Service. Bitte wählen Sie einen Standort: ";
        $options = [];
        
        foreach ($menuOptions as $digit => $option) {
            $prompt .= "Für {$option['name']} drücken Sie die {$digit}. ";
            $options[] = [
                'digit' => $digit,
                'name' => $option['name'],
                'branch_id' => $option['branch_id']
            ];
        }
        
        return [
            'prompt' => $prompt,
            'options' => $options,
            'type' => 'dtmf_menu'
        ];
    }
    
    /**
     * Geographic routing based on caller location
     */
    private function handleGeographicRouting($phoneRecord, array $callData): array
    {
        $callerNumber = $callData['from'] ?? $callData['from_number'] ?? null;
        
        if (!$callerNumber) {
            return $this->handleDefaultRouting($phoneRecord, $callData);
        }
        
        // Extract area code (simplified for German numbers)
        $areaCode = $this->extractAreaCode($callerNumber);
        
        // Get branches for company
        $branches = Branch::where('company_id', $phoneRecord->company_id)
            ->where('active', true)
            ->get();
            
        // Find nearest branch based on area code mapping
        $routingConfig = $phoneRecord->routing_config ?? [];
        $areaMapping = $routingConfig['area_mapping'] ?? [];
        
        if (isset($areaMapping[$areaCode])) {
            $branch = $branches->find($areaMapping[$areaCode]);
            if ($branch) {
                return [
                    'action' => 'route_to_branch',
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'company_id' => $phoneRecord->company_id,
                    'routing_method' => 'geographic'
                ];
            }
        }
        
        // Fallback to default
        return $this->handleDefaultRouting($phoneRecord, $callData);
    }
    
    /**
     * Load balanced routing across branches
     */
    private function handleLoadBalancedRouting($phoneRecord, array $callData): array
    {
        $branches = Branch::where('company_id', $phoneRecord->company_id)
            ->where('active', true)
            ->get();
            
        if ($branches->isEmpty()) {
            return $this->createErrorResponse('Keine aktiven Filialen gefunden');
        }
        
        // Get current load for each branch
        $branchLoads = [];
        foreach ($branches as $branch) {
            $cacheKey = "branch_load:{$branch->id}";
            $load = Cache::get($cacheKey, 0);
            $branchLoads[$branch->id] = $load;
        }
        
        // Find branch with lowest load
        asort($branchLoads);
        $selectedBranchId = array_key_first($branchLoads);
        $selectedBranch = $branches->find($selectedBranchId);
        
        // Increment load counter
        Cache::increment("branch_load:{$selectedBranchId}", 1);
        Cache::put("branch_load:{$selectedBranchId}", $branchLoads[$selectedBranchId] + 1, 300); // 5 minutes
        
        return [
            'action' => 'route_to_branch',
            'branch_id' => $selectedBranch->id,
            'branch_name' => $selectedBranch->name,
            'company_id' => $phoneRecord->company_id,
            'routing_method' => 'load_balanced'
        ];
    }
    
    /**
     * Business hours based routing
     */
    private function handleBusinessHoursRouting($phoneRecord, array $callData): array
    {
        $branches = Branch::where('company_id', $phoneRecord->company_id)
            ->where('active', true)
            ->get();
            
        $currentTime = now();
        $dayOfWeek = strtolower($currentTime->format('l'));
        
        // Find branches currently open
        $openBranches = $branches->filter(function ($branch) use ($dayOfWeek, $currentTime) {
            $hours = $branch->business_hours[$dayOfWeek] ?? null;
            
            if (!$hours || !isset($hours['open']) || !isset($hours['close'])) {
                return false;
            }
            
            $openTime = \Carbon\Carbon::parse($hours['open']);
            $closeTime = \Carbon\Carbon::parse($hours['close']);
            
            return $currentTime->between($openTime, $closeTime);
        });
        
        if ($openBranches->isEmpty()) {
            return [
                'action' => 'all_closed',
                'message' => 'Leider sind derzeit alle Filialen geschlossen.',
                'company_id' => $phoneRecord->company_id,
                'next_opening' => $this->findNextOpening($branches)
            ];
        }
        
        // If multiple open, use load balancing
        if ($openBranches->count() > 1) {
            $phoneRecord->routing_config = ['strategy' => 'load_balanced'];
            return $this->handleLoadBalancedRouting($phoneRecord, $callData);
        }
        
        $selectedBranch = $openBranches->first();
        
        return [
            'action' => 'route_to_branch',
            'branch_id' => $selectedBranch->id,
            'branch_name' => $selectedBranch->name,
            'company_id' => $phoneRecord->company_id,
            'routing_method' => 'business_hours'
        ];
    }
    
    /**
     * Default routing to main branch
     */
    private function handleDefaultRouting($phoneRecord, array $callData): array
    {
        $mainBranch = Branch::where('company_id', $phoneRecord->company_id)
            ->where('active', true)
            ->where('is_main', true)
            ->first();
            
        if (!$mainBranch) {
            // Get first active branch
            $mainBranch = Branch::where('company_id', $phoneRecord->company_id)
                ->where('active', true)
                ->first();
        }
        
        if (!$mainBranch) {
            return $this->createErrorResponse('Keine aktive Filiale gefunden');
        }
        
        return [
            'action' => 'route_to_branch',
            'branch_id' => $mainBranch->id,
            'branch_name' => $mainBranch->name,
            'company_id' => $phoneRecord->company_id,
            'routing_method' => 'default'
        ];
    }
    
    /**
     * Extract area code from phone number
     */
    private function extractAreaCode(string $phoneNumber): ?string
    {
        // Remove non-digits
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // German area codes logic
        if (str_starts_with($cleaned, '49')) {
            $cleaned = substr($cleaned, 2);
        }
        if (str_starts_with($cleaned, '0')) {
            $cleaned = substr($cleaned, 1);
        }
        
        // Extract first 2-5 digits as area code
        if (strlen($cleaned) >= 2) {
            return substr($cleaned, 0, min(5, strlen($cleaned)));
        }
        
        return null;
    }
    
    /**
     * Find next opening time
     */
    private function findNextOpening($branches): ?array
    {
        // Implementation for finding next opening time
        // This is simplified - real implementation would check all branches
        return [
            'time' => 'Montag 9:00 Uhr',
            'branch' => 'Hauptfiliale'
        ];
    }
    
    /**
     * Create error response
     */
    private function createErrorResponse(string $message): array
    {
        return [
            'action' => 'error',
            'message' => $message,
            'success' => false
        ];
    }
}