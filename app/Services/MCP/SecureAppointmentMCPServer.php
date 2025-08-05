<?php

namespace App\Services\MCP;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * SECURE VERSION: Appointment MCP Server with proper tenant isolation
 * 
 * This MCP server handles appointment operations while maintaining strict
 * tenant boundaries. All operations validate company context.
 */
class SecureAppointmentMCPServer
{
    protected DatabaseMCPServer $databaseMCP;
    
    public function __construct(DatabaseMCPServer $databaseMCP)
    {
        $this->databaseMCP = $databaseMCP;
    }
    
    /**
     * Get appointment details with company validation
     */
    public function getAppointment(int $appointmentId, ?int $companyId = null): array
    {
        try {
            // Resolve company context
            $companyId = $this->resolveCompanyContext($companyId);
            
            if (!$companyId) {
                return [
                    'success' => false,
                    'message' => 'Company context required',
                    'data' => null
                ];
            }
            
            // Get appointment with company validation through branch
            $appointment = Appointment::with(['customer', 'staff', 'service', 'branch'])
                ->whereHas('branch', function($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->find($appointmentId);
                
            if (!$appointment) {
                $this->auditUnauthorizedAccess('appointment_not_found', [
                    'appointment_id' => $appointmentId,
                    'company_id' => $companyId
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Appointment not found or access denied',
                    'data' => null
                ];
            }
            
            // Audit successful access
            $this->auditAccess('appointment_retrieved', [
                'appointment_id' => $appointmentId,
                'company_id' => $companyId,
                'branch_id' => $appointment->branch_id
            ]);
            
            return [
                'success' => true,
                'data' => $this->formatAppointmentData($appointment)
            ];
            
        } catch (\Exception $e) {
            Log::error('SecureAppointmentMCP: Failed to get appointment', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointmentId
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve appointment',
                'data' => null
            ];
        }
    }
    
    /**
     * Create appointment with full company validation
     */
    public function createAppointment(array $data, ?int $companyId = null): array
    {
        try {
            // Resolve company context
            $companyId = $this->resolveCompanyContext($companyId);
            
            if (!$companyId) {
                return [
                    'success' => false,
                    'message' => 'Company context required',
                    'data' => null
                ];
            }
            
            // Validate branch belongs to company
            $branch = Branch::where('id', $data['branch_id'])
                ->where('company_id', $companyId)
                ->first();
                
            if (!$branch) {
                return [
                    'success' => false,
                    'message' => 'Invalid branch or access denied',
                    'data' => null
                ];
            }
            
            // Validate customer belongs to company
            if (!empty($data['customer_id'])) {
                $customer = Customer::where('id', $data['customer_id'])
                    ->where('company_id', $companyId)
                    ->first();
                    
                if (!$customer) {
                    return [
                        'success' => false,
                        'message' => 'Invalid customer or access denied',
                        'data' => null
                    ];
                }
            }
            
            // Validate staff belongs to company
            if (!empty($data['staff_id'])) {
                $staff = Staff::where('id', $data['staff_id'])
                    ->where('company_id', $companyId)
                    ->first();
                    
                if (!$staff) {
                    return [
                        'success' => false,
                        'message' => 'Invalid staff or access denied',
                        'data' => null
                    ];
                }
            }
            
            // Validate service belongs to company
            if (!empty($data['service_id'])) {
                $service = Service::where('id', $data['service_id'])
                    ->where('company_id', $companyId)
                    ->first();
                    
                if (!$service) {
                    return [
                        'success' => false,
                        'message' => 'Invalid service or access denied',
                        'data' => null
                    ];
                }
            }
            
            // Create appointment
            $appointment = Appointment::create([
                'branch_id' => $branch->id,
                'customer_id' => $data['customer_id'] ?? null,
                'staff_id' => $data['staff_id'] ?? null,
                'service_id' => $data['service_id'] ?? null,
                'starts_at' => Carbon::parse($data['starts_at']),
                'ends_at' => Carbon::parse($data['ends_at']),
                'status' => $data['status'] ?? 'scheduled',
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'price' => $data['price'] ?? null,
                'is_paid' => $data['is_paid'] ?? false,
                'payment_method' => $data['payment_method'] ?? null,
                'reminder_sent' => false,
                'metadata' => $data['metadata'] ?? []
            ]);
            
            // Audit creation
            $this->auditAccess('appointment_created', [
                'appointment_id' => $appointment->id,
                'company_id' => $companyId,
                'branch_id' => $branch->id,
                'customer_id' => $appointment->customer_id
            ]);
            
            // Load relationships for response
            $appointment->load(['customer', 'staff', 'service', 'branch']);
            
            return [
                'success' => true,
                'message' => 'Appointment created successfully',
                'data' => $this->formatAppointmentData($appointment)
            ];
            
        } catch (\Exception $e) {
            Log::error('SecureAppointmentMCP: Failed to create appointment', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to create appointment: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Check availability with company validation
     */
    public function checkAvailability(array $params, ?int $companyId = null): array
    {
        try {
            // Resolve company context
            $companyId = $this->resolveCompanyContext($companyId);
            
            if (!$companyId) {
                return [
                    'success' => false,
                    'message' => 'Company context required',
                    'data' => null
                ];
            }
            
            $date = Carbon::parse($params['date']);
            $duration = $params['duration'] ?? 30; // minutes
            
            // Validate branch if provided
            $branchId = null;
            if (!empty($params['branch_id'])) {
                $branch = Branch::where('id', $params['branch_id'])
                    ->where('company_id', $companyId)
                    ->first();
                    
                if (!$branch) {
                    return [
                        'success' => false,
                        'message' => 'Invalid branch or access denied',
                        'data' => null
                    ];
                }
                $branchId = $branch->id;
            }
            
            // Validate staff if provided
            $staffId = null;
            if (!empty($params['staff_id'])) {
                $staff = Staff::where('id', $params['staff_id'])
                    ->where('company_id', $companyId)
                    ->first();
                    
                if (!$staff) {
                    return [
                        'success' => false,
                        'message' => 'Invalid staff or access denied',
                        'data' => null
                    ];
                }
                $staffId = $staff->id;
            }
            
            // Get working hours (simplified - should check branch/staff specific hours)
            $workingHours = [
                'start' => '09:00',
                'end' => '18:00',
                'slot_duration' => $duration
            ];
            
            // Get existing appointments for the day
            $existingAppointments = Appointment::whereDate('starts_at', $date)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->when($staffId, fn($q) => $q->where('staff_id', $staffId))
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->orderBy('starts_at')
                ->get(['starts_at', 'ends_at']);
            
            // Generate available slots
            $availableSlots = $this->generateAvailableSlots(
                $date,
                $workingHours,
                $existingAppointments,
                $duration
            );
            
            return [
                'success' => true,
                'data' => [
                    'date' => $date->format('Y-m-d'),
                    'duration' => $duration,
                    'available_slots' => $availableSlots,
                    'working_hours' => $workingHours
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('SecureAppointmentMCP: Failed to check availability', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to check availability',
                'data' => null
            ];
        }
    }
    
    /**
     * Get appointments for date range with company validation
     */
    public function getAppointments(array $filters, ?int $companyId = null): array
    {
        try {
            // Resolve company context
            $companyId = $this->resolveCompanyContext($companyId);
            
            if (!$companyId) {
                return [
                    'success' => false,
                    'message' => 'Company context required',
                    'data' => null
                ];
            }
            
            $query = Appointment::with(['customer', 'staff', 'service', 'branch'])
                ->whereHas('branch', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            
            // Apply filters
            if (!empty($filters['branch_id'])) {
                // Validate branch belongs to company
                $branch = Branch::where('id', $filters['branch_id'])
                    ->where('company_id', $companyId)
                    ->first();
                    
                if ($branch) {
                    $query->where('branch_id', $branch->id);
                }
            }
            
            if (!empty($filters['staff_id'])) {
                // Validate staff belongs to company
                $staff = Staff::where('id', $filters['staff_id'])
                    ->where('company_id', $companyId)
                    ->first();
                    
                if ($staff) {
                    $query->where('staff_id', $staff->id);
                }
            }
            
            if (!empty($filters['customer_id'])) {
                // Validate customer belongs to company
                $customer = Customer::where('id', $filters['customer_id'])
                    ->where('company_id', $companyId)
                    ->first();
                    
                if ($customer) {
                    $query->where('customer_id', $customer->id);
                }
            }
            
            if (!empty($filters['date_from'])) {
                $query->where('starts_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
            }
            
            if (!empty($filters['date_to'])) {
                $query->where('starts_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
            }
            
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            
            $appointments = $query->orderBy('starts_at', 'asc')
                ->limit($filters['limit'] ?? 100)
                ->get();
            
            return [
                'success' => true,
                'data' => $appointments->map(fn($apt) => $this->formatAppointmentData($apt))->toArray()
            ];
            
        } catch (\Exception $e) {
            Log::error('SecureAppointmentMCP: Failed to get appointments', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve appointments',
                'data' => null
            ];
        }
    }
    
    /**
     * Format appointment data for response
     */
    protected function formatAppointmentData(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'branch' => [
                'id' => $appointment->branch_id,
                'name' => $appointment->branch->name ?? null
            ],
            'customer' => $appointment->customer ? [
                'id' => $appointment->customer->id,
                'name' => $appointment->customer->name,
                'phone' => $appointment->customer->phone,
                'email' => $appointment->customer->email
            ] : null,
            'staff' => $appointment->staff ? [
                'id' => $appointment->staff->id,
                'name' => $appointment->staff->name
            ] : null,
            'service' => $appointment->service ? [
                'id' => $appointment->service->id,
                'name' => $appointment->service->name,
                'duration' => $appointment->service->duration,
                'price' => $appointment->service->price
            ] : null,
            'starts_at' => $appointment->starts_at->toIso8601String(),
            'ends_at' => $appointment->ends_at->toIso8601String(),
            'duration_minutes' => $appointment->starts_at->diffInMinutes($appointment->ends_at),
            'status' => $appointment->status,
            'notes' => $appointment->notes,
            'internal_notes' => $appointment->internal_notes,
            'price' => $appointment->service->price,
            'is_paid' => $appointment->is_paid,
            'payment_method' => $appointment->payment_method,
            'reminder_sent' => $appointment->reminder_sent,
            'created_at' => $appointment->created_at->toIso8601String(),
            'updated_at' => $appointment->updated_at->toIso8601String()
        ];
    }
    
    /**
     * Generate available time slots
     */
    protected function generateAvailableSlots(Carbon $date, array $workingHours, $existingAppointments, int $duration): array
    {
        $slots = [];
        $start = $date->copy()->setTimeFromTimeString($workingHours['start']);
        $end = $date->copy()->setTimeFromTimeString($workingHours['end']);
        
        $current = $start->copy();
        
        while ($current->copy()->addMinutes($duration)->lte($end)) {
            $slotEnd = $current->copy()->addMinutes($duration);
            
            // Check if slot conflicts with existing appointments
            $hasConflict = $existingAppointments->some(function($apt) use ($current, $slotEnd) {
                $aptStart = Carbon::parse($apt->starts_at);
                $aptEnd = Carbon::parse($apt->ends_at);
                
                return !($slotEnd->lte($aptStart) || $current->gte($aptEnd));
            });
            
            if (!$hasConflict) {
                $slots[] = [
                    'start' => $current->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                    'datetime' => $current->toIso8601String()
                ];
            }
            
            $current->addMinutes(15); // 15-minute intervals
        }
        
        return $slots;
    }
    
    /**
     * Resolve company context
     */
    protected function resolveCompanyContext(?int $companyId): ?int
    {
        // If company ID provided, validate it exists
        if ($companyId) {
            $exists = Company::where('id', $companyId)
                ->where('is_active', true)
                ->exists();
                
            return $exists ? $companyId : null;
        }
        
        // Try from authenticated user
        if (auth()->check() && auth()->user()->company_id) {
            return auth()->user()->company_id;
        }
        
        // Try from request context
        if (request()->has('company_id')) {
            return $this->resolveCompanyContext(request()->input('company_id'));
        }
        
        return null;
    }
    
    /**
     * Audit access attempts
     */
    protected function auditAccess(string $action, array $context): void
    {
        if (DB::connection()->getSchemaBuilder()->hasTable('security_audit_logs')) {
            DB::table('security_audit_logs')->insert([
                'event_type' => 'appointment_mcp_' . $action,
                'user_id' => auth()->id(),
                'company_id' => $context['company_id'] ?? null,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'url' => request()->fullUrl() ?? 'mcp',
                'metadata' => json_encode($context),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
    
    /**
     * Audit unauthorized access
     */
    protected function auditUnauthorizedAccess(string $action, array $context): void
    {
        Log::warning('SecureAppointmentMCP: Unauthorized access attempt', [
            'action' => $action,
            'context' => $context,
            'user_id' => auth()->id(),
            'ip' => request()->ip()
        ]);
        
        $this->auditAccess('unauthorized_' . $action, $context);
    }
}