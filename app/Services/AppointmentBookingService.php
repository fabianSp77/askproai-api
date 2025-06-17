<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Branch;
use App\Models\Call;
use App\Traits\TransactionalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\CalcomV2Service;
use App\Services\NotificationService;
use App\Exceptions\BookingException;
use App\Exceptions\AvailabilityException;
use App\Services\Locking\TimeSlotLockManager;
use Illuminate\Support\Str;

class AppointmentBookingService
{
    use TransactionalService;
    private $calcomService;
    private $notificationService;
    private $availabilityService;
    private $lockManager;
    
    public function __construct(
        ?CalcomV2Service $calcomService = null,
        ?NotificationService $notificationService = null,
        ?AvailabilityService $availabilityService = null,
        ?TimeSlotLockManager $lockManager = null
    ) {
        $this->calcomService = $calcomService ?? new CalcomV2Service();
        $this->notificationService = $notificationService ?? new NotificationService();
        // AvailabilityService requires CacheService
        if (!$availabilityService) {
            $cacheService = app(\App\Services\CacheService::class);
            $this->availabilityService = new AvailabilityService($cacheService);
        } else {
            $this->availabilityService = $availabilityService;
        }
        $this->lockManager = $lockManager ?? new TimeSlotLockManager();
    }
    
    /**
     * Complete phone-to-appointment booking flow
     * Supports both old format and new collect_appointment_data format
     */
    public function bookFromPhoneCall($callOrData, ?array $appointmentData = null): array
    {
        $lockToken = null;
        $startTime = microtime(true);
        
        try {
            // Determine if we're working with a Call object or data array
            $call = null;
            $data = [];
            
            if ($callOrData instanceof Call) {
                $call = $callOrData;
                $data = $appointmentData ?? [];
            } else {
                $data = $callOrData;
            }
            
            $context = [
                'call_id' => $call?->id,
                'has_appointment_data' => !empty($data),
            ];
            
            return $this->executeInTransaction(function () use ($call, $data, &$lockToken) {
                // 1. Prepare booking data from new format
                $bookingData = $this->prepareBookingDataFromCollectFunction($data, $call);
                
                // 2. Find or create customer
                $customer = $this->findOrCreateCustomer($bookingData['customer']);
                
                // 3. Validate service and staff (make them optional for now)
                $service = null;
                $staff = null;
                $branch = null;
                
                if (!empty($bookingData['service_id'])) {
                    $service = $this->validateService($bookingData['service_id']);
                }
                
                if (!empty($bookingData['staff_id'])) {
                    $staff = $this->validateStaff($bookingData['staff_id'], $service);
                }
                
                // Get default branch if not specified
                if ($call && $call->branch_id) {
                    $branch = Branch::find($call->branch_id);
                } elseif ($staff) {
                    $branch = Branch::find($staff->home_branch_id);
                } else {
                    $branch = Branch::where('company_id', $call?->company_id ?? $customer->company_id)->first();
                }
                
                // 4. Check availability (skip for now if no staff)
                if ($staff) {
                    $timeSlot = $this->checkAndReserveTimeSlot(
                        $staff,
                        $bookingData['starts_at'],
                        $bookingData['ends_at'] ?? $this->calculateEndTime($bookingData['starts_at'], $service)
                    );
                    // Store the lock token for later release
                    $lockToken = $timeSlot['lock_token'] ?? null;
                } else {
                    // Just use the provided times
                    $timeSlot = [
                        'start' => $bookingData['starts_at'],
                        'end' => $bookingData['ends_at'] ?? Carbon::parse($bookingData['starts_at'])->addHour()
                    ];
                }
                
                // 5. Create appointment
                $appointment = $this->createAppointment([
                    'customer_id' => $customer->id,
                    'service_id' => $service?->id,
                    'staff_id' => $staff?->id,
                    'branch_id' => $branch?->id,
                    'company_id' => $call?->company_id ?? $customer->company_id,
                    'starts_at' => $timeSlot['start'],
                    'ends_at' => $timeSlot['end'],
                    'status' => 'scheduled',
                    'call_id' => $call?->id,
                    'source' => 'phone',
                    'notes' => $bookingData['notes'] ?? null,
                    'metadata' => [
                        'booked_via' => 'phone_ai',
                        'call_duration' => $call?->duration_sec,
                        'customer_phone' => $customer->phone,
                        'raw_booking_data' => $data,
                        'lock_token' => $lockToken // Store lock token for reference
                    ]
                ]);
                
                // 6. Sync with calendar system (if we have the necessary data)
                if ($service && $staff) {
                    // Extend lock before potentially long calendar sync
                    if ($lockToken) {
                        $this->lockManager->extendLock($lockToken, 3);
                    }
                    
                    $this->syncWithCalendar($appointment);
                }
                
                // 7. Send confirmations
                $this->sendConfirmations($appointment);
                
                // 8. Update call record
                if ($call) {
                    $call->update([
                        'appointment_id' => $appointment->id,
                        'status' => 'completed'
                    ]);
                }
                
                // 9. Release the lock after successful booking
                if ($lockToken) {
                    $this->lockManager->releaseLock($lockToken);
                    $lockToken = null; // Clear reference
                }
                
                Log::info('Appointment booked successfully from phone call', [
                    'appointment_id' => $appointment->id,
                    'call_id' => $call?->id
                ]);
                
                $this->logTransactionMetrics('bookFromPhoneCall', $startTime, true, [
                    'appointment_id' => $appointment->id,
                    'customer_id' => $customer->id,
                ]);
                
                return [
                    'success' => true,
                    'appointment' => $appointment,
                    'message' => 'Termin erfolgreich gebucht'
                ];
            }, $context, 3); // Allow 3 attempts for deadlocks
            
        } catch (\Throwable $e) {
            // Always release lock on error
            if ($lockToken) {
                try {
                    $this->lockManager->releaseLock($lockToken);
                } catch (\Exception $lockException) {
                    Log::error('Failed to release lock after error', [
                        'lock_token' => $lockToken,
                        'error' => $lockException->getMessage(),
                    ]);
                }
            }
            
            $this->logTransactionMetrics('bookFromPhoneCall', $startTime, false, [
                'error' => $e->getMessage(),
            ]);
            
            // Determine if it's a known booking exception
            if ($e instanceof BookingException || $e instanceof AvailabilityException) {
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error' => $e instanceof BookingException ? $e->getErrorCode() : 'availability_error'
                ];
            }
            
            // For unexpected errors, log more details
            Log::error('Unexpected error in bookFromPhoneCall', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data ?? [],
                'call_id' => $call?->id ?? null
            ]);
            
            return [
                'success' => false,
                'message' => 'Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es erneut.',
                'error' => 'system_error'
            ];
        }
    }
    
    /**
     * Prepare booking data from collect_appointment_data format
     */
    private function prepareBookingDataFromCollectFunction(array $data, ?Call $call = null): array
    {
        // Parse date and time
        $dateStr = $data['datum'] ?? '';
        $timeStr = $data['uhrzeit'] ?? '';
        
        // Try to parse German date format (e.g., "15.04.2025")
        try {
            if (strpos($dateStr, '.') !== false) {
                $date = Carbon::createFromFormat('d.m.Y', $dateStr);
            } else {
                $date = Carbon::parse($dateStr);
            }
            
            // Parse time (e.g., "14:30" or "14:30 Uhr")
            $timeStr = str_replace(' Uhr', '', $timeStr);
            list($hour, $minute) = explode(':', $timeStr . ':00');
            $date->setTime((int)$hour, (int)$minute);
            
        } catch (\Exception $e) {
            Log::warning('Failed to parse date/time', [
                'datum' => $dateStr,
                'uhrzeit' => $timeStr,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to tomorrow at 10:00
            $date = Carbon::tomorrow()->setTime(10, 0);
        }
        
        return [
            'starts_at' => $date,
            'ends_at' => null, // Will be calculated based on service
            'customer' => [
                'name' => $data['name'] ?? 'Unbekannt',
                'phone' => $data['telefonnummer'] ?? $call?->from_number,
                'email' => $data['email'] ?? 'info@askproai.de', // Standard-E-Mail wenn keine angegeben
                'company_id' => $call?->company_id
            ],
            'service_name' => $data['dienstleistung'] ?? null,
            'service_id' => $this->findServiceIdByName($data['dienstleistung'] ?? '', $call?->company_id),
            'staff_name' => $data['mitarbeiter_wunsch'] ?? null,
            'staff_id' => $this->findStaffIdByName($data['mitarbeiter_wunsch'] ?? '', $call?->company_id),
            'branch_id' => $call?->branch_id,
            'notes' => $this->generateNotesFromData($data),
            'customer_preferences' => $data['kundenpraeferenzen'] ?? null
        ];
    }
    
    /**
     * Find service ID by name
     */
    private function findServiceIdByName(string $serviceName, ?int $companyId = null): ?int
    {
        if (empty($serviceName)) {
            return null;
        }
        
        $query = Service::where('name', 'LIKE', '%' . $serviceName . '%');
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        $service = $query->first();
        
        return $service?->id;
    }
    
    /**
     * Generate notes from appointment data
     */
    private function generateNotesFromData(array $data): string
    {
        $notes = [];
        
        if (!empty($data['dienstleistung'])) {
            $notes[] = "Gewünschte Dienstleistung: " . $data['dienstleistung'];
        }
        
        if (!empty($data['email'])) {
            $notes[] = "E-Mail: " . $data['email'];
        }
        
        // Add any additional fields that might be useful
        foreach ($data as $key => $value) {
            if (!in_array($key, ['datum', 'uhrzeit', 'name', 'telefonnummer', 'email', 'dienstleistung']) && !empty($value)) {
                $notes[] = ucfirst($key) . ": " . $value;
            }
        }
        
        return implode("\n", $notes);
    }
    
    /**
     * Find staff ID by name
     */
    private function findStaffIdByName(string $staffName, ?int $companyId = null): ?int
    {
        if (empty($staffName)) {
            return null;
        }
        
        $query = Staff::where(function($q) use ($staffName) {
            $q->where('name', 'LIKE', '%' . $staffName . '%')
              ->orWhere('first_name', 'LIKE', '%' . $staffName . '%')
              ->orWhere('last_name', 'LIKE', '%' . $staffName . '%');
        });
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        $staff = $query->first();
        
        if ($staff) {
            Log::info('Found staff member by name', [
                'search_name' => $staffName,
                'found_staff' => $staff->name,
                'staff_id' => $staff->id
            ]);
        } else {
            Log::warning('No staff member found', [
                'search_name' => $staffName,
                'company_id' => $companyId
            ]);
        }
        
        return $staff?->id;
    }
    
    /**
     * Find or create customer from phone data
     */
    private function findOrCreateCustomer(array $customerData): Customer
    {
        // Try to find by phone number first
        if (!empty($customerData['phone'])) {
            $customer = Customer::where('phone', $customerData['phone'])
                ->where('company_id', $customerData['company_id'] ?? null)
                ->first();
                
            if ($customer) {
                // Update with new information if provided
                $customer->update(array_filter([
                    'name' => $customerData['name'] ?? $customer->name,
                    'email' => $customerData['email'] ?? $customer->email,
                ]));
                
                return $customer;
            }
        }
        
        // Create new customer
        return Customer::create([
            'name' => $customerData['name'] ?? 'Unbekannt',
            'phone' => $customerData['phone'],
            'email' => $customerData['email'] ?? null,
            'company_id' => $customerData['company_id'],
            'source' => 'phone_ai',
            'notes' => 'Automatisch erfasst über Telefon-KI'
        ]);
    }
    
    /**
     * Check availability and reserve time slot with locking
     */
    private function checkAndReserveTimeSlot(Staff $staff, string $startTime, string $endTime): array
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $lockToken = null;
        
        try {
            // Try to acquire a lock on this time slot using TimeSlotLockManager
            // Use the staff's home branch ID or a default if not available
            $branchId = $staff->home_branch_id ?? $staff->branch_id ?? '1';
            
            $lockToken = $this->lockManager->acquireLock(
                $branchId,
                $staff->id,
                $start,
                $end,
                5 // Lock expires in 5 minutes
            );
            
            if (!$lockToken) {
                throw new AvailabilityException(
                    'Dieser Termin wird gerade von einem anderen Nutzer gebucht. Bitte versuchen Sie es erneut.'
                );
            }
            
            // Double-check availability with lock held
            if (!$this->isSlotStillAvailable($staff, $start, $end)) {
                // Release lock before looking for alternatives
                $this->lockManager->releaseLock($lockToken);
                $lockToken = null;
                
                // Try to find alternative slots
                $alternatives = $this->availabilityService->findAlternativeSlots(
                    $staff,
                    $start,
                    $end->diffInMinutes($start),
                    5 // Max 5 alternatives
                );
                
                if (empty($alternatives)) {
                    throw new AvailabilityException(
                        'Keine verfügbaren Termine in diesem Zeitraum'
                    );
                }
                
                // Use first alternative and try to lock it
                $alternative = $alternatives[0];
                $start = $alternative['start'];
                $end = $alternative['end'];
                
                // Try to acquire lock on alternative slot
                $lockToken = $this->lockManager->acquireLock(
                    $branchId,
                    $staff->id,
                    $start,
                    $end,
                    5 // Lock expires in 5 minutes
                );
                
                if (!$lockToken) {
                    throw new AvailabilityException(
                        'Alternative Termine sind nicht mehr verfügbar'
                    );
                }
            }
            
            // Reserve the slot with lock protection
            $this->availabilityService->reserveSlot($staff, $start, $end);
            
            return [
                'start' => $start,
                'end' => $end,
                'lock_token' => $lockToken
            ];
            
        } catch (\Exception $e) {
            // Always release lock on error
            if ($lockToken) {
                $this->lockManager->releaseLock($lockToken);
            }
            throw $e;
        }
    }
    
    /**
     * Create appointment record
     */
    private function createAppointment(array $data): Appointment
    {
        return Appointment::create($data);
    }
    
    /**
     * Sync appointment with external calendar
     */
    private function syncWithCalendar(Appointment $appointment): void
    {
        try {
            $calcomBooking = $this->calcomService->createBooking([
                'eventTypeId' => $appointment->service->calcom_event_type_id,
                'start' => $appointment->starts_at->toIso8601String(),
                'responses' => [
                    'name' => $appointment->customer->name,
                    'email' => $appointment->customer->email ?? 'noreply@askproai.de',
                    'phone' => $appointment->customer->phone,
                    'notes' => $appointment->notes,
                ],
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'source' => 'phone_ai'
                ],
                'timeZone' => 'Europe/Berlin',
            ]);
            
            $appointment->update([
                'calcom_booking_id' => $calcomBooking['id'] ?? null,
                'external_id' => $calcomBooking['uid'] ?? null,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to sync appointment with calendar', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            
            // Queue retry job for calendar sync
            \App\Jobs\RetryCalendarSyncJob::dispatch($appointment->id)
                ->delay(now()->addMinutes(5))
                ->onQueue('calendar-sync');
        }
    }
    
    /**
     * Send booking confirmations
     */
    private function sendConfirmations(Appointment $appointment): void
    {
        try {
            // Send customer confirmation
            if ($appointment->customer->email) {
                $this->notificationService->sendAppointmentConfirmation($appointment);
            }
            
            // Send SMS if available
            if ($appointment->customer->phone) {
                $this->notificationService->sendAppointmentSms($appointment);
            }
            
            // Notify staff
            if ($appointment->staff->email) {
                $this->notificationService->notifyStaffNewAppointment($appointment);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to send appointment confirmations', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            // Don't fail the booking if notifications fail
        }
    }
    
    /**
     * Prepare and validate booking data
     */
    private function prepareBookingData(array $data, ?Call $call): array
    {
        // Extract data from call transcript if available
        if ($call && !empty($call->transcript)) {
            $extractedData = $this->extractDataFromTranscript($call->transcript);
            $data = array_merge($extractedData, $data);
        }
        
        // Validate required fields
        $required = ['customer', 'service_id', 'staff_id', 'starts_at'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Pflichtfeld fehlt: {$field}");
            }
        }
        
        return $data;
    }
    
    /**
     * Extract booking data from AI call transcript
     */
    private function extractDataFromTranscript(string $transcript): array
    {
        // This would use NLP or structured extraction
        // For now, return empty array
        return [];
    }
    
    /**
     * Validate service exists and is bookable
     */
    private function validateService(string $serviceId): Service
    {
        $service = Service::find($serviceId);
        
        if (!$service || !$service->is_active) {
            throw new \InvalidArgumentException('Service nicht verfügbar');
        }
        
        return $service;
    }
    
    /**
     * Validate staff can perform service
     */
    private function validateStaff(string $staffId, Service $service): Staff
    {
        $staff = Staff::find($staffId);
        
        if (!$staff || !$staff->active) {
            throw new \InvalidArgumentException('Mitarbeiter nicht verfügbar');
        }
        
        // Check if staff offers this service
        if (!$staff->services->contains($service->id)) {
            throw new \InvalidArgumentException('Mitarbeiter bietet diese Leistung nicht an');
        }
        
        return $staff;
    }
    
    /**
     * Validate branch
     */
    private function validateBranch(string $branchId): Branch
    {
        $branch = Branch::find($branchId);
        
        if (!$branch || !$branch->is_active) {
            throw new \InvalidArgumentException('Filiale nicht verfügbar');
        }
        
        return $branch;
    }
    
    /**
     * Calculate end time based on service duration
     */
    private function calculateEndTime(string $startTime, Service $service): Carbon
    {
        return Carbon::parse($startTime)->addMinutes($service->duration ?? 30);
    }
    
    /**
     * Check if slot is still available (with lock held)
     */
    private function isSlotStillAvailable(Staff $staff, Carbon $start, Carbon $end): bool
    {
        return !Appointment::where('staff_id', $staff->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('starts_at', [$start, $end->subMinute()])
                    ->orWhereBetween('ends_at', [$start->addMinute(), $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('starts_at', '<=', $start)
                            ->where('ends_at', '>=', $end);
                    });
            })
            ->exists();
    }
    
    /**
     * Clean up expired locks (delegates to TimeSlotLockManager)
     */
    public function cleanupExpiredLocks(): int
    {
        return $this->lockManager->cleanupExpiredLocks();
    }
    
    /**
     * Extend an existing lock (useful for long-running operations)
     */
    public function extendLock(string $lockToken, int $additionalMinutes = 5): bool
    {
        return $this->lockManager->extendLock($lockToken, $additionalMinutes);
    }
    
    /**
     * Check if a time slot is currently locked
     */
    public function isSlotLocked($staff, $startTime, $endTime): bool
    {
        $staffId = is_object($staff) ? $staff->id : $staff;
        return $this->lockManager->isSlotLocked($staffId, $startTime, $endTime);
    }
    
}