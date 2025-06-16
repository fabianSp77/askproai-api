<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Branch;
use App\Models\Call;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\CalcomV2Service;
use App\Services\NotificationService;
use App\Exceptions\BookingException;
use App\Exceptions\AvailabilityException;

class AppointmentBookingService
{
    public function __construct(
        private CalcomV2Service $calcomService,
        private NotificationService $notificationService,
        private AvailabilityService $availabilityService
    ) {}
    
    /**
     * Complete phone-to-appointment booking flow
     */
    public function bookFromPhoneCall(array $data, ?Call $call = null): Appointment
    {
        return DB::transaction(function () use ($data, $call) {
            try {
                // 1. Validate and prepare data
                $bookingData = $this->prepareBookingData($data, $call);
                
                // 2. Find or create customer
                $customer = $this->findOrCreateCustomer($bookingData['customer']);
                
                // 3. Validate service and staff
                $service = $this->validateService($bookingData['service_id']);
                $staff = $this->validateStaff($bookingData['staff_id'], $service);
                $branch = $this->validateBranch($bookingData['branch_id'] ?? $staff->home_branch_id);
                
                // 4. Check availability
                $timeSlot = $this->checkAndReserveTimeSlot(
                    $staff,
                    $bookingData['starts_at'],
                    $bookingData['ends_at'] ?? $this->calculateEndTime($bookingData['starts_at'], $service)
                );
                
                // 5. Create appointment
                $appointment = $this->createAppointment([
                    'customer_id' => $customer->id,
                    'service_id' => $service->id,
                    'staff_id' => $staff->id,
                    'branch_id' => $branch->id,
                    'company_id' => $staff->company_id,
                    'starts_at' => $timeSlot['start'],
                    'ends_at' => $timeSlot['end'],
                    'status' => 'confirmed',
                    'call_id' => $call?->id,
                    'source' => 'phone',
                    'notes' => $bookingData['notes'] ?? null,
                    'metadata' => [
                        'booked_via' => 'phone_ai',
                        'call_duration' => $call?->duration,
                        'customer_phone' => $customer->phone,
                    ]
                ]);
                
                // 6. Sync with calendar system
                $this->syncWithCalendar($appointment);
                
                // 7. Send confirmations
                $this->sendConfirmations($appointment);
                
                // 8. Update call record
                if ($call) {
                    $call->update([
                        'appointment_id' => $appointment->id,
                        'status' => 'completed'
                    ]);
                }
                
                Log::info('Appointment booked successfully from phone call', [
                    'appointment_id' => $appointment->id,
                    'call_id' => $call?->id
                ]);
                
                return $appointment;
                
            } catch (\Exception $e) {
                Log::error('Failed to book appointment from phone call', [
                    'error' => $e->getMessage(),
                    'data' => $data,
                    'call_id' => $call?->id
                ]);
                
                // Check if it's a known exception type
                if ($e instanceof BookingException || $e instanceof AvailabilityException) {
                    throw $e;
                }
                
                // Otherwise wrap in BookingException
                throw new BookingException(
                    'Terminbuchung fehlgeschlagen: ' . $e->getMessage(),
                    BookingException::ERROR_GENERAL,
                    [
                        'original_error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ],
                    $e->getCode(),
                    $e
                );
            }
        });
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
     * Check availability and reserve time slot
     */
    private function checkAndReserveTimeSlot(Staff $staff, string $startTime, string $endTime): array
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        
        // Check if slot is available
        if (!$this->availabilityService->isSlotAvailable($staff, $start, $end)) {
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
            
            // Use first alternative
            $alternative = $alternatives[0];
            $start = $alternative['start'];
            $end = $alternative['end'];
        }
        
        // Reserve the slot temporarily (released on rollback if booking fails)
        $this->availabilityService->reserveSlot($staff, $start, $end);
        
        return [
            'start' => $start,
            'end' => $end
        ];
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
            // Don't fail the booking if calendar sync fails
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
}