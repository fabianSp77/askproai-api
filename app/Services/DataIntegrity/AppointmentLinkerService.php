<?php

namespace App\Services\DataIntegrity;

use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentLinkerService
{
    /**
     * Link a call to an appointment with bidirectional relationship
     *
     * @param Call $call
     * @param Appointment $appointment
     * @param string $reason Description of why this link was created
     * @param int|null $userId User ID if manually linked
     * @return bool
     */
    public function linkAppointment(
        Call $call,
        Appointment $appointment,
        string $reason = 'auto_linked',
        ?int $userId = null
    ): bool {
        try {
            DB::beginTransaction();

            // Verify multi-tenant isolation
            if ($call->company_id !== $appointment->company_id) {
                Log::error('Attempted to link call to appointment from different company', [
                    'call_id' => $call->id,
                    'call_company_id' => $call->company_id,
                    'appointment_id' => $appointment->id,
                    'appointment_company_id' => $appointment->company_id,
                ]);
                return false;
            }

            // Update call with appointment link
            $call->update([
                'appointment_id' => $appointment->id,
                'appointment_link_status' => 'linked',
                'appointment_linked_at' => now(),
                'linking_metadata' => array_merge($call->linking_metadata ?? [], [
                    'appointment_linked_at' => now()->toIso8601String(),
                    'appointment_link_reason' => $reason,
                    'linked_by_user_id' => $userId,
                ]),
            ]);

            // Also link customer if appointment has one and call doesn't
            if ($appointment->customer_id && !$call->customer_id) {
                $customer = \App\Models\Customer::find($appointment->customer_id);
                if ($customer && $customer->company_id === $call->company_id) {
                    $linkerService = new CallCustomerLinkerService();
                    $linkerService->linkCustomer(
                        $call,
                        $customer,
                        'appointment_link',
                        95.0,
                        $userId
                    );
                }
            }

            DB::commit();

            Log::info('Call linked to appointment', [
                'call_id' => $call->id,
                'appointment_id' => $appointment->id,
                'reason' => $reason,
                'user_id' => $userId,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to link call to appointment', [
                'call_id' => $call->id,
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Unlink a call from an appointment
     *
     * @param Call $call
     * @param string $reason
     * @param int|null $userId
     * @return bool
     */
    public function unlinkAppointment(Call $call, string $reason, ?int $userId = null): bool
    {
        try {
            DB::beginTransaction();

            $previousAppointmentId = $call->appointment_id;

            $call->update([
                'appointment_id' => null,
                'appointment_link_status' => 'unlinked',
                'linking_metadata' => array_merge($call->linking_metadata ?? [], [
                    'appointment_unlinked_at' => now()->toIso8601String(),
                    'unlink_reason' => $reason,
                    'unlinked_by_user_id' => $userId,
                    'previous_appointment_id' => $previousAppointmentId,
                ]),
            ]);

            DB::commit();

            Log::info('Call unlinked from appointment', [
                'call_id' => $call->id,
                'previous_appointment_id' => $previousAppointmentId,
                'reason' => $reason,
                'user_id' => $userId,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to unlink call from appointment', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Find appointment by customer and time proximity
     *
     * @param Call $call
     * @return Appointment|null
     */
    public function findAppointmentByCustomerAndTime(Call $call): ?Appointment
    {
        if (!$call->customer_id || !$call->created_at) {
            return null;
        }

        // Look for appointments within 1 hour before/after the call
        $timeWindow = 60; // minutes

        $appointment = Appointment::where('customer_id', $call->customer_id)
            ->where('company_id', $call->company_id)
            ->whereBetween('starts_at', [
                $call->created_at->clone()->subMinutes($timeWindow),
                $call->created_at->clone()->addMinutes($timeWindow),
            ])
            ->orderBy('starts_at', 'asc')
            ->first();

        return $appointment;
    }

    /**
     * Find appointment by name and time proximity (fuzzy matching)
     *
     * @param Call $call
     * @return Appointment|null
     */
    public function findAppointmentByNameAndTime(Call $call): ?Appointment
    {
        if (!$call->customer_name || !$call->created_at) {
            return null;
        }

        // Look for appointments within 2 hours before/after the call
        $timeWindow = 120; // minutes

        $searchName = strtolower(trim($call->customer_name));

        // First try exact customer name match
        $appointments = Appointment::where('company_id', $call->company_id)
            ->whereBetween('starts_at', [
                $call->created_at->clone()->subMinutes($timeWindow),
                $call->created_at->clone()->addMinutes($timeWindow),
            ])
            ->whereHas('customer', function ($query) use ($searchName) {
                $query->whereRaw('LOWER(name) = ?', [$searchName]);
            })
            ->orderBy('starts_at', 'asc')
            ->first();

        if ($appointments) {
            return $appointments;
        }

        // Fallback: fuzzy match
        $appointments = Appointment::where('company_id', $call->company_id)
            ->whereBetween('starts_at', [
                $call->created_at->clone()->subMinutes($timeWindow),
                $call->created_at->clone()->addMinutes($timeWindow),
            ])
            ->whereHas('customer', function ($query) use ($searchName) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . $searchName . '%']);
            })
            ->orderBy('starts_at', 'asc')
            ->first();

        return $appointments;
    }

    /**
     * Update appointment status based on call outcome
     *
     * @param Call $call
     * @return bool
     */
    public function updateAppointmentStatusFromCall(Call $call): bool
    {
        if (!$call->appointment_id || !$call->session_outcome) {
            return false;
        }

        try {
            $appointment = Appointment::find($call->appointment_id);
            if (!$appointment) {
                return false;
            }

            // Map call outcomes to appointment statuses
            $newStatus = match ($call->session_outcome) {
                'appointment_cancelled' => 'cancelled',
                'appointment_rescheduled' => 'rescheduled',
                'appointment_booked' => 'scheduled',
                default => null,
            };

            if ($newStatus && $appointment->status !== $newStatus) {
                $oldStatus = $appointment->status;

                $appointment->update([
                    'status' => $newStatus,
                    'updated_at' => now(),
                ]);

                Log::info('Appointment status updated from call', [
                    'appointment_id' => $appointment->id,
                    'call_id' => $call->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'session_outcome' => $call->session_outcome,
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Failed to update appointment status from call', [
                'call_id' => $call->id,
                'appointment_id' => $call->appointment_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
