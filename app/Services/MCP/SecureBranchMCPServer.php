<?php

namespace App\Services\MCP;

use App\Models\Branch;
use App\Models\StaffMember;
use App\Models\Service;
use App\Models\WorkingHours;
use App\Models\Company;
use App\Services\SecurityAuditService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Exceptions\SecurityException;

/**
 * SECURE MCP Server for Branch Management
 * 
 * Security Enhancements:
 * - All queries strictly scoped to authenticated company
 * - No withoutGlobalScopes usage
 * - Company ownership validation on all operations
 * - Audit logging for all data access and modifications
 * - Branch operations limited to company boundaries
 * - Prevents cross-tenant data access
 */
class SecureBranchMCPServer
{
    protected Company $company;
    protected SecurityAuditService $auditService;
    protected DatabaseMCPServer $databaseMCP;
    
    public function __construct(DatabaseMCPServer $databaseMCP)
    {
        $this->databaseMCP = $databaseMCP;
        $this->auditService = app(SecurityAuditService::class);
        
        // Get authenticated company context
        $user = Auth::user();
        if (!$user || !$user->company_id) {
            throw new SecurityException('No authenticated company context');
        }
        
        $this->company = Company::findOrFail($user->company_id);
        
        Log::info('SecureBranchMCPServer: Initialized with company context', [
            'company_id' => $this->company->id,
            'user_id' => $user->id
        ]);
    }
    
    /**
     * Get branch details by ID (company-scoped)
     */
    public function getBranch(string $branchId): array
    {
        try {
            // SECURITY: Verify branch belongs to authenticated company
            $branch = Branch::where('company_id', $this->company->id)
                ->with(['company', 'phoneNumbers', 'staff', 'services'])
                ->find($branchId);
                
            if (!$branch) {
                $this->auditService->logSecurityEvent('branch_access_denied', [
                    'branch_id' => $branchId,
                    'company_id' => $this->company->id,
                    'reason' => 'Branch not found or access denied'
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Branch not found',
                    'data' => null
                ];
            }
            
            $this->auditService->logDataAccess('branch_viewed', [
                'branch_id' => $branch->id,
                'company_id' => $this->company->id
            ]);
            
            return [
                'success' => true,
                'data' => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'company_id' => $branch->company_id,
                    'company_name' => $branch->company->name,
                    'address' => $branch->address,
                    'city' => $branch->city,
                    'postal_code' => $branch->postal_code,
                    'country' => $branch->country,
                    'phone_number' => $branch->phone_number,
                    'email' => $branch->email,
                    'is_active' => $branch->is_active,
                    'calcom_event_type_id' => $branch->calcom_event_type_id,
                    'retell_agent_id' => $branch->retell_agent_id,
                    'phone_numbers' => $branch->phoneNumbers->map(function($phone) {
                        return [
                            'id' => $phone->id,
                            'phone_number' => $phone->number,
                            'type' => $phone->type,
                            'is_primary' => $phone->is_primary,
                            'is_active' => $phone->is_active
                        ];
                    }),
                    'staff_count' => $branch->staff->count(),
                    'services_count' => $branch->services->count(),
                    'created_at' => $branch->created_at,
                    'updated_at' => $branch->updated_at
                ]
            ];
        } catch (\Exception $e) {
            Log::error('SecureBranchMCP: Failed to get branch', [
                'branch_id' => $branchId,
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get branch: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Get all branches for authenticated company
     */
    public function getBranchesByCompany(): array
    {
        try {
            // SECURITY: Only return branches for authenticated company
            $branches = Branch::where('company_id', $this->company->id)
                ->with(['phoneNumbers', 'staff', 'services'])
                ->get();
                
            $this->auditService->logDataAccess('branches_listed', [
                'company_id' => $this->company->id,
                'count' => $branches->count()
            ]);
                
            return [
                'success' => true,
                'data' => $branches->map(function($branch) {
                    return [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'address' => $branch->address,
                        'city' => $branch->city,
                        'phone_number' => $branch->phone_number,
                        'email' => $branch->email,
                        'is_active' => $branch->is_active,
                        'staff_count' => $branch->staff->count(),
                        'services_count' => $branch->services->count()
                    ];
                }),
                'count' => $branches->count()
            ];
        } catch (\Exception $e) {
            Log::error('SecureBranchMCP: Failed to get branches', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get branches: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Create a new branch (for authenticated company only)
     */
    public function createBranch(array $data): array
    {
        try {
            DB::beginTransaction();
            
            // SECURITY: Force company_id to authenticated company
            $data['company_id'] = $this->company->id;
            
            $branch = Branch::create([
                'company_id' => $this->company->id, // CRITICAL: Use authenticated company
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? 'DE',
                'phone_number' => $data['phone_number'] ?? null,
                'email' => $data['email'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'calcom_event_type_id' => $data['calcom_event_type_id'] ?? null,
                'retell_agent_id' => $data['retell_agent_id'] ?? null,
                'timezone' => $data['timezone'] ?? 'Europe/Berlin'
            ]);
            
            // Create default working hours if requested
            if ($data['create_default_hours'] ?? false) {
                $this->createDefaultWorkingHours($branch->id);
            }
            
            DB::commit();
            
            Log::info('SecureBranchMCP: Created new branch', [
                'branch_id' => $branch->id,
                'company_id' => $this->company->id
            ]);
            
            $this->auditService->logDataModification('branch_created', [
                'branch_id' => $branch->id,
                'company_id' => $this->company->id
            ]);
            
            return [
                'success' => true,
                'message' => 'Branch created successfully',
                'data' => $branch,
                'branch_id' => $branch->id
            ];
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('SecureBranchMCP: Failed to create branch', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to create branch: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Update branch details (company-scoped)
     */
    public function updateBranch(string $branchId, array $data): array
    {
        try {
            // SECURITY: Verify branch belongs to company
            $branch = Branch::where('company_id', $this->company->id)
                ->find($branchId);
            
            if (!$branch) {
                $this->auditService->logSecurityEvent('branch_update_denied', [
                    'branch_id' => $branchId,
                    'company_id' => $this->company->id,
                    'reason' => 'Branch not found or access denied'
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Branch not found',
                    'data' => null
                ];
            }
            
            // SECURITY: Prevent changing company_id
            unset($data['company_id']);
            
            $branch->update($data);
            
            // Clear cache
            Cache::forget("branch_{$branchId}");
            Cache::tags(['branches', "company_{$this->company->id}"])->flush();
            
            Log::info('SecureBranchMCP: Updated branch', [
                'branch_id' => $branchId,
                'company_id' => $this->company->id,
                'updated_fields' => array_keys($data)
            ]);
            
            $this->auditService->logDataModification('branch_updated', [
                'branch_id' => $branchId,
                'company_id' => $this->company->id,
                'fields' => array_keys($data)
            ]);
            
            return [
                'success' => true,
                'message' => 'Branch updated successfully',
                'data' => $branch->fresh()
            ];
        } catch (\Exception $e) {
            Log::error('SecureBranchMCP: Failed to update branch', [
                'branch_id' => $branchId,
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to update branch: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Get branch staff members (company-scoped)
     */
    public function getBranchStaff(string $branchId): array
    {
        try {
            // SECURITY: Verify branch belongs to company
            $branch = Branch::where('company_id', $this->company->id)
                ->find($branchId);
                
            if (!$branch) {
                $this->auditService->logSecurityEvent('staff_access_denied', [
                    'branch_id' => $branchId,
                    'company_id' => $this->company->id,
                    'reason' => 'Branch not found or access denied'
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Branch not found',
                    'data' => []
                ];
            }
            
            $staff = StaffMember::where('branch_id', $branchId)
                ->with(['services', 'workingHours'])
                ->get();
                
            $this->auditService->logDataAccess('branch_staff_viewed', [
                'branch_id' => $branchId,
                'company_id' => $this->company->id,
                'staff_count' => $staff->count()
            ]);
                
            return [
                'success' => true,
                'data' => $staff->map(function($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'phone' => $member->phone,
                        'position' => $member->position,
                        'is_active' => $member->is_active,
                        'calcom_user_id' => $member->calcom_user_id,
                        'services' => $member->services->pluck('name'),
                        'has_working_hours' => $member->workingHours->count() > 0
                    ];
                }),
                'count' => $staff->count()
            ];
        } catch (\Exception $e) {
            Log::error('SecureBranchMCP: Failed to get branch staff', [
                'branch_id' => $branchId,
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get branch staff: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Get branch services (company-scoped)
     */
    public function getBranchServices(string $branchId): array
    {
        try {
            // SECURITY: Verify branch belongs to company
            $branch = Branch::where('company_id', $this->company->id)
                ->find($branchId);
                
            if (!$branch) {
                $this->auditService->logSecurityEvent('services_access_denied', [
                    'branch_id' => $branchId,
                    'company_id' => $this->company->id,
                    'reason' => 'Branch not found or access denied'
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Branch not found',
                    'data' => []
                ];
            }
            
            $services = Service::where('branch_id', $branchId)
                ->with(['staff'])
                ->get();
                
            $this->auditService->logDataAccess('branch_services_viewed', [
                'branch_id' => $branchId,
                'company_id' => $this->company->id,
                'services_count' => $services->count()
            ]);
                
            return [
                'success' => true,
                'data' => $services->map(function($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'description' => $service->description,
                        'duration' => $service->duration,
                        'price' => $service->price,
                        'is_active' => $service->is_active,
                        'staff_count' => $service->staff->count()
                    ];
                }),
                'count' => $services->count()
            ];
        } catch (\Exception $e) {
            Log::error('SecureBranchMCP: Failed to get branch services', [
                'branch_id' => $branchId,
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get branch services: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Get branch working hours (company-scoped)
     */
    public function getBranchWorkingHours(string $branchId): array
    {
        try {
            // SECURITY: Verify branch belongs to company
            $branch = Branch::where('company_id', $this->company->id)
                ->find($branchId);
                
            if (!$branch) {
                $this->auditService->logSecurityEvent('working_hours_access_denied', [
                    'branch_id' => $branchId,
                    'company_id' => $this->company->id,
                    'reason' => 'Branch not found or access denied'
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Branch not found',
                    'data' => []
                ];
            }
            
            $workingHours = WorkingHours::where('branch_id', $branchId)
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();
                
            $this->auditService->logDataAccess('working_hours_viewed', [
                'branch_id' => $branchId,
                'company_id' => $this->company->id
            ]);
                
            return [
                'success' => true,
                'data' => $workingHours->map(function($hours) {
                    return [
                        'id' => $hours->id,
                        'day_of_week' => $hours->day_of_week,
                        'day_name' => $this->getDayName($hours->day_of_week),
                        'start_time' => $hours->start_time,
                        'end_time' => $hours->end_time,
                        'is_closed' => $hours->is_closed
                    ];
                }),
                'formatted_hours' => $this->formatWorkingHours($workingHours)
            ];
        } catch (\Exception $e) {
            Log::error('SecureBranchMCP: Failed to get branch working hours', [
                'branch_id' => $branchId,
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get working hours: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Check if branch is open at specific time (company-scoped)
     */
    public function isBranchOpen(string $branchId, Carbon $dateTime = null): array
    {
        try {
            // SECURITY: Verify branch belongs to company
            $branch = Branch::where('company_id', $this->company->id)
                ->find($branchId);
                
            if (!$branch) {
                return [
                    'success' => false,
                    'message' => 'Branch not found',
                    'is_open' => false
                ];
            }
            
            if (!$dateTime) {
                $dateTime = Carbon::now();
            }
            
            $dayOfWeek = $dateTime->dayOfWeek; // 0 = Sunday, 6 = Saturday
            $time = $dateTime->format('H:i:00');
            
            $workingHours = WorkingHours::where('branch_id', $branchId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_closed', false)
                ->where('start_time', '<=', $time)
                ->where('end_time', '>', $time)
                ->exists();
                
            $this->auditService->logDataAccess('branch_open_status_checked', [
                'branch_id' => $branchId,
                'company_id' => $this->company->id,
                'checked_at' => $dateTime->toDateTimeString(),
                'is_open' => $workingHours
            ]);
                
            return [
                'success' => true,
                'is_open' => $workingHours,
                'checked_at' => $dateTime->toDateTimeString(),
                'day_of_week' => $dayOfWeek,
                'time' => $time
            ];
        } catch (\Exception $e) {
            Log::error('SecureBranchMCP: Failed to check if branch is open', [
                'branch_id' => $branchId,
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to check branch status: ' . $e->getMessage(),
                'is_open' => false
            ];
        }
    }
    
    /**
     * Create default working hours for a branch
     */
    protected function createDefaultWorkingHours(string $branchId): void
    {
        $defaultHours = [
            // Monday to Friday
            ['day_of_week' => 1, 'start_time' => '09:00:00', 'end_time' => '18:00:00'],
            ['day_of_week' => 2, 'start_time' => '09:00:00', 'end_time' => '18:00:00'],
            ['day_of_week' => 3, 'start_time' => '09:00:00', 'end_time' => '18:00:00'],
            ['day_of_week' => 4, 'start_time' => '09:00:00', 'end_time' => '18:00:00'],
            ['day_of_week' => 5, 'start_time' => '09:00:00', 'end_time' => '18:00:00'],
            // Saturday - shorter hours
            ['day_of_week' => 6, 'start_time' => '09:00:00', 'end_time' => '14:00:00'],
            // Sunday - closed
            ['day_of_week' => 0, 'is_closed' => true]
        ];
        
        foreach ($defaultHours as $hours) {
            WorkingHours::create(array_merge($hours, ['branch_id' => $branchId]));
        }
    }
    
    /**
     * Get day name from day of week number
     */
    protected function getDayName(int $dayOfWeek): string
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday'
        ];
        
        return $days[$dayOfWeek] ?? 'Unknown';
    }
    
    /**
     * Format working hours for display
     */
    protected function formatWorkingHours($workingHours): array
    {
        $formatted = [];
        
        foreach ($workingHours as $hours) {
            $day = $this->getDayName($hours->day_of_week);
            
            if ($hours->is_closed) {
                $formatted[$day] = 'Closed';
            } else {
                $start = Carbon::createFromFormat('H:i:s', $hours->start_time)->format('H:i');
                $end = Carbon::createFromFormat('H:i:s', $hours->end_time)->format('H:i');
                $formatted[$day] = "{$start} - {$end}";
            }
        }
        
        return $formatted;
    }
}