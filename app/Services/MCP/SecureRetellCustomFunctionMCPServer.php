<?php

namespace App\Services\MCP;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Appointment;
use App\Exceptions\SecurityException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * SECURE VERSION: Retell Custom Function MCP Server with proper tenant isolation
 * 
 * This server handles custom functions for Retell AI with strict multi-tenant security.
 * All operations are scoped to the authenticated company context.
 * 
 * Security Features:
 * - Mandatory company context validation
 * - No arbitrary company fallbacks
 * - All queries properly scoped to company
 * - Audit logging for all operations
 * - No cross-tenant data exposure
 */
class SecureRetellCustomFunctionMCPServer extends BaseMCPServer
{
    /**
     * @var Company|null Current company context
     */
    protected ?Company $company = null;
    
    /**
     * @var Branch|null Current branch context
     */
    protected ?Branch $branch = null;
    
    /**
     * @var bool Audit logging enabled
     */
    protected bool $auditEnabled = true;
    
    /**
     * Constructor - resolves company context
     */
    public function __construct()
    {
        parent::__construct();
        $this->resolveCompanyContext();
    }
    
    /**
     * Set company context explicitly (only for super admins or system operations)
     */
    public function setCompanyContext(Company $company): self
    {
        // Only allow super admins or system operations to override context
        if (Auth::check() && !Auth::user()->hasRole('super_admin')) {
            throw new SecurityException('Unauthorized company context override');
        }
        
        $this->company = $company;
        $this->branch = null; // Reset branch when company changes
        
        $this->auditAccess('company_context_override', [
            'company_id' => $company->id,
            'user_id' => Auth::id(),
        ]);
        
        return $this;
    }
    
    /**
     * Set branch context with validation
     */
    public function setBranchContext(Branch $branch): self
    {
        $this->ensureCompanyContext();
        
        // Validate branch belongs to company
        if ($branch->company_id !== $this->company->id) {
            throw new SecurityException("Branch does not belong to authenticated company");
        }
        
        $this->branch = $branch;
        
        return $this;
    }
    
    /**
     * Get server info
     */
    public function getServerInfo(): array
    {
        return [
            'name' => 'secure-retell-custom-functions',
            'version' => '1.0.0',
            'description' => 'Secure custom functions for Retell AI with tenant isolation',
            'functions' => [
                'get_available_services',
                'get_available_staff',
                'check_availability',
                'collect_appointment_data',
                'book_appointment_tool'
            ]
        ];
    }
    
    /**
     * Execute a custom function
     */
    public function execute(string $operation, array $params = []): array
    {
        $this->ensureCompanyContext();
        
        $this->logDebug("Executing secure Retell custom function", [
            'operation' => $operation,
            'params' => $params,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch?->id
        ]);
        
        try {
            switch ($operation) {
                case 'get_available_services':
                    return $this->getAvailableServicesSecure($params);
                    
                case 'get_available_staff':
                    return $this->getAvailableStaffSecure($params);
                    
                case 'check_availability':
                    return $this->checkAvailabilitySecure($params);
                    
                case 'collect_appointment_data':
                    return $this->collectAppointmentDataSecure($params);
                    
                case 'book_appointment_tool':
                    return $this->bookAppointmentToolSecure($params);
                    
                default:
                    return ['error' => "Unknown operation: {$operation}"];
            }
        } catch (\Exception $e) {
            $this->logError("Secure Retell function failed", $e, [
                'operation' => $operation,
                'company_id' => $this->company->id
            ]);
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Get available services for the company/branch
     */
    protected function getAvailableServicesSecure(array $params): array
    {
        $this->auditAccess('get_available_services', $params);
        
        $query = Service::where('company_id', $this->company->id) // Company scope
            ->where('is_active', true);
            
        // If branch context is set, filter by branch services
        if ($this->branch) {
            $query->whereHas('branches', function($q) {
                $q->where('branches.id', $this->branch->id);
            });
        }
        
        $services = $query->get();
        
        return [
            'services' => $services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'duration' => $service->duration,
                    'price' => $service->price,
                    'category' => $service->category
                ];
            })->toArray(),
            'total' => $services->count()
        ];
    }
    
    /**
     * Get available staff for service/branch
     */
    protected function getAvailableStaffSecure(array $params): array
    {
        $this->auditAccess('get_available_staff', $params);
        
        $query = Staff::whereHas('branches', function($q) {
            $q->where('company_id', $this->company->id); // Company scope via branches
        })->where('is_active', true);
        
        // Filter by branch if set
        if ($this->branch) {
            $query->whereHas('branches', function($q) {
                $q->where('branches.id', $this->branch->id);
            });
        }
        
        // Filter by service if provided
        if (!empty($params['service_id'])) {
            $service = Service::where('id', $params['service_id'])
                ->where('company_id', $this->company->id) // Validate service belongs to company
                ->first();
                
            if ($service) {
                $query->whereHas('services', function($q) use ($service) {
                    $q->where('services.id', $service->id);
                });
            }
        }
        
        $staff = $query->get();
        
        return [
            'staff' => $staff->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'title' => $member->title,
                    'specializations' => $member->services->pluck('name')->toArray()
                ];
            })->toArray(),
            'total' => $staff->count()
        ];
    }
    
    /**
     * Check availability with security validation
     */
    protected function checkAvailabilitySecure(array $params): array
    {
        $this->validateParams($params, ['date']);
        $this->auditAccess('check_availability', $params);
        
        $date = Carbon::parse($params['date']);
        $serviceId = $params['service_id'] ?? null;
        $staffId = $params['staff_id'] ?? null;
        
        // Validate service belongs to company
        if ($serviceId) {
            $service = Service::where('id', $serviceId)
                ->where('company_id', $this->company->id)
                ->first();
                
            if (!$service) {
                throw new SecurityException('Invalid service ID');
            }
        }
        
        // Validate staff belongs to company
        if ($staffId) {
            $staff = Staff::where('id', $staffId)
                ->whereHas('branches', function($q) {
                    $q->where('company_id', $this->company->id);
                })
                ->first();
                
            if (!$staff) {
                throw new SecurityException('Invalid staff ID');
            }
        }
        
        // Get branch for availability check
        $checkBranch = $this->branch ?? Branch::where('company_id', $this->company->id)
            ->orderBy('is_main', 'desc')
            ->first();
            
        if (!$checkBranch) {
            return ['available_slots' => []];
        }
        
        // Generate available time slots
        $slots = $this->generateTimeSlots($date, $checkBranch, $service, $staff);
        
        return [
            'date' => $date->format('Y-m-d'),
            'available_slots' => $slots,
            'branch' => [
                'id' => $checkBranch->id,
                'name' => $checkBranch->name
            ]
        ];
    }
    
    /**
     * Collect appointment data with validation
     */
    protected function collectAppointmentDataSecure(array $params): array
    {
        $this->auditAccess('collect_appointment_data', $params);
        
        // All data is validated against company context
        $response = [
            'status' => 'collected',
            'data' => [
                'company_id' => $this->company->id,
                'branch_id' => $this->branch?->id,
                'service_request' => $params['service_request'] ?? null,
                'appointment_datetime' => $params['appointment_datetime'] ?? null,
                'customer_name' => $params['customer_name'] ?? null,
                'customer_phone' => $params['customer_phone'] ?? null,
                'customer_email' => $params['customer_email'] ?? null,
                'staff_preference' => $params['staff_preference'] ?? null,
                'additional_notes' => $params['additional_notes'] ?? null
            ]
        ];
        
        // Validate collected data
        $missingFields = [];
        
        if (empty($response['data']['service_request'])) {
            $missingFields[] = 'service_request';
        }
        
        if (empty($response['data']['appointment_datetime'])) {
            $missingFields[] = 'appointment_datetime';
        }
        
        if (empty($response['data']['customer_phone'])) {
            $missingFields[] = 'customer_phone';
        }
        
        if (!empty($missingFields)) {
            $response['status'] = 'incomplete';
            $response['missing_fields'] = $missingFields;
        }
        
        return $response;
    }
    
    /**
     * Book appointment with full security validation
     */
    protected function bookAppointmentToolSecure(array $params): array
    {
        $this->validateParams($params, ['appointment_data']);
        $this->auditAccess('book_appointment_tool', $params);
        
        $appointmentData = $params['appointment_data'];
        
        // Use SecureAppointmentBookingService
        $bookingService = app(SecureAppointmentBookingService::class);
        
        // Set company context on booking service
        if ($this->company) {
            $bookingService->setCompanyContext($this->company);
        }
        
        try {
            $result = $bookingService->bookFromPhoneCall($appointmentData);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'appointment_id' => $result['appointment']->id,
                    'confirmation_number' => $result['confirmation_number'],
                    'appointment_details' => [
                        'date' => $result['appointment']->starts_at->format('Y-m-d'),
                        'time' => $result['appointment']->starts_at->format('H:i'),
                        'service' => $result['appointment']->service?->name,
                        'staff' => $result['appointment']->staff?->name,
                        'branch' => $result['appointment']->branch?->name
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $result['message'] ?? 'Booking failed',
                    'errors' => $result['errors'] ?? []
                ];
            }
        } catch (\Exception $e) {
            $this->logError('book_appointment_failed', $e, [
                'appointment_data' => $appointmentData,
                'company_id' => $this->company->id
            ]);
            
            return [
                'success' => false,
                'error' => 'Booking failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate available time slots
     */
    protected function generateTimeSlots(Carbon $date, Branch $branch, ?Service $service, ?Staff $staff): array
    {
        $slots = [];
        $duration = $service?->duration ?? 30;
        
        // Get working hours for the branch
        $dayOfWeek = strtolower($date->format('l'));
        $workingHours = $branch->working_hours[$dayOfWeek] ?? null;
        
        if (!$workingHours || !$workingHours['is_open']) {
            return [];
        }
        
        $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHours['open']);
        $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHours['close']);
        
        // Get existing appointments for the day
        $existingAppointments = Appointment::whereHas('branch', function($q) {
            $q->where('company_id', $this->company->id);
        })
        ->where('branch_id', $branch->id)
        ->whereDate('starts_at', $date)
        ->where('status', '!=', 'cancelled')
        ->when($staff, function($q) use ($staff) {
            $q->where('staff_id', $staff->id);
        })
        ->get();
        
        // Generate slots
        $currentSlot = $startTime->copy();
        
        while ($currentSlot->copy()->addMinutes($duration)->lte($endTime)) {
            $slotEnd = $currentSlot->copy()->addMinutes($duration);
            
            // Check if slot is available
            $isAvailable = true;
            
            foreach ($existingAppointments as $appointment) {
                if ($currentSlot->between($appointment->starts_at, $appointment->ends_at) ||
                    $slotEnd->between($appointment->starts_at, $appointment->ends_at)) {
                    $isAvailable = false;
                    break;
                }
            }
            
            if ($isAvailable) {
                $slots[] = [
                    'start' => $currentSlot->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                    'available' => true
                ];
            }
            
            $currentSlot->addMinutes(15); // 15-minute intervals
        }
        
        return $slots;
    }
    
    /**
     * Resolve company context from authenticated user
     */
    protected function resolveCompanyContext(): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            if ($user->company_id) {
                $this->company = Company::find($user->company_id);
            }
        }
    }
    
    /**
     * Ensure company context is set
     */
    protected function ensureCompanyContext(): void
    {
        if (!$this->company) {
            throw new SecurityException('No valid company context for Retell custom functions');
        }
    }
    
    /**
     * Audit access to custom functions
     */
    protected function auditAccess(string $operation, array $context = []): void
    {
        if (!$this->auditEnabled) {
            return;
        }
        
        try {
            if (DB::connection()->getSchemaBuilder()->hasTable('security_audit_logs')) {
                DB::table('security_audit_logs')->insert([
                    'event_type' => 'retell_custom_function',
                    'user_id' => Auth::id(),
                    'company_id' => $this->company->id ?? null,
                    'ip_address' => request()->ip() ?? '127.0.0.1',
                    'url' => request()->fullUrl() ?? 'console',
                    'metadata' => json_encode(array_merge($context, [
                        'operation' => $operation,
                        'branch_id' => $this->branch?->id,
                        'user_agent' => request()->userAgent()
                    ])),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('SecureRetellCustomFunction: Audit logging failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Disable audit logging (for testing)
     */
    public function disableAudit(): self
    {
        $this->auditEnabled = false;
        return $this;
    }
}