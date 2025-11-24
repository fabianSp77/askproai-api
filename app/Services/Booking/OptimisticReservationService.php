<?php

namespace App\Services\Booking;

use App\Models\AppointmentReservation;
use App\Models\Appointment;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OptimisticReservationService
{
    protected int $defaultTtlSeconds = 120; // 2 minutes
    protected int $compoundTtlSeconds = 180; // 3 minutes for compound services

    /**
     * Create a reservation for a time slot
     *
     * @param array $params [
     *   'call_id' => string,
     *   'customer_phone' => string,
     *   'customer_name' => ?string,
     *   'service_id' => int,
     *   'staff_id' => ?int,
     *   'start_time' => Carbon|string,
     *   'end_time' => Carbon|string,
     *   'is_compound' => bool,
     *   'segments' => ?array // For compound services
     * ]
     * @return array ['success' => bool, 'token' => ?string, 'expires_at' => ?Carbon, 'error' => ?string]
     */
    public function createReservation(array $params): array
    {
        $companyId = auth()->user()->company_id ?? Company::getActiveCompanyId();

        // Validate inputs
        if (!$this->validateReservationParams($params)) {
            return ['success' => false, 'error' => 'Invalid reservation parameters'];
        }

        // Determine TTL
        $ttl = !empty($params['is_compound']) ? $this->compoundTtlSeconds : $this->defaultTtlSeconds;
        $expiresAt = now()->addSeconds($ttl);

        // Check for conflicts
        try {
            $this->validateNoConflicts(
                $companyId,
                $params['start_time'],
                $params['end_time'],
                $params['staff_id'] ?? null
            );
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Time slot conflict detected: ' . $e->getMessage(),
            ];
        }

        // Create reservation(s)
        return DB::transaction(function () use ($params, $companyId, $expiresAt) {
            if (!empty($params['segments'])) {
                return $this->createCompoundReservation($params, $companyId, $expiresAt);
            }

            return $this->createSingleReservation($params, $companyId, $expiresAt);
        });
    }

    /**
     * Create single reservation
     */
    protected function createSingleReservation(array $params, int $companyId, Carbon $expiresAt): array
    {
        $reservation = AppointmentReservation::create([
            'company_id' => $companyId,
            'reservation_token' => Str::uuid(),
            'status' => 'active',
            'call_id' => $params['call_id'],
            'customer_phone' => $params['customer_phone'],
            'customer_name' => $params['customer_name'] ?? null,
            'service_id' => $params['service_id'],
            'staff_id' => $params['staff_id'] ?? null,
            'start_time' => $params['start_time'],
            'end_time' => $params['end_time'],
            'is_compound' => false,
            'expires_at' => $expiresAt,
        ]);

        // Cache reservation for fast lookup
        $this->cacheReservation($reservation);

        return [
            'success' => true,
            'token' => $reservation->reservation_token,
            'expires_at' => $reservation->expires_at,
            'time_remaining' => $reservation->timeRemaining(),
        ];
    }

    /**
     * Create compound reservation (multiple segments)
     */
    protected function createCompoundReservation(array $params, int $companyId, Carbon $expiresAt): array
    {
        $parentToken = Str::uuid();
        $segments = $params['segments'];
        $tokens = [];

        foreach ($segments as $index => $segment) {
            $reservation = AppointmentReservation::create([
                'company_id' => $companyId,
                'reservation_token' => Str::uuid(),
                'status' => 'active',
                'call_id' => $params['call_id'],
                'customer_phone' => $params['customer_phone'],
                'customer_name' => $params['customer_name'] ?? null,
                'service_id' => $segment['service_id'],
                'staff_id' => $params['staff_id'] ?? null,
                'start_time' => $segment['start_time'],
                'end_time' => $segment['end_time'],
                'is_compound' => true,
                'compound_parent_token' => $parentToken,
                'segment_number' => $index + 1,
                'total_segments' => count($segments),
                'expires_at' => $expiresAt,
            ]);

            $this->cacheReservation($reservation);
            $tokens[] = $reservation->reservation_token;
        }

        return [
            'success' => true,
            'parent_token' => $parentToken,
            'tokens' => $tokens,
            'expires_at' => $expiresAt,
            'time_remaining' => $expiresAt->diffInSeconds(now()),
            'segments_count' => count($segments),
        ];
    }

    /**
     * Validate reservation token and check if still valid
     */
    public function validateReservation(string $token): array
    {
        // Try cache first
        $cached = Cache::get("reservation:{$token}");
        if ($cached && $cached['expires_at'] > now()) {
            return [
                'valid' => true,
                'expires_at' => $cached['expires_at'],
                'time_remaining' => now()->diffInSeconds($cached['expires_at'], false),
            ];
        }

        // Fallback to database
        $reservation = AppointmentReservation::where('reservation_token', $token)
            ->where('status', 'active')
            ->first();

        if (!$reservation) {
            return ['valid' => false, 'reason' => 'not_found'];
        }

        if ($reservation->isExpired()) {
            $reservation->markExpired();
            return ['valid' => false, 'reason' => 'expired'];
        }

        // Check for conflicts with confirmed appointments
        $conflicts = $this->checkConflictsWithAppointments(
            $reservation->company_id,
            $reservation->start_time,
            $reservation->end_time,
            $reservation->staff_id
        );

        if ($conflicts > 0) {
            return ['valid' => false, 'reason' => 'conflict'];
        }

        return [
            'valid' => true,
            'expires_at' => $reservation->expires_at,
            'time_remaining' => $reservation->timeRemaining(),
            'reservation' => $reservation,
        ];
    }

    /**
     * Convert reservation to appointment
     */
    public function convertToAppointment(string $token, int $appointmentId): bool
    {
        $reservation = AppointmentReservation::where('reservation_token', $token)
            ->where('status', 'active')
            ->first();

        if (!$reservation) {
            return false;
        }

        $result = $reservation->markConverted($appointmentId);

        // Clear cache
        Cache::forget("reservation:{$token}");

        return $result;
    }

    /**
     * Check for conflicts with existing reservations or appointments
     */
    protected function validateNoConflicts(
        int $companyId,
        $startTime,
        $endTime,
        ?int $staffId
    ): void {
        $startTime = Carbon::parse($startTime);
        $endTime = Carbon::parse($endTime);

        // Check active reservations
        $reservationConflicts = AppointmentReservation::where('company_id', $companyId)
            ->active()
            ->forTimeRange($startTime, $endTime);

        if ($staffId) {
            $reservationConflicts->where('staff_id', $staffId);
        }

        if ($reservationConflicts->exists()) {
            throw new \Exception('Time slot already reserved');
        }

        // Check confirmed appointments
        $appointmentConflicts = $this->checkConflictsWithAppointments(
            $companyId,
            $startTime,
            $endTime,
            $staffId
        );

        if ($appointmentConflicts > 0) {
            throw new \Exception('Time slot conflicts with existing appointment');
        }
    }

    /**
     * Check conflicts with appointments table
     */
    protected function checkConflictsWithAppointments(
        int $companyId,
        $startTime,
        $endTime,
        ?int $staffId
    ): int {
        $query = Appointment::where('company_id', $companyId)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function ($subq) use ($startTime, $endTime) {
                      $subq->where('start_time', '<=', $startTime)
                           ->where('end_time', '>=', $endTime);
                  });
            });

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        return $query->count();
    }

    /**
     * Cache reservation for fast lookup
     */
    protected function cacheReservation(AppointmentReservation $reservation): void
    {
        $ttl = $reservation->timeRemaining();

        if ($ttl > 0) {
            Cache::put(
                "reservation:{$reservation->reservation_token}",
                [
                    'id' => $reservation->id,
                    'expires_at' => $reservation->expires_at,
                    'staff_id' => $reservation->staff_id,
                    'start_time' => $reservation->start_time,
                    'end_time' => $reservation->end_time,
                ],
                $ttl
            );
        }
    }

    /**
     * Validate reservation parameters
     */
    protected function validateReservationParams(array $params): bool
    {
        $required = ['call_id', 'customer_phone', 'service_id', 'start_time', 'end_time'];

        foreach ($required as $field) {
            if (empty($params[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Cleanup expired reservations (called by scheduled job)
     */
    public function cleanupExpired(): int
    {
        $expired = AppointmentReservation::expired()->get();

        $count = 0;
        foreach ($expired as $reservation) {
            $reservation->markExpired();
            Cache::forget("reservation:{$reservation->reservation_token}");
            $count++;
        }

        return $count;
    }

    /**
     * Get reservation statistics
     */
    public function getStatistics(int $companyId, ?Carbon $since = null): array
    {
        $query = AppointmentReservation::where('company_id', $companyId);

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        $total = $query->count();
        $converted = $query->where('status', 'converted')->count();
        $expired = $query->where('status', 'expired')->count();
        $active = $query->where('status', 'active')->count();

        return [
            'total' => $total,
            'converted' => $converted,
            'expired' => $expired,
            'active' => $active,
            'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 2) : 0,
        ];
    }
}
