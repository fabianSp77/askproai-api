<?php

namespace App\Jobs;

use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use App\Services\CalcomV2Service;
use App\Repositories\CustomerRepository;
use App\Helpers\TenantHelper;
use Carbon\Carbon;

/**
 * Secure Tenant-Aware Retell Call Processing Job
 * 
 * Example implementation showing how to extend TenantAwareJob for
 * secure multi-tenant background processing with comprehensive
 * tenant isolation and audit logging.
 */
class ProcessRetellCallJobSecure extends TenantAwareJob
{
    protected array $payload;
    protected bool $requiresTenantContext = true;
    
    public function __construct(array $payload)
    {
        $this->payload = $payload;
        parent::__construct();
    }
    
    /**
     * Execute the job with tenant context
     */
    protected function execute(): void
    {
        // Validate payload has required fields
        $this->validatePayload();
        
        // Process the call with tenant isolation
        $call = $this->createCallRecord();
        
        // Extract customer information and handle customer creation/lookup
        $customer = $this->handleCustomer();
        
        // Update call with customer reference
        $call->update(['customer_id' => $customer->id]);
        
        // Check if appointment booking is requested
        if ($this->shouldCreateAppointment()) {
            $appointment = $this->createAppointment($customer);
            
            if ($appointment) {
                $call->update(['appointment_id' => $appointment->id]);
            }
        }
        
        // Log successful processing
        $this->auditJobExecution('call_processed_successfully', [
            'call_id' => $call->id,
            'customer_id' => $customer->id,
            'company_id' => $this->getTenantCompanyId(),
            'has_appointment' => isset($appointment)
        ]);
    }
    
    /**
     * Validate the webhook payload
     */
    protected function validatePayload(): void
    {
        $required = ['call_id', 'phone_number'];
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($this->payload[$field]) || empty($this->payload[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Missing required payload fields: ' . implode(', ', $missing)
            );
        }
    }
    
    /**
     * Create call record with tenant context
     */
    protected function createCallRecord(): Call
    {
        $callData = [
            'external_id' => $this->payload['call_id'],
            'phone_number' => $this->payload['phone_number'],
            'transcript' => $this->payload['transcript'] ?? '',
            'duration_seconds' => $this->payload['duration_seconds'] ?? 0,
            'call_status' => $this->payload['call_status'] ?? 'completed',
            'raw_payload' => $this->payload,
            'processed_at' => now(),
            // company_id will be auto-assigned by the model's BelongsToCompany trait
        ];
        
        // Use tenant-scoped model creation
        $call = $this->getTenantModel(Call::class)->create($callData);
        
        $this->auditJobExecution('call_record_created', [
            'call_id' => $call->id,
            'external_id' => $call->external_id,
            'company_id' => $call->company_id
        ]);
        
        return $call;
    }
    
    /**
     * Handle customer creation/lookup with tenant isolation
     */
    protected function handleCustomer(): Customer
    {
        $phoneNumber = $this->payload['phone_number'];
        $customerData = $this->extractCustomerData();
        
        // Use tenant-aware repository
        $customerRepository = app(CustomerRepository::class);
        
        // Find or create customer within current tenant
        $customer = $customerRepository->findOrCreateByPhone($phoneNumber, $customerData);
        
        $this->auditJobExecution('customer_handled', [
            'customer_id' => $customer->id,
            'phone_number' => $phoneNumber,
            'was_created' => $customer->wasRecentlyCreated,
            'company_id' => $customer->company_id
        ]);
        
        return $customer;
    }
    
    /**
     * Extract customer data from payload
     */
    protected function extractCustomerData(): array
    {
        $data = [];
        
        if (isset($this->payload['customer_name'])) {
            $data['name'] = $this->payload['customer_name'];
        }
        
        if (isset($this->payload['customer_email'])) {
            $data['email'] = $this->payload['customer_email'];
        }
        
        // Add any other customer fields from the call transcript or metadata
        if (isset($this->payload['customer_metadata'])) {
            $data = array_merge($data, $this->payload['customer_metadata']);
        }
        
        return $data;
    }
    
    /**
     * Check if appointment should be created
     */
    protected function shouldCreateAppointment(): bool
    {
        return isset($this->payload['appointment_requested']) && 
               $this->payload['appointment_requested'] === true;
    }
    
    /**
     * Create appointment with tenant validation
     */
    protected function createAppointment(Customer $customer): ?Appointment
    {
        try {
            $appointmentData = $this->extractAppointmentData($customer);
            
            // Validate appointment time is available
            if (!$this->isTimeSlotAvailable($appointmentData['starts_at'], $appointmentData['branch_id'])) {
                $this->auditJobExecution('appointment_time_unavailable', [
                    'requested_time' => $appointmentData['starts_at'],
                    'branch_id' => $appointmentData['branch_id']
                ]);
                return null;
            }
            
            // Create appointment using tenant-scoped model
            $appointment = $this->getTenantModel(Appointment::class)->create($appointmentData);
            
            // Sync with Cal.com if integration is active
            $this->syncWithCalcom($appointment);
            
            $this->auditJobExecution('appointment_created', [
                'appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
                'starts_at' => $appointment->starts_at,
                'company_id' => $appointment->company_id
            ]);
            
            return $appointment;
            
        } catch (\Exception $e) {
            $this->auditJobExecution('appointment_creation_failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'company_id' => $this->getTenantCompanyId()
            ]);
            
            // Don't fail the entire job if appointment creation fails
            return null;
        }
    }
    
    /**
     * Extract appointment data from payload
     */
    protected function extractAppointmentData(Customer $customer): array
    {
        $requestedTime = $this->payload['appointment_time'] ?? null;
        $serviceType = $this->payload['service_type'] ?? 'consultation';
        $branchId = $this->payload['branch_id'] ?? $this->getDefaultBranchId();
        
        // Default to next available slot if no time specified
        if (!$requestedTime) {
            $requestedTime = $this->findNextAvailableSlot($branchId);
        }
        
        $startsAt = Carbon::parse($requestedTime);
        $endsAt = $startsAt->copy()->addMinutes(30); // Default 30-minute appointment
        
        return [
            'customer_id' => $customer->id,
            'branch_id' => $branchId,
            'service_type' => $serviceType,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'scheduled',
            'notes' => $this->payload['appointment_notes'] ?? 'Created from phone call',
            'source' => 'retell_ai',
            // company_id will be auto-assigned
        ];
    }
    
    /**
     * Check if time slot is available (tenant-scoped)
     */
    protected function isTimeSlotAvailable(Carbon $startTime, int $branchId): bool
    {
        $endTime = $startTime->copy()->addMinutes(30);
        
        // Check for conflicting appointments in the same branch and tenant
        $conflicts = $this->getTenantModel(Appointment::class)
            ->where('branch_id', $branchId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('starts_at', [$startTime, $endTime])
                      ->orWhereBetween('ends_at', [$startTime, $endTime])
                      ->orWhere(function ($q) use ($startTime, $endTime) {
                          $q->where('starts_at', '<=', $startTime)
                            ->where('ends_at', '>=', $endTime);
                      });
            })
            ->exists();
            
        return !$conflicts;
    }
    
    /**
     * Get default branch ID for current tenant
     */
    protected function getDefaultBranchId(): ?int
    {
        $branch = $this->getTenantModel('App\Models\Branch')->first();
        return $branch?->id;
    }
    
    /**
     * Find next available time slot
     */
    protected function findNextAvailableSlot(int $branchId): Carbon
    {
        $startTime = now()->addHours(1)->startOfHour(); // Start from next hour
        
        for ($i = 0; $i < 48; $i++) { // Check next 48 hours
            $candidateTime = $startTime->copy()->addHours($i);
            
            // Skip non-business hours (9 AM - 5 PM)
            if ($candidateTime->hour < 9 || $candidateTime->hour >= 17) {
                continue;
            }
            
            // Skip weekends
            if ($candidateTime->isWeekend()) {
                continue;
            }
            
            if ($this->isTimeSlotAvailable($candidateTime, $branchId)) {
                return $candidateTime;
            }
        }
        
        // Fallback to tomorrow at 9 AM
        return now()->addDay()->setTime(9, 0);
    }
    
    /**
     * Sync appointment with Cal.com (if integration is active)
     */
    protected function syncWithCalcom(Appointment $appointment): void
    {
        try {
            $calcomService = app(CalcomV2Service::class);
            
            // This would use the tenant's Cal.com integration settings
            $booking = $calcomService->createBooking([
                'eventTypeId' => $this->getCalcomEventTypeId(),
                'start' => $appointment->starts_at->toISOString(),
                'end' => $appointment->ends_at->toISOString(),
                'attendees' => [[
                    'email' => $appointment->customer->email ?? 'noreply@askproai.de',
                    'name' => $appointment->customer->name ?? 'Unknown Customer',
                ]],
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'source' => 'retell_ai'
                ]
            ]);
            
            if ($booking->successful()) {
                $appointment->update([
                    'external_id' => $booking->json('uid'),
                    'sync_status' => 'synced'
                ]);
                
                $this->auditJobExecution('calcom_sync_success', [
                    'appointment_id' => $appointment->id,
                    'calcom_uid' => $booking->json('uid')
                ]);
            }
            
        } catch (\Exception $e) {
            $this->auditJobExecution('calcom_sync_failed', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            
            // Update appointment status but don't fail the job
            $appointment->update(['sync_status' => 'sync_failed']);
        }
    }
    
    /**
     * Get Cal.com event type ID for current tenant
     */
    protected function getCalcomEventTypeId(): ?string
    {
        // Get tenant-specific event type configuration
        $integration = $this->getTenantModel('App\Models\Integration')
            ->where('system', 'calcom')
            ->where('active', true)
            ->first();
            
        return $integration?->settings['default_event_type_id'] ?? config('services.calcom.default_event_type_id');
    }
    
    /**
     * Handle job failure with tenant context
     */
    public function failed(\Throwable $exception): void
    {
        $this->auditJobExecution('job_failed_with_context', [
            'error' => $exception->getMessage(),
            'payload' => $this->payload,
            'company_id' => $this->getTenantCompanyId(),
            'exception_class' => get_class($exception)
        ]);
        
        parent::failed($exception);
    }
}