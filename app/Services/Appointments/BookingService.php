<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Services\Appointments\Contracts\BookingServiceInterface;
use App\Jobs\SyncAppointmentToCalcomJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * BookingService
 *
 * Handles appointment booking operations including validation,
 * duplicate checking, and Cal.com synchronization.
 */
class BookingService implements BookingServiceInterface
{
    /**
     * Create a new appointment booking
     */
    public function createAppointment(array $data): Appointment
    {
        // Validate data first
        $validation = $this->validateBookingData($data);
        if (!$validation['valid']) {
            throw new \Exception('Invalid booking data: ' . implode(', ', $validation['errors']));
        }

        // Check for duplicates
        if ($this->checkDuplicateBooking(
            $data['customer_id'],
            $data['service_id'],
            Carbon::parse($data['start_time'])
        )) {
            throw new \Exception('Duplicate booking detected');
        }

        return DB::transaction(function () use ($data) {
            // Calculate end time
            $startTime = Carbon::parse($data['start_time']);
            $endTime = $this->calculateEndTime($startTime, $data['service_id']);

            // Create appointment
            // NOTE: branch_id is REQUIRED and VALIDATED (not optional)
            $appointment = Appointment::create([
                'customer_id' => $data['customer_id'],
                'service_id' => $data['service_id'],
                'staff_id' => $data['staff_id'] ?? null,
                'branch_id' => $data['branch_id'],  // ← REQUIRED (validated above)
                'starts_at' => $startTime,
                'ends_at' => $endTime,
                'status' => 'scheduled',
                'notes' => $data['notes'] ?? null,
                'company_id' => auth()->user()->company_id,
            ]);

            Log::info('[BookingService] Appointment created', [
                'appointment_id' => $appointment->id,
                'customer_id' => $appointment->customer_id,
                'service_id' => $appointment->service_id,
                'starts_at' => $appointment->starts_at->toIso8601String(),
            ]);

            // Dispatch sync job (async)
            SyncAppointmentToCalcomJob::dispatch($appointment, 'create');

            return $appointment;
        });
    }

    /**
     * Validate booking data before creation
     *
     * CRITICAL: Validates all required fields including branch_id
     * for multi-tenant isolation and Cal.com sync context.
     */
    public function validateBookingData(array $data): array
    {
        $errors = [];
        $companyId = auth()->user()->company_id ?? null;

        // ═══════════════════════════════════════════════════════════
        // CRITICAL: branch_id is REQUIRED for multi-tenant isolation
        // ═══════════════════════════════════════════════════════════
        if (empty($data['branch_id'])) {
            $errors[] = 'Branch ID is required for appointment creation';
        } else {
            // Validate branch exists and belongs to company
            $branchBelongsToCompany = Branch::where('id', $data['branch_id'])
                ->where('company_id', $companyId)
                ->exists();

            if (!$branchBelongsToCompany) {
                $errors[] = 'Selected branch does not belong to your company';
            }
        }

        // Required fields
        if (empty($data['customer_id'])) {
            $errors[] = 'Customer ID is required';
        }

        if (empty($data['service_id'])) {
            $errors[] = 'Service ID is required';
        }

        if (empty($data['start_time'])) {
            $errors[] = 'Start time is required';
        }

        // Validate datetime format
        if (!empty($data['start_time'])) {
            try {
                $startTime = Carbon::parse($data['start_time']);

                // Check if datetime is in the past
                if ($startTime->isPast()) {
                    $errors[] = 'Cannot book appointments in the past';
                }

                // Check if datetime is too far in future (e.g., > 6 months)
                if ($startTime->isAfter(now()->addMonths(6))) {
                    $errors[] = 'Cannot book appointments more than 6 months in advance';
                }
            } catch (\Exception $e) {
                $errors[] = 'Invalid datetime format';
            }
        }

        // Validate service exists
        if (!empty($data['service_id'])) {
            $service = Service::find($data['service_id']);
            if (!$service) {
                $errors[] = 'Service not found';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Check for duplicate bookings
     */
    public function checkDuplicateBooking(
        string $customerId,
        string $serviceId,
        Carbon $datetime
    ): bool {
        // Check for existing appointment within ±15 minutes
        $existingAppointment = Appointment::where('customer_id', $customerId)
            ->where('service_id', $serviceId)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('starts_at', [
                $datetime->copy()->subMinutes(15),
                $datetime->copy()->addMinutes(15),
            ])
            ->exists();

        if ($existingAppointment) {
            Log::warning('[BookingService] Duplicate booking detected', [
                'customer_id' => $customerId,
                'service_id' => $serviceId,
                'datetime' => $datetime->toIso8601String(),
            ]);
        }

        return $existingAppointment;
    }

    /**
     * Find or create customer for booking
     */
    public function findOrCreateCustomer(array $customerData): Customer
    {
        $companyId = auth()->user()->company_id;

        // Try to find existing customer by phone or email
        $customer = Customer::where('company_id', $companyId)
            ->where(function ($query) use ($customerData) {
                if (!empty($customerData['phone'])) {
                    $query->where('phone', $customerData['phone']);
                }
                if (!empty($customerData['email'])) {
                    $query->orWhere('email', $customerData['email']);
                }
            })
            ->first();

        if ($customer) {
            Log::info('[BookingService] Existing customer found', [
                'customer_id' => $customer->id,
                'phone' => $customer->phone,
            ]);
            return $customer;
        }

        // Create new customer
        $customer = Customer::create([
            'name' => $customerData['name'] ?? '',
            'phone' => $customerData['phone'] ?? null,
            'email' => $customerData['email'] ?? null,
            'company_id' => $companyId,
        ]);

        Log::info('[BookingService] New customer created', [
            'customer_id' => $customer->id,
            'phone' => $customer->phone,
        ]);

        return $customer;
    }

    /**
     * Sync appointment to Cal.com
     */
    public function syncToCalcom(Appointment $appointment): array
    {
        // This is handled by SyncAppointmentToCalcomJob
        // Dispatch the job and return immediately
        SyncAppointmentToCalcomJob::dispatch($appointment, 'create');

        return [
            'success' => true,
            'message' => 'Sync job dispatched',
            'booking_uid' => null, // Will be updated asynchronously
        ];
    }

    /**
     * Calculate appointment end time based on service duration
     */
    public function calculateEndTime(Carbon $startTime, string $serviceId): Carbon
    {
        $service = Service::find($serviceId);

        if (!$service || !$service->duration_minutes) {
            // Default to 30 minutes if no duration specified
            return $startTime->copy()->addMinutes(30);
        }

        return $startTime->copy()->addMinutes($service->duration_minutes);
    }

    /**
     * Get booking confirmation data
     */
    public function getConfirmationData(Appointment $appointment): array
    {
        $appointment->load(['customer', 'service', 'staff', 'branch']);

        return [
            'appointment_id' => $appointment->id,
            'customer' => [
                'name' => $appointment->customer->full_name,
                'phone' => $appointment->customer->phone,
                'email' => $appointment->customer->email,
            ],
            'service' => [
                'name' => $appointment->service->name,
                'duration' => $appointment->service->duration_minutes,
                'price' => $appointment->service->price,
            ],
            'datetime' => [
                'start' => $appointment->starts_at->format('d.m.Y H:i'),
                'end' => $appointment->ends_at->format('H:i'),
                'day_name' => $appointment->starts_at->locale('de')->isoFormat('dddd'),
            ],
            'staff' => $appointment->staff ? [
                'name' => $appointment->staff->name,
            ] : null,
            'branch' => $appointment->branch ? [
                'name' => $appointment->branch->name,
                'address' => $appointment->branch->address,
            ] : null,
            'status' => $appointment->status,
        ];
    }

    /**
     * Cancel an appointment
     */
    public function cancelAppointment(string $appointmentId, string $reason): bool
    {
        $appointment = Appointment::find($appointmentId);

        if (!$appointment) {
            throw new \Exception('Appointment not found');
        }

        if ($appointment->status === 'cancelled') {
            return true; // Already cancelled
        }

        $appointment->update([
            'status' => 'cancelled',
            'notes' => ($appointment->notes ? $appointment->notes . "\n\n" : '') .
                       "Storniert: {$reason}",
        ]);

        Log::info('[BookingService] Appointment cancelled', [
            'appointment_id' => $appointment->id,
            'reason' => $reason,
        ]);

        // TODO: Sync cancellation to Cal.com
        // This would require implementing Cal.com booking cancellation

        return true;
    }

    /**
     * Reschedule an appointment
     */
    public function rescheduleAppointment(string $appointmentId, Carbon $newDatetime): Appointment
    {
        $appointment = Appointment::find($appointmentId);

        if (!$appointment) {
            throw new \Exception('Appointment not found');
        }

        if ($appointment->status === 'cancelled') {
            throw new \Exception('Cannot reschedule cancelled appointment');
        }

        // Calculate new end time
        $newEndTime = $this->calculateEndTime($newDatetime, $appointment->service_id);

        $oldStartTime = $appointment->starts_at->format('d.m.Y H:i');

        $appointment->update([
            'starts_at' => $newDatetime,
            'ends_at' => $newEndTime,
            'notes' => ($appointment->notes ? $appointment->notes . "\n\n" : '') .
                       "Umgebucht von {$oldStartTime} zu {$newDatetime->format('d.m.Y H:i')}",
        ]);

        Log::info('[BookingService] Appointment rescheduled', [
            'appointment_id' => $appointment->id,
            'old_time' => $oldStartTime,
            'new_time' => $newDatetime->format('d.m.Y H:i'),
        ]);

        // Sync to Cal.com
        SyncAppointmentToCalcomJob::dispatch($appointment, 'reschedule');

        return $appointment->fresh();
    }
}
