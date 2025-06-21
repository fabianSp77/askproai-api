<?php
// MARKED_FOR_DELETION - 2025-06-17


namespace App\Services;

use App\Models\Appointment;
use App\Repositories\AppointmentRepository;
use App\Repositories\CustomerRepository;
use App\Traits\TransactionalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\AppointmentCreated;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentRescheduled;
use App\Services\CalcomV2Service;

class AppointmentService
{
    use TransactionalService;
    protected AppointmentRepository $appointmentRepository;
    protected CustomerRepository $customerRepository;
    protected CalcomV2Service $calcomService;

    public function __construct(
        AppointmentRepository $appointmentRepository,
        CustomerRepository $customerRepository,
        CalcomV2Service $calcomService
    ) {
        $this->appointmentRepository = $appointmentRepository;
        $this->customerRepository = $customerRepository;
        $this->calcomService = $calcomService;
    }

    /**
     * Create new appointment
     */
    public function create(array $data): Appointment
    {
        $startTime = microtime(true);
        $context = [
            'customer_phone' => $data['customer_phone'] ?? 'unknown',
            'staff_id' => $data['staff_id'] ?? null,
            'operation' => 'create_appointment'
        ];
        
        try {
            return $this->executeInTransaction(function () use ($data) {
                // Find or create customer
                $customer = $this->customerRepository->findOrCreate([
                    'name' => $data['customer_name'],
                    'email' => $data['customer_email'] ?? null,
                    'phone' => $data['customer_phone'],
                    'company_id' => $data['company_id'] ?? auth()->user()->company_id,
                ]);

                // Check availability
                if (!$this->checkAvailability($data['staff_id'], $data['starts_at'], $data['ends_at'])) {
                    throw new \Exception('Time slot is not available');
                }

                // Create appointment
                $appointment = $this->appointmentRepository->create([
                    'customer_id' => $customer->id,
                    'staff_id' => $data['staff_id'],
                    'service_id' => $data['service_id'] ?? null,
                    'branch_id' => $data['branch_id'],
                    'company_id' => $data['company_id'] ?? auth()->user()->company_id,
                    'starts_at' => $data['starts_at'],
                    'ends_at' => $data['ends_at'],
                    'status' => 'scheduled',
                    'price' => $data['price'] ?? 0,
                    'notes' => $data['notes'] ?? null,
                    'source' => $data['source'] ?? 'manual',
                ]);

                // Create Cal.com booking if event type is provided
                if (!empty($data['calcom_event_type_id'])) {
                    try {
                        $calcomBooking = $this->calcomService->createBooking([
                            'eventTypeId' => $data['calcom_event_type_id'],
                            'start' => $data['starts_at'],
                            'responses' => [
                                'name' => $customer->name,
                                'email' => $customer->email ?? 'noreply@askproai.de',
                                'phone' => $customer->phone,
                            ],
                            'metadata' => [
                                'appointment_id' => $appointment->id,
                            ],
                        ]);

                        // Update appointment with Cal.com booking ID
                        $appointment->update([
                            'calcom_booking_id' => $calcomBooking['id'] ?? null,
                            'calcom_event_type_id' => $data['calcom_event_type_id'],
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to create Cal.com booking', [
                            'appointment_id' => $appointment->id,
                            'error' => $e->getMessage(),
                        ]);
                        // Don't fail the appointment creation if Cal.com sync fails
                    }
                }

                // Fire event
                event(new AppointmentCreated($appointment));
                
                $this->logTransactionMetrics('create', $startTime, true, [
                    'appointment_id' => $appointment->id,
                    'customer_id' => $customer->id,
                ]);

                return $appointment->fresh(['customer', 'staff', 'service', 'branch']);
            }, $context, 2);
            
        } catch (\Throwable $e) {
            $this->logTransactionMetrics('create', $startTime, false, [
                'error' => $e->getMessage()
            ]);
            
            Log::error('Failed to create appointment', array_merge($context, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));
            
            throw $e;
        }
    }

    /**
     * Update appointment
     */
    public function update(int $appointmentId, array $data): Appointment
    {
        $startTime = microtime(true);
        $context = [
            'appointment_id' => $appointmentId,
            'operation' => 'update_appointment'
        ];
        
        try {
            return $this->executeInTransaction(function () use ($appointmentId, $data) {
                $appointment = $this->appointmentRepository->findOrFail($appointmentId);
                
                // Check if rescheduling
                $isRescheduling = isset($data['starts_at']) && 
                                  $data['starts_at'] != $appointment->starts_at;

                if ($isRescheduling) {
                    // Check new availability
                    if (!$this->checkAvailability(
                        $data['staff_id'] ?? $appointment->staff_id,
                        $data['starts_at'],
                        $data['ends_at'],
                        $appointmentId
                    )) {
                        throw new \Exception('New time slot is not available');
                    }

                    // Update Cal.com booking if exists
                    if ($appointment->calcom_booking_id) {
                        try {
                            $this->calcomService->rescheduleBooking(
                                $appointment->calcom_booking_id,
                                $data['starts_at']
                            );
                        } catch (\Exception $e) {
                            Log::error('Failed to reschedule Cal.com booking', [
                                'appointment_id' => $appointmentId,
                                'error' => $e->getMessage(),
                            ]);
                            // Don't fail the update if Cal.com sync fails
                        }
                    }
                }

                // Update appointment
                $this->appointmentRepository->update($appointmentId, $data);
                
                if ($isRescheduling) {
                    event(new AppointmentRescheduled($appointment->fresh()));
                }
                
                $this->logTransactionMetrics('update', $startTime, true, [
                    'appointment_id' => $appointmentId,
                    'is_rescheduling' => $isRescheduling,
                ]);

                return $appointment->fresh();
            }, $context, 2);
            
        } catch (\Throwable $e) {
            $this->logTransactionMetrics('update', $startTime, false, [
                'error' => $e->getMessage()
            ]);
            
            Log::error('Failed to update appointment', array_merge($context, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));
            
            throw $e;
        }
    }

    /**
     * Cancel appointment
     */
    public function cancel(int $appointmentId, string $reason = null): bool
    {
        $startTime = microtime(true);
        $context = [
            'appointment_id' => $appointmentId,
            'operation' => 'cancel_appointment'
        ];
        
        try {
            return $this->executeInTransaction(function () use ($appointmentId, $reason) {
                $appointment = $this->appointmentRepository->findOrFail($appointmentId);
                
                // Check if already cancelled
                if ($appointment->status === 'cancelled') {
                    Log::warning('Appointment already cancelled', [
                        'appointment_id' => $appointmentId
                    ]);
                    return true;
                }
                
                // Cancel Cal.com booking if exists
                if ($appointment->calcom_booking_id) {
                    try {
                        $this->calcomService->cancelBooking(
                            $appointment->calcom_booking_id,
                            $reason
                        );
                    } catch (\Exception $e) {
                        Log::error('Failed to cancel Cal.com booking', [
                            'appointment_id' => $appointmentId,
                            'error' => $e->getMessage(),
                        ]);
                        // Don't fail the cancellation if Cal.com sync fails
                    }
                }

                // Update status
                $this->appointmentRepository->update($appointmentId, [
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason,
                ]);

                event(new AppointmentCancelled($appointment->fresh()));
                
                $this->logTransactionMetrics('cancel', $startTime, true, [
                    'appointment_id' => $appointmentId,
                ]);

                return true;
            }, $context, 2);
            
        } catch (\Throwable $e) {
            $this->logTransactionMetrics('cancel', $startTime, false, [
                'error' => $e->getMessage()
            ]);
            
            Log::error('Failed to cancel appointment', array_merge($context, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));
            
            throw $e;
        }
    }

    /**
     * Check availability
     */
    public function checkAvailability(
        int $staffId, 
        $startTime, 
        $endTime, 
        int $excludeAppointmentId = null
    ): bool {
        $startTime = Carbon::parse($startTime);
        $endTime = Carbon::parse($endTime);
        
        $overlapping = $this->appointmentRepository->getOverlapping(
            $staffId,
            $startTime,
            $endTime,
            $excludeAppointmentId
        );

        return $overlapping->isEmpty();
    }

    /**
     * Get available time slots
     */
    public function getAvailableSlots(
        int $staffId,
        Carbon $date,
        int $duration = 30
    ): array {
        $slots = [];
        $workingHours = $this->getWorkingHours($staffId, $date);
        
        if (!$workingHours) {
            return $slots;
        }

        $start = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHours['start']);
        $end = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHours['end']);
        
        // Get existing appointments
        $appointments = $this->appointmentRepository->getByStaff($staffId, $date);
        
        // Generate slots
        while ($start->copy()->addMinutes($duration)->lte($end)) {
            $slotEnd = $start->copy()->addMinutes($duration);
            
            // Check if slot overlaps with any appointment
            $isAvailable = !$appointments->contains(function ($appointment) use ($start, $slotEnd) {
                return $start->lt($appointment->ends_at) && $slotEnd->gt($appointment->starts_at);
            });
            
            if ($isAvailable) {
                $slots[] = [
                    'start' => $start->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                    'datetime' => $start->toIso8601String(),
                ];
            }
            
            $start->addMinutes($duration);
        }
        
        return $slots;
    }

    /**
     * Get working hours for staff
     */
    private function getWorkingHours(int $staffId, Carbon $date): ?array
    {
        // This is simplified - in real implementation would check
        // staff working hours from database
        $dayOfWeek = $date->dayOfWeek;
        
        // Example: Mon-Fri 9-17, Sat 9-13, Sun closed
        if ($dayOfWeek === 0) {
            return null; // Sunday
        } elseif ($dayOfWeek === 6) {
            return ['start' => '09:00', 'end' => '13:00']; // Saturday
        } else {
            return ['start' => '09:00', 'end' => '17:00']; // Weekdays
        }
    }

    /**
     * Complete appointment
     */
    public function complete(int $appointmentId, array $data = []): bool
    {
        return $this->appointmentRepository->update($appointmentId, array_merge([
            'status' => 'completed',
            'completed_at' => now(),
        ], $data));
    }

    /**
     * Mark as no-show
     */
    public function markAsNoShow(int $appointmentId): bool
    {
        $appointment = $this->appointmentRepository->findOrFail($appointmentId);
        
        // Update customer no-show count
        $customer = $appointment->customer;
        $customer->increment('no_show_count');
        
        return $this->appointmentRepository->update($appointmentId, [
            'status' => 'no_show',
            'no_show_at' => now(),
        ]);
    }

    /**
     * Get appointment statistics
     */
    public function getStatistics(Carbon $startDate, Carbon $endDate): array
    {
        return $this->appointmentRepository->getStatistics($startDate, $endDate);
    }
}