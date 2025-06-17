<?php

namespace App\Services\Booking;

use App\Exceptions\AvailabilityException;
use App\Exceptions\BookingException;
use App\Exceptions\SlotUnavailableException;
use App\Jobs\Appointment\SendAppointmentNotificationsJob;
use App\Jobs\Appointment\SyncAppointmentToCalcomJob;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Results\AppointmentResult;
use App\Services\AvailabilityService;
use App\Services\CalcomV2Service;
use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\Locking\TimeSlotLockManager;
use App\Services\Logging\StructuredLogger;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnhancedBookingService
{
    private TimeSlotLockManager $lockManager;
    private CircuitBreaker $circuitBreaker;
    private StructuredLogger $logger;
    private CalcomV2Service $calcomService;
    private NotificationService $notificationService;
    private AvailabilityService $availabilityService;

    public function __construct(
        TimeSlotLockManager $lockManager,
        CircuitBreaker $circuitBreaker,
        StructuredLogger $logger,
        CalcomV2Service $calcomService,
        NotificationService $notificationService,
        AvailabilityService $availabilityService
    ) {
        $this->lockManager = $lockManager;
        $this->circuitBreaker = $circuitBreaker;
        $this->logger = $logger;
        $this->calcomService = $calcomService;
        $this->notificationService = $notificationService;
        $this->availabilityService = $availabilityService;
    }

    /**
     * Create appointment with enhanced error handling and resilience
     */
    public function createAppointment(array $data): AppointmentResult
    {
        $correlationId = Str::uuid()->toString();
        $lockToken = null;
        
        // Set correlation ID for all logs in this request
        $this->logger->setCorrelationId($correlationId);
        
        $this->logger->logBookingFlow('appointment_creation_started', [
            'data' => $this->sanitizeBookingData($data),
            'source' => $data['source'] ?? 'unknown',
        ]);

        try {
            // 1. Validate and prepare data
            $validatedData = $this->validateBookingData($data);
            
            // 2. Acquire time slot lock
            $lockToken = $this->lockManager->acquireLock(
                $validatedData['branch_id'],
                $validatedData['staff_id'],
                $validatedData['start_time'],
                $validatedData['end_time']
            );

            if (!$lockToken) {
                $this->logger->warning('Failed to acquire slot lock', [
                    'branch_id' => $validatedData['branch_id'],
                    'staff_id' => $validatedData['staff_id'],
                    'start_time' => $validatedData['start_time'],
                ]);
                
                return AppointmentResult::failure(
                    'Time slot is no longer available',
                    'slot_unavailable'
                );
            }

            // Start database transaction
            return DB::transaction(function () use ($validatedData, $lockToken, $correlationId) {
                
                // 3. Double-check availability with lock held
                if (!$this->isSlotStillAvailable($validatedData)) {
                    throw new SlotUnavailableException('Time slot is no longer available');
                }

                // 4. Find or create customer
                $customer = $this->findOrCreateCustomer($validatedData['customer']);

                // 5. Create appointment record
                $appointment = $this->createAppointmentRecord([
                    'customer_id' => $customer->id,
                    'service_id' => $validatedData['service_id'],
                    'staff_id' => $validatedData['staff_id'],
                    'branch_id' => $validatedData['branch_id'],
                    'company_id' => $validatedData['company_id'] ?? $customer->company_id,
                    'starts_at' => $validatedData['start_time'],
                    'ends_at' => $validatedData['end_time'],
                    'status' => 'scheduled',
                    'source' => $validatedData['source'] ?? 'phone',
                    'notes' => $validatedData['notes'] ?? null,
                    'metadata' => array_merge($validatedData['metadata'] ?? [], [
                        'correlation_id' => $correlationId,
                        'lock_token' => $lockToken,
                        'booked_at' => now()->toIso8601String(),
                    ]),
                    'call_id' => $validatedData['call_id'] ?? null,
                ]);

                // 6. Sync to Cal.com with Circuit Breaker
                $calcomSyncResult = $this->syncToCalcom($appointment, $correlationId);
                
                if (!$calcomSyncResult['success']) {
                    // Queue for retry instead of failing the entire booking
                    dispatch(new SyncAppointmentToCalcomJob($appointment, $correlationId))
                        ->onQueue('calendar-sync')
                        ->delay(now()->addMinutes(5));
                    
                    $this->logger->warning('Cal.com sync failed, queued for retry', [
                        'appointment_id' => $appointment->id,
                        'error' => $calcomSyncResult['error'],
                    ]);
                }

                // 7. Send notifications asynchronously
                dispatch(new SendAppointmentNotificationsJob(
                    $appointment,
                    $this->determineNotificationChannels($appointment),
                    'confirmation',
                    $correlationId
                ));

                $this->logger->logBookingFlow('appointment_creation_completed', [
                    'appointment_id' => $appointment->id,
                    'customer_id' => $customer->id,
                    'calcom_synced' => $calcomSyncResult['success'],
                ]);

                // Build success result with warnings if any
                $warnings = [];
                if (!$calcomSyncResult['success']) {
                    $warnings[] = 'Calendar sync pending - will retry automatically';
                }

                if (!empty($warnings)) {
                    return AppointmentResult::successWithWarnings(
                        $appointment,
                        $warnings,
                        'Appointment booked successfully',
                        ['correlation_id' => $correlationId]
                    );
                }

                return AppointmentResult::success(
                    $appointment,
                    'Appointment booked successfully',
                    ['correlation_id' => $correlationId]
                );

            });

        } catch (SlotUnavailableException $e) {
            $this->logger->warning('Slot unavailable during booking', [
                'error' => $e->getMessage(),
                'data' => $this->sanitizeBookingData($data),
            ]);
            
            return AppointmentResult::failure(
                $e->getMessage(),
                'slot_unavailable'
            );
            
        } catch (BookingException $e) {
            $this->logger->error('Booking exception occurred', [
                'error' => $e->getMessage(),
                'code' => $e->getErrorCode(),
                'data' => $this->sanitizeBookingData($data),
            ]);
            
            return AppointmentResult::failure(
                $e->getMessage(),
                $e->getErrorCode()
            );
            
        } catch (\Exception $e) {
            $this->logger->logError($e, [
                'context' => 'appointment_creation',
                'data' => $this->sanitizeBookingData($data),
            ]);
            
            return AppointmentResult::failure(
                'An unexpected error occurred while booking the appointment',
                'general_error'
            );
            
        } finally {
            // Always release lock
            if ($lockToken) {
                $this->lockManager->releaseLock($lockToken);
            }
        }
    }

    /**
     * Book appointment from phone call data
     */
    public function bookFromPhoneCall($callOrData, ?array $appointmentData = null): AppointmentResult
    {
        // Determine if we're working with a Call object or data array
        $call = null;
        $data = [];
        
        if ($callOrData instanceof Call) {
            $call = $callOrData;
            $data = $appointmentData ?? [];
        } else {
            $data = $callOrData;
        }

        // Prepare booking data
        $bookingData = $this->prepareBookingDataFromCall($data, $call);
        $bookingData['source'] = 'phone';
        $bookingData['call_id'] = $call?->id;

        // Use the main createAppointment method
        $result = $this->createAppointment($bookingData);

        // Update call record if successful
        if ($result->isSuccess() && $call) {
            $call->update([
                'appointment_id' => $result->getAppointment()->id,
                'status' => 'completed',
            ]);
        }

        return $result;
    }

    /**
     * Validate booking data
     */
    private function validateBookingData(array $data): array
    {
        $required = ['staff_id', 'service_id', 'start_time', 'customer'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new BookingException("Required field missing: {$field}", 'missing_required_field');
            }
        }

        // Parse times
        $startTime = Carbon::parse($data['start_time']);
        $endTime = isset($data['end_time']) 
            ? Carbon::parse($data['end_time'])
            : $this->calculateEndTime($startTime, $data['service_id']);

        // Validate service
        $service = Service::find($data['service_id']);
        if (!$service || !$service->is_active) {
            throw new BookingException('Service not available', 'service_unavailable');
        }

        // Validate staff
        $staff = Staff::find($data['staff_id']);
        if (!$staff || !$staff->active) {
            throw new BookingException('Staff member not available', 'staff_unavailable');
        }

        // Validate staff can perform service
        if (!$staff->services->contains($service->id)) {
            throw new BookingException('Staff member does not offer this service', 'staff_service_mismatch');
        }

        // Get branch
        $branchId = $data['branch_id'] ?? $staff->home_branch_id;
        $branch = Branch::find($branchId);
        if (!$branch || !$branch->is_active) {
            throw new BookingException('Branch not available', 'branch_unavailable');
        }

        return [
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'branch_id' => $branch->id,
            'company_id' => $data['company_id'] ?? $branch->company_id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'customer' => $data['customer'],
            'source' => $data['source'] ?? 'unknown',
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? [],
            'call_id' => $data['call_id'] ?? null,
        ];
    }

    /**
     * Check if slot is still available
     */
    private function isSlotStillAvailable(array $data): bool
    {
        return !Appointment::where('staff_id', $data['staff_id'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($data) {
                $query->whereBetween('starts_at', [$data['start_time'], $data['end_time']->subMinute()])
                    ->orWhereBetween('ends_at', [$data['start_time']->addMinute(), $data['end_time']])
                    ->orWhere(function ($q) use ($data) {
                        $q->where('starts_at', '<=', $data['start_time'])
                            ->where('ends_at', '>=', $data['end_time']);
                    });
            })
            ->exists();
    }

    /**
     * Find or create customer
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
            'name' => $customerData['name'] ?? 'Unknown',
            'phone' => $customerData['phone'],
            'email' => $customerData['email'] ?? null,
            'company_id' => $customerData['company_id'],
            'source' => 'phone_ai',
            'notes' => 'Automatically captured via phone AI',
        ]);
    }

    /**
     * Create appointment record
     */
    private function createAppointmentRecord(array $data): Appointment
    {
        return Appointment::create($data);
    }

    /**
     * Sync appointment to Cal.com with circuit breaker
     */
    private function syncToCalcom(Appointment $appointment, string $correlationId): array
    {
        try {
            $result = $this->circuitBreaker->call('calcom', function () use ($appointment, $correlationId) {
                $startTime = microtime(true);
                
                $bookingData = [
                    'eventTypeId' => $appointment->service->calcom_event_type_id,
                    'start' => $appointment->starts_at->toIso8601String(),
                    'end' => $appointment->ends_at->toIso8601String(),
                    'responses' => [
                        'name' => $appointment->customer->name,
                        'email' => $appointment->customer->email ?? 'noreply@askproai.de',
                        'phone' => $appointment->customer->phone,
                        'notes' => $appointment->notes,
                    ],
                    'metadata' => [
                        'appointment_id' => $appointment->id,
                        'source' => 'phone_ai',
                        'correlation_id' => $correlationId,
                    ],
                    'timeZone' => 'Europe/Berlin',
                ];

                $response = $this->calcomService->createBooking($bookingData);
                
                $duration = microtime(true) - $startTime;
                
                $this->logger->logApiCall(
                    'calcom',
                    '/bookings',
                    'POST',
                    ['body' => $bookingData],
                    ['status' => 200, 'body' => $response],
                    $duration
                );

                return $response;
            });

            // Update appointment with Cal.com details
            $appointment->update([
                'calcom_booking_id' => $result['id'] ?? null,
                'external_id' => $result['uid'] ?? null,
            ]);

            return ['success' => true, 'data' => $result];

        } catch (\Exception $e) {
            $this->logger->warning('Cal.com sync failed during booking', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Determine notification channels based on customer and company settings
     */
    private function determineNotificationChannels(Appointment $appointment): array
    {
        $channels = [];

        // Always try email if available
        if ($appointment->customer->email) {
            $channels[] = 'email';
        }

        // Check company settings for additional channels
        $settings = $appointment->company->settings ?? [];
        
        if (($settings['sms_enabled'] ?? false) && $appointment->customer->phone) {
            $channels[] = 'sms';
        }

        if (($settings['whatsapp_enabled'] ?? false) && $appointment->customer->phone) {
            $channels[] = 'whatsapp';
        }

        // Always notify staff if email available
        if ($appointment->staff && $appointment->staff->email) {
            $channels[] = 'staff';
        }

        return $channels;
    }

    /**
     * Prepare booking data from phone call
     */
    private function prepareBookingDataFromCall(array $data, ?Call $call = null): array
    {
        // Parse date and time from German format
        $dateStr = $data['datum'] ?? '';
        $timeStr = $data['uhrzeit'] ?? '';
        
        try {
            if (strpos($dateStr, '.') !== false) {
                $date = Carbon::createFromFormat('d.m.Y', $dateStr);
            } else {
                $date = Carbon::parse($dateStr);
            }
            
            // Parse time
            $timeStr = str_replace(' Uhr', '', $timeStr);
            list($hour, $minute) = explode(':', $timeStr . ':00');
            $date->setTime((int)$hour, (int)$minute);
            
        } catch (\Exception $e) {
            $this->logger->warning('Failed to parse date/time from call data', [
                'datum' => $dateStr,
                'uhrzeit' => $timeStr,
                'error' => $e->getMessage(),
            ]);
            
            // Default to tomorrow at 10:00
            $date = Carbon::tomorrow()->setTime(10, 0);
        }

        return [
            'start_time' => $date,
            'end_time' => null, // Will be calculated based on service
            'customer' => [
                'name' => $data['name'] ?? 'Unknown',
                'phone' => $data['telefonnummer'] ?? $call?->from_number,
                'email' => $data['email'] ?? null,
                'company_id' => $call?->company_id,
            ],
            'service_id' => $this->findServiceIdByName($data['dienstleistung'] ?? '', $call?->company_id),
            'staff_id' => $this->findStaffIdByName($data['mitarbeiter_wunsch'] ?? '', $call?->company_id),
            'branch_id' => $call?->branch_id,
            'notes' => $this->generateNotesFromData($data),
            'metadata' => [
                'raw_call_data' => $data,
                'customer_preferences' => $data['kundenpraeferenzen'] ?? null,
            ],
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
        
        return $query->value('id');
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
        
        return $query->value('id');
    }

    /**
     * Generate notes from call data
     */
    private function generateNotesFromData(array $data): string
    {
        $notes = [];
        
        if (!empty($data['dienstleistung'])) {
            $notes[] = "Requested service: " . $data['dienstleistung'];
        }
        
        foreach ($data as $key => $value) {
            if (!in_array($key, ['datum', 'uhrzeit', 'name', 'telefonnummer', 'email', 'dienstleistung']) && !empty($value)) {
                $notes[] = ucfirst($key) . ": " . $value;
            }
        }
        
        return implode("\n", $notes);
    }

    /**
     * Calculate end time based on service duration
     */
    private function calculateEndTime(Carbon $startTime, $serviceId): Carbon
    {
        $service = Service::find($serviceId);
        $duration = $service ? $service->duration : 30;
        
        return $startTime->copy()->addMinutes($duration);
    }

    /**
     * Sanitize booking data for logging
     */
    private function sanitizeBookingData(array $data): array
    {
        $sanitized = $data;
        
        // Remove sensitive customer data
        if (isset($sanitized['customer'])) {
            $sanitized['customer'] = array_intersect_key(
                $sanitized['customer'],
                array_flip(['name', 'company_id'])
            );
        }
        
        return $sanitized;
    }
}