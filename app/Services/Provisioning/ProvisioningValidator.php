<?php

namespace App\Services\Provisioning;

use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProvisioningValidator
{
    private array $errors = [];
    private array $warnings = [];
    
    /**
     * Validate branch for provisioning
     */
    public function validateBranch(Branch $branch): ValidationResult
    {
        $this->errors = [];
        $this->warnings = [];
        
        // Core validations
        $this->validateBranchData($branch);
        $this->validateServices($branch);
        $this->validateWorkingHours($branch);
        $this->validateCalendarIntegration($branch);
        $this->validateApiConnectivity($branch);
        
        return new ValidationResult(
            empty($this->errors),
            $this->errors,
            $this->warnings,
            $this->generateRecommendations()
        );
    }
    
    /**
     * Validate basic branch data
     */
    private function validateBranchData(Branch $branch): void
    {
        if (!$branch->company) {
            $this->errors[] = [
                'field' => 'company',
                'message' => 'Branch must belong to a company',
                'code' => 'COMPANY_MISSING'
            ];
        }
        
        if (empty($branch->name)) {
            $this->errors[] = [
                'field' => 'name',
                'message' => 'Branch name is required',
                'code' => 'BRANCH_NAME_MISSING'
            ];
        }
        
        if (empty($branch->phone_number)) {
            $this->errors[] = [
                'field' => 'phone_number',
                'message' => 'Branch phone number is required for call routing',
                'code' => 'PHONE_NUMBER_MISSING',
                'action' => [
                    'type' => 'input',
                    'field' => 'phone_number',
                    'label' => 'Add Phone Number',
                    'placeholder' => '+49 30 12345678'
                ]
            ];
        }
        
        if (!$branch->active) {
            $this->warnings[] = [
                'field' => 'active',
                'message' => 'Branch is not active. AI agent will not handle calls.',
                'code' => 'BRANCH_INACTIVE'
            ];
        }
        
        if (empty($branch->address)) {
            $this->warnings[] = [
                'field' => 'address',
                'message' => 'Branch address is recommended for customer communications',
                'code' => 'ADDRESS_MISSING'
            ];
        }
    }
    
    /**
     * Validate services configuration
     */
    private function validateServices(Branch $branch): void
    {
        if ($branch->services->isEmpty()) {
            $this->errors[] = [
                'field' => 'services',
                'message' => 'At least one service must be configured before provisioning',
                'code' => 'NO_SERVICES',
                'action' => [
                    'type' => 'navigate',
                    'url' => "/admin/branches/{$branch->id}/services/create",
                    'label' => 'Add Service'
                ]
            ];
            return;
        }
        
        // Check for active services
        $activeServices = $branch->services->where('is_active', true);
        if ($activeServices->isEmpty()) {
            $this->warnings[] = [
                'field' => 'services',
                'message' => 'No active services found. Customers won\'t be able to book appointments.',
                'code' => 'NO_ACTIVE_SERVICES'
            ];
        }
        
        // Check service details
        foreach ($branch->services as $service) {
            if (empty($service->duration) || $service->duration < 5) {
                $this->warnings[] = [
                    'field' => 'services',
                    'message' => "Service '{$service->name}' has invalid duration ({$service->duration} minutes)",
                    'code' => 'INVALID_SERVICE_DURATION',
                    'service_id' => $service->id
                ];
            }
            
            if (empty($service->name)) {
                $this->errors[] = [
                    'field' => 'services',
                    'message' => 'Service name is required',
                    'code' => 'SERVICE_NAME_MISSING',
                    'service_id' => $service->id
                ];
            }
        }
        
        // Industry-specific validations
        $this->validateIndustrySpecificServices($branch);
    }
    
    /**
     * Validate industry-specific service requirements
     */
    private function validateIndustrySpecificServices(Branch $branch): void
    {
        $industry = $branch->company->industry ?? 'general';
        
        $recommendations = [
            'medical' => ['consultation', 'examination', 'follow_up'],
            'beauty' => ['haircut', 'coloring', 'treatment'],
            'veterinary' => ['checkup', 'vaccination', 'surgery'],
            'legal' => ['consultation', 'document_review'],
            'automotive' => ['inspection', 'oil_change', 'repair']
        ];
        
        if (isset($recommendations[$industry])) {
            $existingTypes = $branch->services->pluck('type')->filter()->toArray();
            $recommendedTypes = $recommendations[$industry];
            $missingTypes = array_diff($recommendedTypes, $existingTypes);
            
            if (!empty($missingTypes)) {
                $this->warnings[] = [
                    'field' => 'services',
                    'message' => "Recommended service types for {$industry}: " . implode(', ', $missingTypes),
                    'code' => 'INDUSTRY_SERVICES_RECOMMENDATION',
                    'industry' => $industry,
                    'missing_types' => $missingTypes
                ];
            }
        }
    }
    
    /**
     * Validate working hours configuration
     */
    private function validateWorkingHours(Branch $branch): void
    {
        if (empty($branch->business_hours) || !is_array($branch->business_hours)) {
            $this->errors[] = [
                'field' => 'working_hours',
                'message' => 'Working hours must be configured for appointment scheduling',
                'code' => 'WORKING_HOURS_MISSING',
                'action' => [
                    'type' => 'navigate',
                    'url' => "/admin/branches/{$branch->id}/working-hours",
                    'label' => 'Set Working Hours'
                ]
            ];
            return;
        }
        
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $hasAnyOpenDay = false;
        
        foreach ($daysOfWeek as $day) {
            if (isset($branch->business_hours[$day]) && $branch->business_hours[$day]['enabled'] ?? false) {
                $hasAnyOpenDay = true;
                
                // Validate time format
                $start = $branch->business_hours[$day]['start'] ?? null;
                $end = $branch->business_hours[$day]['end'] ?? null;
                
                if (!$start || !$end) {
                    $this->warnings[] = [
                        'field' => 'working_hours',
                        'message' => "Invalid working hours for {$day}",
                        'code' => 'INVALID_WORKING_HOURS',
                        'day' => $day
                    ];
                }
            }
        }
        
        if (!$hasAnyOpenDay) {
            $this->errors[] = [
                'field' => 'working_hours',
                'message' => 'At least one day must have working hours configured',
                'code' => 'NO_WORKING_DAYS'
            ];
        }
    }
    
    /**
     * Validate calendar integration
     */
    private function validateCalendarIntegration(Branch $branch): void
    {
        if (empty($branch->calcom_event_type_id)) {
            $this->errors[] = [
                'field' => 'calendar',
                'message' => 'Cal.com event type must be configured for booking',
                'code' => 'CALCOM_EVENT_TYPE_MISSING',
                'action' => [
                    'type' => 'navigate',
                    'url' => "/admin/branches/{$branch->id}/calendar-setup",
                    'label' => 'Setup Calendar'
                ]
            ];
        }
        
        if (empty($branch->company->calcom_api_key) && empty($branch->calcom_api_key)) {
            $this->errors[] = [
                'field' => 'calendar',
                'message' => 'Cal.com API key is required for calendar integration',
                'code' => 'CALCOM_API_KEY_MISSING'
            ];
        }
    }
    
    /**
     * Validate API connectivity
     */
    private function validateApiConnectivity(Branch $branch): void
    {
        // Check Retell API key
        if (empty($branch->company->retell_api_key)) {
            $this->errors[] = [
                'field' => 'api',
                'message' => 'Retell.ai API key is required for AI agent',
                'code' => 'RETELL_API_KEY_MISSING',
                'action' => [
                    'type' => 'navigate',
                    'url' => "/admin/companies/{$branch->company_id}/settings",
                    'label' => 'Add API Key'
                ]
            ];
        }
        
        // Check if branch already has an agent
        if (!empty($branch->retell_agent_id)) {
            $this->warnings[] = [
                'field' => 'agent',
                'message' => 'Branch already has a Retell agent configured. Provisioning will update the existing agent.',
                'code' => 'AGENT_EXISTS',
                'agent_id' => $branch->retell_agent_id
            ];
        }
    }
    
    /**
     * Generate recommendations based on validation results
     */
    private function generateRecommendations(): array
    {
        $recommendations = [];
        
        // High priority fixes
        foreach ($this->errors as $error) {
            if (isset($error['action'])) {
                $recommendations[] = [
                    'priority' => 'high',
                    'action' => $error['action'],
                    'reason' => $error['message'],
                    'code' => $error['code']
                ];
            }
        }
        
        // Medium priority improvements
        if (count($this->warnings) > 2) {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => [
                    'type' => 'info',
                    'message' => 'Consider addressing the warnings to improve customer experience'
                ],
                'reason' => count($this->warnings) . ' warnings found'
            ];
        }
        
        // Success message if all good
        if (empty($this->errors) && empty($this->warnings)) {
            $recommendations[] = [
                'priority' => 'info',
                'action' => [
                    'type' => 'success',
                    'message' => '✅ Branch is ready for provisioning!'
                ]
            ];
        }
        
        return $recommendations;
    }
}

/**
 * Validation Result DTO
 */
class ValidationResult
{
    private bool $valid;
    private array $errors;
    private array $warnings;
    private array $recommendations;
    
    public function __construct(bool $valid, array $errors = [], array $warnings = [], array $recommendations = [])
    {
        $this->valid = $valid;
        $this->errors = $errors;
        $this->warnings = $warnings;
        $this->recommendations = $recommendations;
    }
    
    public function isValid(): bool
    {
        return $this->valid;
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    public function getWarnings(): array
    {
        return $this->warnings;
    }
    
    public function getRecommendations(): array
    {
        return $this->recommendations;
    }
    
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }
    
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'recommendations' => $this->recommendations,
            'summary' => [
                'error_count' => count($this->errors),
                'warning_count' => count($this->warnings),
                'recommendation_count' => count($this->recommendations)
            ]
        ];
    }
}