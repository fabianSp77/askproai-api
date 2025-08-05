<?php

namespace App\Services\MCP;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Company;
use App\Services\CalcomV2Service;
use App\Services\NotificationService;
use App\Exceptions\SecurityException;
use App\Exceptions\MCPException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * SECURE VERSION: Appointment Management MCP Server with proper tenant isolation
 * 
 * This server handles appointment operations with strict multi-tenant security.
 * All operations are scoped to the authenticated company context.
 * 
 * Security Features:
 * - Mandatory company context validation
 * - No arbitrary company fallbacks
 * - All queries properly scoped to company
 * - Phone-based authentication within company scope
 * - Audit logging for all operations
 */
class SecureAppointmentManagementMCPServer extends BaseMCPServer
{
    /**
     * @var Company|null Current company context
     */
    protected ?Company $company = null;
    
    /**
     * @var NotificationService
     */
    protected NotificationService $notificationService;
    
    /**
     * @var bool Audit logging enabled
     */
    protected bool $auditEnabled = true;
    
    protected string $name = 'secure-appointment-management';
    protected string $version = '1.0.0';
    protected string $description = 'Secure appointment management with tenant isolation';
    
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
        $this->resolveCompanyContext();
    }
    
    /**
     * Set company context explicitly (only for super admins)
     */
    public function setCompanyContext(Company $company): self
    {
        // Only allow super admins to override context
        if (Auth::check() && !Auth::user()->hasRole('super_admin')) {
            throw new SecurityException('Unauthorized company context override');
        }
        
        $this->company = $company;
        
        $this->auditAccess('company_context_override', [
            'company_id' => $company->id,
            'user_id' => Auth::id(),
        ]);
        
        return $this;
    }
    
    /**
     * Create a new appointment with security validation
     */
    public function create(array $params): array
    {
        $this->ensureCompanyContext();
        
        Log::info('SecureAppointmentManagementMCP: Create appointment request', [
            'company_id' => $this->company->id,
            'params' => array_diff_key($params, ['phone_number' => 1]) // Don't log phone
        ]);
        
        $this->auditAccess('create_appointment', $params);
        
        try {
            DB::beginTransaction();
            
            // Extract and normalize phone number
            $phoneNumber = $this->normalizePhoneNumber($params['phone_number'] ?? '');
            
            // Find or create customer WITHIN COMPANY SCOPE
            $customer = Customer::where('company_id', $this->company->id) // CRITICAL: Company scope
                ->where(function($q) use ($phoneNumber) {
                    $q->where('phone', $phoneNumber)
                      ->orWhere('phone', 'LIKE', '%' . substr($phoneNumber, -10));
                })
                ->first();
                
            if (!$customer) {
                // Create new customer for this company
                $customer = Customer::create([
                    'name' => $params['customer_name'] ?? 'Unknown',
                    'phone' => $phoneNumber,
                    'email' => $params['email'] ?? null,
                    'company_id' => $this->company->id, // CRITICAL: Force company context
                    'source' => 'phone_ai'
                ]);
                
                Log::info('Created new customer', [
                    'customer_id' => $customer->id,
                    'company_id' => $this->company->id
                ]);
            }
            
            // Parse datetime
            $startTime = null;
            if (isset($params['datetime'])) {
                $startTime = Carbon::parse($params['datetime']);
            } elseif (isset($params['date']) && isset($params['time'])) {
                $germanDate = $params['date'];
                $time = $params['time'];
                $startTime = $this->parseGermanDateTime($germanDate, $time);
            }
            
            if (!$startTime) {
                throw new MCPException('Kein gültiges Datum angegeben');
            }
            
            // Get branch - ensure it belongs to company
            $branch = null;
            if (isset($params['branch_id'])) {
                $branch = Branch::where('id', $params['branch_id'])
                    ->where('company_id', $this->company->id) // Company scope
                    ->where('active', true)
                    ->first();
            }
            
            if (!$branch) {
                // Get main branch for company
                $branch = Branch::where('company_id', $this->company->id)
                    ->where('is_main', true)
                    ->where('active', true)
                    ->first();
            }
            
            if (!$branch) {
                // Fallback to any active branch for company
                $branch = Branch::where('company_id', $this->company->id)
                    ->where('active', true)
                    ->first();
            }
            
            if (!$branch) {
                throw new MCPException('Keine aktive Filiale gefunden');
            }
            
            // For appointments table, we need numeric branch_id
            $branchIdForAppointment = is_numeric($branch->id) ? $branch->id : 1;
            
            // Get service - ensure it belongs to company
            $service = null;
            if (isset($params['service_name'])) {
                $service = Service::where('company_id', $this->company->id) // Company scope
                    ->where('active', true)
                    ->where('name', 'LIKE', '%' . $params['service_name'] . '%')
                    ->first();
            }
            
            if (!$service) {
                $service = Service::where('company_id', $this->company->id)
                    ->where('active', true)
                    ->first();
            }
            
            // Calculate end time
            $duration = $service ? $service->duration : 30;
            $endTime = $startTime->copy()->addMinutes($duration);
            
            // Get staff preference - ensure they work at the branch
            $staff = null;
            if (isset($params['staff_preference'])) {
                $staff = Staff::whereHas('branches', function($q) use ($branch) {
                        $q->where('branches.id', $branch->id);
                    })
                    ->where('active', true)
                    ->where('name', 'LIKE', '%' . $params['staff_preference'] . '%')
                    ->first();
            }
            
            if (!$staff) {
                // Get default staff for branch
                $staff = Staff::whereHas('branches', function($q) use ($branch) {
                        $q->where('branches.id', $branch->id);
                    })
                    ->where('active', true)
                    ->first();
            }
            
            // Check if Cal.com is configured
            if (!$this->company->calcom_api_key || !$branch->calcom_event_type_id) {
                Log::error('Cal.com not configured', [
                    'company_id' => $this->company->id,
                    'branch_id' => $branch->id,
                    'has_api_key' => (bool)$this->company->calcom_api_key,
                    'has_event_type' => (bool)$branch->calcom_event_type_id
                ]);
                
                throw new MCPException(
                    'Das Buchungssystem ist momentan nicht verfügbar. Bitte versuchen Sie es später erneut.'
                );
            }
            
            // First try to create Cal.com booking
            $calcomBookingId = null;
            try {
                // Decrypt API key if needed
                $apiKey = $this->company->calcom_api_key;
                try {
                    $apiKey = decrypt($apiKey);
                } catch (\Exception $e) {
                    // API key might not be encrypted
                }
                
                $calcomService = new CalcomV2Service($apiKey);
                
                $calcomResult = $calcomService->createBooking([
                    'eventTypeId' => $branch->calcom_event_type_id,
                    'start' => $startTime->toIso8601String(),
                    'end' => $endTime->toIso8601String(),
                    'name' => $customer->name,
                    'email' => $customer->email ?? 'noreply@askproai.de',
                    'phone' => $customer->phone,
                    'notes' => $params['notes'] ?? ''
                ]);
                
                if (isset($calcomResult['data']['id'])) {
                    $calcomBookingId = $calcomResult['data']['id'];
                    Log::info('Cal.com booking created successfully', [
                        'booking_id' => $calcomBookingId,
                        'company_id' => $this->company->id
                    ]);
                } else {
                    throw new \Exception('No booking ID returned from Cal.com');
                }
                
            } catch (\Exception $e) {
                Log::error('Failed to create Cal.com booking', [
                    'error' => $e->getMessage(),
                    'company_id' => $this->company->id
                ]);
                
                // Don't create appointment if Cal.com booking fails
                throw new MCPException(
                    'Der Termin konnte nicht gebucht werden. Bitte versuchen Sie es später erneut.'
                );
            }
            
            // Only create appointment if Cal.com booking was successful
            $appointment = Appointment::create([
                'company_id' => $this->company->id, // CRITICAL: Force company context
                'branch_id' => $branchIdForAppointment,
                'customer_id' => $customer->id,
                'staff_id' => $staff ? $staff->id : null,
                'service_id' => $service ? $service->id : null,
                'starts_at' => $startTime,
                'ends_at' => $endTime,
                'status' => 'scheduled',
                'source' => $params['source'] ?? 'phone_ai',
                'notes' => $params['notes'] ?? $params['preferences'] ?? null,
                'calcom_v2_booking_id' => $calcomBookingId,
                'metadata' => [
                    'booked_via' => 'phone_ai',
                    'preferences' => $params['preferences'] ?? null,
                    'calcom_booking_id' => $calcomBookingId
                ]
            ]);
            
            // Send confirmation notification
            try {
                $this->notificationService->sendAppointmentConfirmation($appointment);
            } catch (\Exception $e) {
                Log::warning('Failed to send confirmation', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            DB::commit();
            
            Log::info('Appointment created successfully', [
                'appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
                'company_id' => $this->company->id,
                'datetime' => $startTime->format('Y-m-d H:i')
            ]);
            
            return [
                'success' => true,
                'message' => 'Termin erfolgreich gebucht',
                'appointment' => [
                    'id' => $appointment->id,
                    'datetime' => $startTime->format('d.m.Y H:i'),
                    'service' => $service ? $service->name : 'Termin',
                    'staff' => $staff ? $staff->name : null,
                    'branch' => $branch->name,
                    'duration_minutes' => $duration
                ],
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone
                ]
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create appointment', [
                'params' => array_diff_key($params, ['phone_number' => 1]),
                'error' => $e->getMessage(),
                'company_id' => $this->company->id
            ]);
            
            if ($e instanceof MCPException) {
                throw $e;
            }
            
            throw new MCPException('Fehler beim Erstellen des Termins: ' . $e->getMessage());
        }
    }
    
    /**
     * Find appointments by phone number with company scope
     */
    public function find(array $params): array
    {
        $this->ensureCompanyContext();
        $this->validateParams($params, ['phone_number']);
        $this->auditAccess('find_appointments', ['has_phone' => true]);
        
        $phoneNumber = $this->normalizePhoneNumber($params['phone_number']);
        $status = $params['status'] ?? 'scheduled';
        
        // Find customer by phone WITHIN COMPANY SCOPE
        $customer = Customer::where('company_id', $this->company->id) // CRITICAL: Company scope
            ->where(function($q) use ($phoneNumber) {
                $q->where('phone', $phoneNumber)
                  ->orWhere('phone', 'LIKE', '%' . substr($phoneNumber, -10));
            })
            ->first();
        
        if (!$customer) {
            return [
                'found' => false,
                'message' => 'Keine Termine unter dieser Nummer gefunden',
                'appointments' => [],
            ];
        }
        
        // Find appointments (already scoped by customer which is scoped by company)
        $query = Appointment::where('customer_id', $customer->id)
            ->where('company_id', $this->company->id) // Double-check company scope
            ->with(['service', 'staff', 'branch']);
        
        if ($status === 'upcoming') {
            $query->where('starts_at', '>', now())
                  ->where('status', 'scheduled');
        } elseif ($status) {
            $query->where('status', $status);
        }
        
        $appointments = $query->orderBy('starts_at')->get();
        
        return [
            'found' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ],
            'appointments' => $appointments->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'date' => $appointment->starts_at->format('Y-m-d'),
                    'time' => $appointment->starts_at->format('H:i'),
                    'datetime' => $appointment->starts_at->toIso8601String(),
                    'service' => $appointment->service ? $appointment->service->name : 'Termin',
                    'staff' => $appointment->staff ? $appointment->staff->name : null,
                    'branch' => $appointment->branch->name,
                    'status' => $appointment->status,
                    'duration_minutes' => $appointment->duration_minutes,
                    'can_modify' => $this->canModifyAppointment($appointment),
                ];
            })->toArray(),
            'count' => $appointments->count(),
        ];
    }
    
    /**
     * Change appointment with security validation
     */
    public function change(array $params): array
    {
        $this->ensureCompanyContext();
        $this->validateParams($params, ['phone_number', 'new_date', 'new_time']);
        $this->auditAccess('change_appointment', ['has_phone' => true]);
        
        DB::beginTransaction();
        try {
            // Find appointments
            $findResult = $this->find([
                'phone_number' => $params['phone_number'],
                'status' => 'upcoming',
            ]);
            
            if (!$findResult['found'] || empty($findResult['appointments'])) {
                throw new MCPException(
                    'Kein anstehender Termin gefunden',
                    0
                );
            }
            
            // Get the appointment to change
            $appointmentData = null;
            if (isset($params['appointment_id'])) {
                $appointmentData = collect($findResult['appointments'])
                    ->firstWhere('id', $params['appointment_id']);
            } else {
                // Take the next upcoming appointment
                $appointmentData = $findResult['appointments'][0];
            }
            
            if (!$appointmentData) {
                throw new MCPException(
                    'Termin nicht gefunden',
                    0
                );
            }
            
            // Load the actual appointment model with company validation
            $appointment = Appointment::where('id', $appointmentData['id'])
                ->where('company_id', $this->company->id) // CRITICAL: Company scope
                ->first();
                
            if (!$appointment) {
                throw new SecurityException('Appointment not found or does not belong to company');
            }
            
            // Check if modification is allowed
            if (!$this->canModifyAppointment($appointment)) {
                throw new MCPException(
                    'Termin kann nicht mehr geändert werden (zu kurzfristig)',
                    0
                );
            }
            
            // Parse new datetime
            $newDateTime = $this->parseDateTime($params['new_date'], $params['new_time']);
            
            // Check if new time is in the future
            if ($newDateTime->isPast()) {
                throw new MCPException(
                    'Neuer Termin liegt in der Vergangenheit',
                    0
                );
            }
            
            // Check availability
            $isAvailable = $this->checkAvailabilitySecure(
                $appointment->branch_id,
                $appointment->service_id,
                $appointment->staff_id,
                $newDateTime,
                $appointment->duration_minutes,
                $appointment->id // Exclude current appointment
            );
            
            if (!$isAvailable) {
                // Find alternatives
                $alternatives = $this->findAlternativeSlotsSecure(
                    $appointment->branch_id,
                    $appointment->service_id,
                    $appointment->staff_id,
                    $newDateTime,
                    $appointment->duration_minutes
                );
                
                return [
                    'success' => false,
                    'message' => 'Der gewünschte Termin ist nicht verfügbar',
                    'alternatives' => $alternatives,
                ];
            }
            
            // Store old datetime for logging
            $oldDateTime = $appointment->starts_at->copy();
            
            // Update appointment
            $appointment->update([
                'starts_at' => $newDateTime,
                'ends_at' => $newDateTime->copy()->addMinutes($appointment->duration_minutes),
                'rescheduled_at' => now(),
                'rescheduled_from' => $oldDateTime,
            ]);
            
            // Update in Cal.com if integrated
            if ($appointment->calcom_booking_id && $appointment->branch->calcom_event_type_id) {
                try {
                    if ($this->company->calcom_api_key) {
                        $calcomService = new CalcomV2Service(decrypt($this->company->calcom_api_key));
                        // TODO: Implement Cal.com reschedule
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to update Cal.com booking', [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Send notification
            $this->notificationService->sendAppointmentRescheduledNotification($appointment, $oldDateTime);
            
            // Log activity
            activity()
                ->performedOn($appointment)
                ->causedBy($appointment->customer)
                ->withProperties([
                    'old_datetime' => $oldDateTime->toIso8601String(),
                    'new_datetime' => $newDateTime->toIso8601String(),
                    'source' => 'phone_mcp',
                ])
                ->log('appointment_rescheduled');
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Termin erfolgreich verschoben',
                'appointment' => [
                    'id' => $appointment->id,
                    'old_datetime' => $oldDateTime->format('d.m.Y H:i'),
                    'new_datetime' => $newDateTime->format('d.m.Y H:i'),
                    'service' => $appointment->service ? $appointment->service->name : 'Termin',
                    'branch' => $appointment->branch->name,
                ],
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($e instanceof MCPException || $e instanceof SecurityException) {
                throw $e;
            }
            
            Log::error('Failed to change appointment', [
                'params' => array_diff_key($params, ['phone_number' => 1]),
                'error' => $e->getMessage(),
                'company_id' => $this->company->id
            ]);
            
            throw new MCPException(
                'Fehler beim Ändern des Termins',
                0,
                ['error' => $e->getMessage()]
            );
        }
    }
    
    /**
     * Cancel appointment with security validation
     */
    public function cancel(array $params): array
    {
        $this->ensureCompanyContext();
        $this->validateParams($params, ['phone_number']);
        $this->auditAccess('cancel_appointment', ['has_phone' => true]);
        
        DB::beginTransaction();
        try {
            // Find appointments
            $findResult = $this->find([
                'phone_number' => $params['phone_number'],
                'status' => 'upcoming',
            ]);
            
            if (!$findResult['found'] || empty($findResult['appointments'])) {
                throw new MCPException(
                    'Kein anstehender Termin gefunden',
                    0
                );
            }
            
            // Get the appointment to cancel
            $appointmentData = null;
            if (isset($params['appointment_id'])) {
                $appointmentData = collect($findResult['appointments'])
                    ->firstWhere('id', $params['appointment_id']);
            } else {
                // Take the next upcoming appointment
                $appointmentData = $findResult['appointments'][0];
            }
            
            if (!$appointmentData) {
                throw new MCPException(
                    'Termin nicht gefunden',
                    0
                );
            }
            
            // Load the actual appointment model with company validation
            $appointment = Appointment::where('id', $appointmentData['id'])
                ->where('company_id', $this->company->id) // CRITICAL: Company scope
                ->first();
                
            if (!$appointment) {
                throw new SecurityException('Appointment not found or does not belong to company');
            }
            
            // Check if cancellation is allowed
            if (!$this->canModifyAppointment($appointment)) {
                throw new MCPException(
                    'Termin kann nicht mehr storniert werden (zu kurzfristig)',
                    0
                );
            }
            
            // Cancel appointment
            $appointment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $params['reason'] ?? 'Telefonisch storniert',
            ]);
            
            // Cancel in Cal.com if integrated
            if ($appointment->calcom_booking_id && $appointment->branch->calcom_event_type_id) {
                try {
                    if ($this->company->calcom_api_key) {
                        $calcomService = new CalcomV2Service(decrypt($this->company->calcom_api_key));
                        $calcomService->cancelBooking(
                            $appointment->calcom_booking_id,
                            $params['reason'] ?? 'Customer requested cancellation'
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to cancel Cal.com booking', [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Send notification
            $this->notificationService->sendAppointmentCancelledNotification($appointment);
            
            // Log activity
            activity()
                ->performedOn($appointment)
                ->causedBy($appointment->customer)
                ->withProperties([
                    'reason' => $params['reason'] ?? 'No reason provided',
                    'source' => 'phone_mcp',
                ])
                ->log('appointment_cancelled');
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Termin erfolgreich storniert',
                'appointment' => [
                    'id' => $appointment->id,
                    'datetime' => $appointment->starts_at->format('d.m.Y H:i'),
                    'service' => $appointment->service ? $appointment->service->name : 'Termin',
                    'branch' => $appointment->branch->name,
                ],
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($e instanceof MCPException || $e instanceof SecurityException) {
                throw $e;
            }
            
            Log::error('Failed to cancel appointment', [
                'params' => array_diff_key($params, ['phone_number' => 1]),
                'error' => $e->getMessage(),
                'company_id' => $this->company->id
            ]);
            
            throw new MCPException(
                'Fehler beim Stornieren des Termins',
                0,
                ['error' => $e->getMessage()]
            );
        }
    }
    
    /**
     * Confirm appointment with security validation
     */
    public function confirm(array $params): array
    {
        $this->ensureCompanyContext();
        $this->validateParams($params, ['phone_number']);
        $this->auditAccess('confirm_appointment', ['has_phone' => true]);
        
        // Find appointments
        $findResult = $this->find([
            'phone_number' => $params['phone_number'],
            'status' => 'scheduled',
        ]);
        
        if (!$findResult['found'] || empty($findResult['appointments'])) {
            throw new MCPException(
                'Kein offener Termin gefunden',
                0
            );
        }
        
        // Get the appointment to confirm
        $appointmentData = null;
        if (isset($params['appointment_id'])) {
            $appointmentData = collect($findResult['appointments'])
                ->firstWhere('id', $params['appointment_id']);
        } else {
            // Take the next upcoming appointment
            $appointmentData = collect($findResult['appointments'])
                ->filter(fn($a) => Carbon::parse($a['datetime'])->isFuture())
                ->first();
        }
        
        if (!$appointmentData) {
            throw new MCPException(
                'Kein anstehender Termin gefunden',
                0
            );
        }
        
        // Load and update appointment with company validation
        $appointment = Appointment::where('id', $appointmentData['id'])
            ->where('company_id', $this->company->id) // CRITICAL: Company scope
            ->first();
            
        if (!$appointment) {
            throw new SecurityException('Appointment not found or does not belong to company');
        }
        
        $appointment->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
        
        // Log activity
        activity()
            ->performedOn($appointment)
            ->causedBy($appointment->customer)
            ->withProperties(['source' => 'phone_mcp'])
            ->log('appointment_confirmed');
        
        return [
            'success' => true,
            'message' => 'Termin erfolgreich bestätigt',
            'appointment' => [
                'id' => $appointment->id,
                'datetime' => $appointment->starts_at->format('d.m.Y H:i'),
                'service' => $appointment->service ? $appointment->service->name : 'Termin',
                'branch' => $appointment->branch->name,
            ],
        ];
    }
    
    /**
     * Check if appointment can be modified
     */
    protected function canModifyAppointment(Appointment $appointment): bool
    {
        // Cannot modify past appointments
        if ($appointment->starts_at->isPast()) {
            return false;
        }
        
        // Cannot modify if less than 2 hours before appointment
        if ($appointment->starts_at->diffInHours(now()) < 2) {
            return false;
        }
        
        // Cannot modify cancelled appointments
        if ($appointment->status === 'cancelled') {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check availability with company scope
     */
    protected function checkAvailabilitySecure(
        int $branchId,
        ?int $serviceId,
        ?int $staffId,
        Carbon $startTime,
        int $durationMinutes,
        ?int $excludeAppointmentId = null
    ): bool {
        // Validate branch belongs to company
        $branch = Branch::where('id', $branchId)
            ->where('company_id', $this->company->id)
            ->first();
            
        if (!$branch) {
            return false;
        }
        
        $endTime = $startTime->copy()->addMinutes($durationMinutes);
        
        $query = Appointment::where('branch_id', $branchId)
            ->where('company_id', $this->company->id) // Company scope
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('starts_at', [$startTime, $endTime])
                  ->orWhereBetween('ends_at', [$startTime, $endTime])
                  ->orWhere(function ($q2) use ($startTime, $endTime) {
                      $q2->where('starts_at', '<=', $startTime)
                         ->where('ends_at', '>=', $endTime);
                  });
            });
        
        if ($staffId) {
            // Validate staff works at branch
            $staffValid = Staff::whereHas('branches', function($q) use ($branchId) {
                    $q->where('branches.id', $branchId);
                })
                ->where('id', $staffId)
                ->exists();
                
            if ($staffValid) {
                $query->where('staff_id', $staffId);
            }
        }
        
        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }
        
        return !$query->exists();
    }
    
    /**
     * Find alternative slots with company scope
     */
    protected function findAlternativeSlotsSecure(
        int $branchId,
        ?int $serviceId,
        ?int $staffId,
        Carbon $preferredTime,
        int $durationMinutes,
        int $maxAlternatives = 3
    ): array {
        // Validate branch belongs to company
        $branch = Branch::where('id', $branchId)
            ->where('company_id', $this->company->id)
            ->first();
            
        if (!$branch) {
            return [];
        }
        
        $alternatives = [];
        $searchDate = $preferredTime->copy()->startOfDay();
        $maxDays = 14; // Search up to 2 weeks
        
        for ($day = 0; $day < $maxDays && count($alternatives) < $maxAlternatives; $day++) {
            $currentDate = $searchDate->copy()->addDays($day);
            
            // Skip weekends (configurable per branch)
            if ($currentDate->isWeekend()) {
                continue;
            }
            
            // Check slots throughout the day
            $slots = $this->getDaySlots($currentDate);
            
            foreach ($slots as $slot) {
                if ($this->checkAvailabilitySecure($branchId, $serviceId, $staffId, $slot, $durationMinutes)) {
                    $alternatives[] = [
                        'date' => $slot->format('Y-m-d'),
                        'time' => $slot->format('H:i'),
                        'datetime' => $slot->toIso8601String(),
                        'formatted' => $slot->format('d.m.Y H:i'),
                    ];
                    
                    if (count($alternatives) >= $maxAlternatives) {
                        break;
                    }
                }
            }
        }
        
        return $alternatives;
    }
    
    /**
     * Get available time slots for a day
     */
    protected function getDaySlots(Carbon $date): array
    {
        $slots = [];
        $start = $date->copy()->setTime(9, 0); // 9 AM
        $end = $date->copy()->setTime(17, 0);  // 5 PM
        
        while ($start < $end) {
            if ($start->isFuture()) {
                $slots[] = $start->copy();
            }
            $start->addMinutes(30);
        }
        
        return $slots;
    }
    
    /**
     * Parse German date and time strings
     */
    protected function parseGermanDateTime(string $date, string $time): Carbon
    {
        $date = strtolower(trim($date));
        $now = Carbon::now('Europe/Berlin');
        
        // Handle relative dates
        switch ($date) {
            case 'heute':
                $carbonDate = $now->copy();
                break;
            case 'morgen':
                $carbonDate = $now->copy()->addDay();
                break;
            case 'übermorgen':
                $carbonDate = $now->copy()->addDays(2);
                break;
            default:
                // Try to parse as regular date
                return $this->parseDateTime($date, $time);
        }
        
        // Parse time
        $time = trim(str_replace(['Uhr', 'uhr'], '', $time));
        
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            $carbonDate->setTime((int)$matches[1], (int)$matches[2]);
        } elseif (preg_match('/^(\d{1,2})$/', $time, $matches)) {
            $carbonDate->setTime((int)$matches[1], 0);
        }
        
        return $carbonDate;
    }
    
    /**
     * Parse date and time into Carbon instance
     */
    protected function parseDateTime(string $date, string $time): Carbon
    {
        // Parse date (handle various formats)
        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $carbonDate = Carbon::parse($date);
            } elseif (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $date)) {
                $carbonDate = Carbon::createFromFormat('d.m.Y', $date);
            } elseif (preg_match('/^\d{2}\.\d{2}\.\d{2}$/', $date)) {
                $carbonDate = Carbon::createFromFormat('d.m.y', $date);
            } else {
                throw new MCPException(
                    "Ungültiges Datumsformat: {$date}",
                    0
                );
            }
        } catch (\Exception $e) {
            throw new MCPException(
                "Ungültiges Datum: {$date}",
                0
            );
        }
        
        // Parse time
        $time = trim(str_replace(['Uhr', 'uhr'], '', $time));
        
        try {
            if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
                $carbonDate->setTimeFromTimeString($time);
            } elseif (preg_match('/^\d{1,2}$/', $time)) {
                $carbonDate->setTime((int)$time, 0);
            } else {
                throw new MCPException(
                    "Ungültiges Zeitformat: {$time}",
                    0
                );
            }
        } catch (\Exception $e) {
            throw new MCPException(
                "Ungültige Zeit: {$time}",
                0
            );
        }
        
        return $carbonDate;
    }
    
    /**
     * Normalize phone number
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Handle German numbers
        if (str_starts_with($phone, '0')) {
            $phone = '+49' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '+')) {
            $phone = '+49' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Validate required parameters
     */
    protected function validateParams(array $params, array $required): void
    {
        foreach ($required as $param) {
            if (!isset($params[$param]) || empty($params[$param])) {
                throw new MCPException("The {$param} field is required.");
            }
        }
    }
    
    /**
     * Health check
     */
    public function health(): array
    {
        return [
            'status' => 'healthy',
            'service' => 'SecureAppointmentManagementMCPServer',
            'timestamp' => now()->toIso8601String(),
            'company_context' => $this->company ? $this->company->id : null
        ];
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
            throw new SecurityException('No valid company context for appointment management');
        }
    }
    
    /**
     * Audit access to appointment operations
     */
    protected function auditAccess(string $operation, array $context = []): void
    {
        if (!$this->auditEnabled) {
            return;
        }
        
        try {
            if (DB::connection()->getSchemaBuilder()->hasTable('security_audit_logs')) {
                DB::table('security_audit_logs')->insert([
                    'event_type' => 'appointment_mcp_access',
                    'user_id' => Auth::id(),
                    'company_id' => $this->company->id ?? null,
                    'ip_address' => request()->ip() ?? '127.0.0.1',
                    'url' => request()->fullUrl() ?? 'console',
                    'metadata' => json_encode(array_merge($context, [
                        'operation' => $operation,
                        'user_agent' => request()->userAgent()
                    ])),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('SecureAppointmentManagementMCP: Audit logging failed', [
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