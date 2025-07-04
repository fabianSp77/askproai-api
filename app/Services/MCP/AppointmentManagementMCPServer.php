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
use App\Exceptions\MCPException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * MCP Server for appointment management operations
 * 
 * Handles finding, changing, and cancelling appointments
 * with phone-based authentication.
 */
class AppointmentManagementMCPServer
{
    protected NotificationService $notificationService;
    
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    /**
     * Create a new appointment
     * 
     * @param array $params
     * @return array
     */
    public function create(array $params): array
    {
        Log::info('AppointmentManagementMCPServer: Create appointment request', $params);
        
        try {
            DB::beginTransaction();
            
            // Extract and normalize phone number
            $phoneNumber = $this->normalizePhoneNumber($params['phone_number'] ?? '');
            
            // Find or create customer
            $customer = Customer::withoutGlobalScopes()
                ->where('phone', $phoneNumber)
                ->orWhere('phone', 'LIKE', '%' . substr($phoneNumber, -10))
                ->first();
                
            if (!$customer) {
                // Create new customer
                $customer = Customer::create([
                    'name' => $params['customer_name'] ?? 'Unknown',
                    'phone' => $phoneNumber,
                    'email' => $params['email'] ?? null,
                    'company_id' => Company::withoutGlobalScopes()->first()->id, // Default company
                    'source' => 'phone_ai'
                ]);
                
                Log::info('Created new customer', ['customer_id' => $customer->id]);
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
            
            // Get branch - use branch with ID 1 for appointments (numeric ID compatibility)
            $branch = Branch::withoutGlobalScopes()->where('id', '1')->first();
            if (!$branch) {
                // Fallback to first active branch
                $branch = Branch::withoutGlobalScopes()->where('active', true)->first();
                if (!$branch) {
                    throw new MCPException('Keine aktive Filiale gefunden');
                }
            }
            
            // For appointments table, we need numeric branch_id
            $branchIdForAppointment = is_numeric($branch->id) ? $branch->id : 1;
            
            // Get service
            $service = null;
            if (isset($params['service_name'])) {
                $service = Service::withoutGlobalScopes()
                    ->where('active', true)
                    ->where('name', 'LIKE', '%' . $params['service_name'] . '%')
                    ->first();
            }
            if (!$service) {
                $service = Service::withoutGlobalScopes()->where('active', true)->first();
            }
            
            // Calculate end time - use 30 minutes for Cal.com event type 2563193
            $duration = 30; // Fixed 30 minutes to match Cal.com event type
            $endTime = $startTime->copy()->addMinutes($duration);
            
            // Get staff preference
            $staff = null;
            if (isset($params['staff_preference'])) {
                $staff = Staff::withoutGlobalScopes()
                    ->where('active', true)
                    ->where('name', 'LIKE', '%' . $params['staff_preference'] . '%')
                    ->first();
            }
            if (!$staff) {
                // Get default staff
                $staff = Staff::withoutGlobalScopes()
                    ->where('active', true)
                    ->where('branch_id', $branch->id)
                    ->first();
            }
            
            // Check if Cal.com is configured
            $company = Company::find($branch->company_id);
            if (!$company->calcom_api_key || !$branch->calcom_event_type_id) {
                Log::error('Cal.com not configured', [
                    'company_id' => $branch->company_id,
                    'branch_id' => $branch->id,
                    'has_api_key' => (bool)$company->calcom_api_key,
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
                $apiKey = $company->calcom_api_key;
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
                        'booking_id' => $calcomBookingId
                    ]);
                } else {
                    throw new \Exception('No booking ID returned from Cal.com');
                }
                
            } catch (\Exception $e) {
                Log::error('Failed to create Cal.com booking', [
                    'error' => $e->getMessage()
                ]);
                
                // Don't create appointment if Cal.com booking fails
                throw new MCPException(
                    'Der Termin konnte nicht gebucht werden. Bitte versuchen Sie es später erneut.'
                );
            }
            
            // Only create appointment if Cal.com booking was successful
            $appointment = Appointment::create([
                'company_id' => $branch->company_id,
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
                'params' => $params,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($e instanceof MCPException) {
                throw $e;
            }
            
            throw new MCPException('Fehler beim Erstellen des Termins: ' . $e->getMessage());
        }
    }
    
    /**
     * Find appointments by phone number
     * 
     * @param array $params ['phone_number' => string, 'status' => string (optional)]
     * @return array
     */
    public function find(array $params): array
    {
        $this->validateParams($params, ['phone_number']);
        
        $phoneNumber = $this->normalizePhoneNumber($params['phone_number']);
        $status = $params['status'] ?? 'scheduled';
        
        // Find customer by phone
        $customer = Customer::where('phone', $phoneNumber)
            ->orWhere('phone', 'LIKE', '%' . substr($phoneNumber, -10))
            ->first();
        
        if (!$customer) {
            return [
                'found' => false,
                'message' => 'Keine Termine unter dieser Nummer gefunden',
                'appointments' => [],
            ];
        }
        
        // Find appointments
        $query = Appointment::where('customer_id', $customer->id)
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
                    'service' => $appointment->service->name,
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
     * Change an appointment
     * 
     * @param array $params
     * @return array
     */
    public function change(array $params): array
    {
        $this->validateParams($params, ['phone_number', 'new_date', 'new_time']);
        
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
            
            // Load the actual appointment model
            $appointment = Appointment::findOrFail($appointmentData['id']);
            
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
            $isAvailable = $this->checkAvailability(
                $appointment->branch_id,
                $appointment->service_id,
                $appointment->staff_id,
                $newDateTime,
                $appointment->duration_minutes,
                $appointment->id // Exclude current appointment
            );
            
            if (!$isAvailable) {
                // Find alternatives
                $alternatives = $this->findAlternativeSlots(
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
                    $company = $appointment->company;
                    if ($company->calcom_api_key) {
                        $calcomService = new CalcomV2Service(decrypt($company->calcom_api_key));
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
                    'service' => $appointment->service->name,
                    'branch' => $appointment->branch->name,
                ],
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($e instanceof MCPException) {
                throw $e;
            }
            
            Log::error('Failed to change appointment', [
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            
            throw new MCPException(
                'Fehler beim Ändern des Termins',
                0,
                ['error' => $e->getMessage()]
            );
        }
    }
    
    /**
     * Cancel an appointment
     * 
     * @param array $params
     * @return array
     */
    public function cancel(array $params): array
    {
        $this->validateParams($params, ['phone_number']);
        
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
            
            // Load the actual appointment model
            $appointment = Appointment::findOrFail($appointmentData['id']);
            
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
                    $company = $appointment->company;
                    if ($company->calcom_api_key) {
                        $calcomService = new CalcomV2Service(decrypt($company->calcom_api_key));
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
                    'service' => $appointment->service->name,
                    'branch' => $appointment->branch->name,
                ],
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($e instanceof MCPException) {
                throw $e;
            }
            
            Log::error('Failed to cancel appointment', [
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            
            throw new MCPException(
                'Fehler beim Stornieren des Termins',
                0,
                ['error' => $e->getMessage()]
            );
        }
    }
    
    /**
     * Confirm an appointment
     * 
     * @param array $params
     * @return array
     */
    public function confirm(array $params): array
    {
        $this->validateParams($params, ['phone_number']);
        
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
        
        // Load and update appointment
        $appointment = Appointment::findOrFail($appointmentData['id']);
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
                'service' => $appointment->service->name,
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
     * Check availability for a time slot
     */
    protected function checkAvailability(
        int $branchId,
        ?int $serviceId,
        ?int $staffId,
        Carbon $startTime,
        int $durationMinutes,
        ?int $excludeAppointmentId = null
    ): bool {
        $endTime = $startTime->copy()->addMinutes($durationMinutes);
        
        $query = Appointment::where('branch_id', $branchId)
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
            $query->where('staff_id', $staffId);
        }
        
        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }
        
        return !$query->exists();
    }
    
    /**
     * Find alternative time slots
     */
    protected function findAlternativeSlots(
        int $branchId,
        ?int $serviceId,
        ?int $staffId,
        Carbon $preferredTime,
        int $durationMinutes,
        int $maxAlternatives = 3
    ): array {
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
                if ($this->checkAvailability($branchId, $serviceId, $staffId, $slot, $durationMinutes)) {
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
            'service' => 'AppointmentManagementMCPServer',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}