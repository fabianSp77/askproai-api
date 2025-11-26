<?php

namespace App\Services\Booking;

use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * BookingNoticeValidator
 *
 * Validates booking requests against minimum booking notice requirements.
 *
 * Bug #11 Fix (2025-10-25): Prevents false positive "available" responses
 * when requested time violates Cal.com minimum booking notice period.
 *
 * Configuration Hierarchy:
 * 1. Branch override (branch_service.branch_policies.booking_notice_minutes)
 * 2. Service-specific (services.minimum_booking_notice)
 * 3. Global default (config.calcom.minimum_booking_notice_minutes)
 *
 * @package App\Services\Booking
 */
class BookingNoticeValidator
{
    /**
     * Validate if requested appointment time meets minimum booking notice
     *
     * @param Carbon $requestedTime Desired appointment time (Europe/Berlin timezone)
     * @param Service $service Service being booked
     * @param string|null $branchId Optional branch ID for branch-specific override
     * @return array Validation result with keys:
     *               - 'valid' (bool): Whether booking notice is sufficient
     *               - 'reason' (string|null): Rejection reason if invalid
     *               - 'minimum_notice_minutes' (int): Required minimum notice
     *               - 'earliest_bookable' (Carbon): Earliest bookable time
     *               - 'minutes_until_earliest' (int): Minutes until earliest bookable
     *
     * @example
     * $result = $validator->validateBookingNotice(
     *     Carbon::parse('2025-10-25 19:00'),
     *     $service
     * );
     *
     * if (!$result['valid']) {
     *     return error($result['reason']);
     * }
     */
    public function validateBookingNotice(
        Carbon $requestedTime,
        Service $service,
        ?string $branchId = null
    ): array {
        // Get minimum notice for this service
        $minimumNoticeMinutes = $this->getMinimumNoticeMinutes($service, $branchId);

        // Get current time in Europe/Berlin timezone
        $now = Carbon::now('Europe/Berlin');

        // Calculate earliest bookable time
        $earliestBookable = $now->copy()->addMinutes($minimumNoticeMinutes);

        // Validate: requested time must be >= earliest bookable
        // Note: Using >= (not just >) to match Cal.com behavior
        if ($requestedTime->lt($earliestBookable)) {
            Log::info('⚠️ Booking notice validation failed', [
                'requested_time' => $requestedTime->toDateTimeString(),
                'earliest_bookable' => $earliestBookable->toDateTimeString(),
                'minimum_notice_minutes' => $minimumNoticeMinutes,
                'service_id' => $service->id,
                'service_name' => $service->name,
                'minutes_short' => $earliestBookable->diffInMinutes($requestedTime),
            ]);

            return [
                'valid' => false,
                'reason' => 'too_soon',
                'minimum_notice_minutes' => $minimumNoticeMinutes,
                'earliest_bookable' => $earliestBookable,
                'minutes_until_earliest' => $now->diffInMinutes($earliestBookable),
                'minutes_short' => $earliestBookable->diffInMinutes($requestedTime),
            ];
        }

        Log::debug('✅ Booking notice validation passed', [
            'requested_time' => $requestedTime->toDateTimeString(),
            'minimum_notice_minutes' => $minimumNoticeMinutes,
            'service_id' => $service->id,
            'buffer_minutes' => $requestedTime->diffInMinutes($earliestBookable),
        ]);

        return [
            'valid' => true,
            'minimum_notice_minutes' => $minimumNoticeMinutes,
            'earliest_bookable' => $earliestBookable,
        ];
    }

    /**
     * Get minimum booking notice for a service
     *
     * Resolution order:
     * 1. Branch-specific override (if branchId provided)
     * 2. Service-specific configuration
     * 3. Global default from config
     * 4. Hardcoded fallback (15 minutes)
     *
     * @param Service $service Service to check
     * @param string|null $branchId Optional branch ID for override
     * @return int Minimum notice in minutes
     */
    public function getMinimumNoticeMinutes(Service $service, ?string $branchId = null): int
    {
        // Level 1: Branch override (highest priority)
        if ($branchId) {
            $branchPolicy = $service->branches()
                ->where('branch_id', $branchId)
                ->first()
                ?->pivot
                ?->branch_policies;

            if (isset($branchPolicy['booking_notice_minutes'])) {
                Log::debug('📍 Using branch-specific booking notice', [
                    'branch_id' => $branchId,
                    'service_id' => $service->id,
                    'notice_minutes' => $branchPolicy['booking_notice_minutes'],
                    'source' => 'branch_override',
                ]);

                return (int) $branchPolicy['booking_notice_minutes'];
            }
        }

        // Level 2: Service-specific (medium priority)
        if ($service->minimum_booking_notice !== null) {
            Log::debug('🔧 Using service-specific booking notice', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'notice_minutes' => $service->minimum_booking_notice,
                'source' => 'service_config',
            ]);

            return (int) $service->minimum_booking_notice;
        }

        // Level 3: Global default (low priority)
        $globalDefault = config('calcom.minimum_booking_notice_minutes');

        if ($globalDefault !== null) {
            Log::debug('🌍 Using global booking notice', [
                'service_id' => $service->id,
                'notice_minutes' => $globalDefault,
                'source' => 'global_config',
            ]);

            return (int) $globalDefault;
        }

        // Level 4: Hardcoded fallback (last resort)
        Log::warning('⚠️ Using hardcoded booking notice fallback', [
            'service_id' => $service->id,
            'notice_minutes' => 15,
            'source' => 'hardcoded_fallback',
            'recommendation' => 'Set CALCOM_MIN_BOOKING_NOTICE in .env',
        ]);

        return 15; // 15 minutes default
    }

    /**
     * Get earliest bookable time for a service
     *
     * @param Service $service Service to check
     * @param string|null $branchId Optional branch ID
     * @return Carbon Earliest time a booking can be made
     */
    public function getEarliestBookableTime(Service $service, ?string $branchId = null): Carbon
    {
        $minimumNoticeMinutes = $this->getMinimumNoticeMinutes($service, $branchId);
        $now = Carbon::now('Europe/Berlin');

        return $now->copy()->addMinutes($minimumNoticeMinutes);
    }

    /**
     * Suggest alternative appointment times
     *
     * When a requested time is too soon, this method suggests alternative times
     * that meet the minimum booking notice requirement.
     *
     * 🔧 FIX 2025-11-26: Added conflict filtering against existing appointments
     * PROBLEM: Suggested alternatives (e.g., 12:15, 12:30) overlapped with existing
     *          appointments like Dauerwelle 11:00-13:15, causing "wurde gerade vergeben"
     * SOLUTION: Check each candidate slot against local DB before suggesting
     *
     * Strategy:
     * 1. Try to find slots today (after minimum notice)
     * 2. Filter out slots that conflict with existing appointments
     * 3. Round to nearest slot interval (typically 15 or 30 minutes)
     *
     * @param Carbon $requestedTime Original requested time
     * @param Service $service Service being booked
     * @param string|null $branchId Optional branch ID
     * @param int $count Number of alternatives to suggest (default: 3)
     * @return array Array of alternative times with formatted strings
     */
    public function suggestAlternatives(
        Carbon $requestedTime,
        Service $service,
        ?string $branchId = null,
        int $count = 3
    ): array {
        $earliestBookable = $this->getEarliestBookableTime($service, $branchId);
        $alternatives = [];

        // Get slot interval (default to 15 minutes if not configured)
        $slotInterval = $service->calcom_slot_interval ?? 15;

        // Round earliest bookable time to next slot interval
        $nextSlot = $earliestBookable->copy()->addMinutes(
            $slotInterval - ($earliestBookable->minute % $slotInterval)
        );

        // 🔧 FIX 2025-11-26: Calculate full service duration for composite services
        // PROBLEM: Dauerwelle is 135 min, but Cal.com slot interval is 15 min
        // Slot 12:15 + 135 min = 14:30, which overlaps with appointment 11:00-13:15
        $serviceDuration = $this->getFullServiceDuration($service);

        // 🔧 FIX 2025-11-26: Get existing appointments for conflict checking
        $existingAppointments = $this->getExistingAppointments(
            $service->company_id,
            $branchId,
            $nextSlot->format('Y-m-d')
        );

        // 🔧 FIX 2025-11-26: Multi-day alternative search with business hours
        // PROBLEM: maxAttempts=15 was too small to span multiple days
        // SOLUTION: Search across 7 days with proper business hours (08:00-20:00)
        $businessHourStart = 8;  // 08:00
        $businessHourEnd = 20;   // 20:00 (last slot must END before this + buffer)
        $daysToSearch = 7;
        $maxSlotsPerDay = 50;    // Safety limit per day

        // Adjust nextSlot to business hours if needed
        if ($nextSlot->hour < $businessHourStart) {
            $nextSlot->setTime($businessHourStart, 0);
        } elseif ($nextSlot->hour >= $businessHourEnd) {
            // Move to next day
            $nextSlot->addDay()->setTime($businessHourStart, 0);
        }

        $currentDay = $nextSlot->copy();
        $daysSearched = 0;

        while (count($alternatives) < $count && $daysSearched < $daysToSearch) {
            $dayStart = $currentDay->copy();
            if ($dayStart->isSameDay($nextSlot)) {
                // First day: start from nextSlot
                $dayStart = $nextSlot->copy();
            } else {
                $dayStart->setTime($businessHourStart, 0);
            }

            // Calculate last possible slot start time for this day
            // Last slot must END before business hour end
            $latestSlotStart = $businessHourEnd * 60 - $serviceDuration;
            $slotAttempts = 0;

            while ($slotAttempts < $maxSlotsPerDay) {
                $suggestionTime = $dayStart->copy()->addMinutes($slotInterval * $slotAttempts);
                $slotAttempts++;

                // Check if slot is within business hours
                $slotMinutes = $suggestionTime->hour * 60 + $suggestionTime->minute;
                if ($slotMinutes >= $latestSlotStart) {
                    break; // Move to next day
                }

                // Check for conflicts with existing appointments
                if ($this->hasConflict($suggestionTime, $serviceDuration, $existingAppointments)) {
                    Log::debug('🚫 Skipping conflicting slot in suggestAlternatives', [
                        'slot_time' => $suggestionTime->format('Y-m-d H:i'),
                        'slot_end' => $suggestionTime->copy()->addMinutes($serviceDuration)->format('H:i'),
                        'service_duration' => $serviceDuration,
                    ]);
                    continue;
                }

                $alternatives[] = [
                    'datetime' => $suggestionTime,
                    'date' => $suggestionTime->format('Y-m-d'),
                    'time' => $suggestionTime->format('H:i'),
                    'formatted_de' => $suggestionTime->locale('de')->isoFormat('dddd, D. MMMM [um] HH:mm [Uhr]'),
                    'formatted_short' => $suggestionTime->locale('de')->isoFormat('dd D.MM. HH:mm'),
                    'is_same_day' => $suggestionTime->isSameDay($requestedTime),
                    'is_next_day' => $suggestionTime->isSameDay($requestedTime->copy()->addDay()),
                ];

                if (count($alternatives) >= $count) {
                    break;
                }
            }

            // Move to next day
            $currentDay->addDay();
            $daysSearched++;
        }

        Log::debug('💡 Alternative times suggested', [
            'service_id' => $service->id,
            'requested_time' => $requestedTime->toDateTimeString(),
            'earliest_bookable' => $earliestBookable->toDateTimeString(),
            'alternatives_count' => count($alternatives),
            'days_searched' => $daysSearched,
            'service_duration' => $serviceDuration,
        ]);

        return $alternatives;
    }

    /**
     * Get full service duration, including composite segments
     *
     * 🔧 FIX 2025-11-26: ROOT CAUSE FIX for overlap detection
     *
     * @param Service $service
     * @return int Duration in minutes
     */
    protected function getFullServiceDuration(Service $service): int
    {
        // Check for composite service with segments
        // 🔧 FIX 2025-11-26: Use isComposite() method instead of is_composite property
        // Property access returns NULL, method correctly checks DB column 'composite'
        if ($service->isComposite() && !empty($service->segments)) {
            return collect($service->segments)->sum(fn($s) => $s['durationMin'] ?? $s['duration'] ?? 0);
        }

        // Check for duration_minutes field (with null safety)
        if ($service->duration_minutes !== null && $service->duration_minutes > 0) {
            return $service->duration_minutes;
        }

        // Default fallback
        return 60;
    }

    /**
     * Get existing appointments for conflict checking
     *
     * 🔧 FIX 2025-11-26: Changed from single date to date RANGE query
     * PROBLEM: suggestAlternatives() iterates over multiple days but only queried one day
     * This caused alternatives on day 2+ to be offered without conflict checking!
     * Example: User requests 26.11 16:00, alternatives generated for 27.11 09:00
     *          But Appointment #782 (27.11 08:00-10:15) was never queried → conflict missed
     *
     * @param int $companyId
     * @param string|null $branchId
     * @param string $startDate Y-m-d format (start of range)
     * @param int $daysToCheck Number of days to include (default: 8 for full week coverage)
     * @return \Illuminate\Support\Collection
     */
    protected function getExistingAppointments(int $companyId, ?string $branchId, string $startDate, int $daysToCheck = 8)
    {
        $endDate = Carbon::parse($startDate)->addDays($daysToCheck)->format('Y-m-d');

        // Memory safety: Limit results to prevent OOM with enterprise accounts
        $maxAppointments = 2000;

        $query = \App\Models\Appointment::where('company_id', $companyId)
            ->whereIn('status', ['scheduled', 'confirmed', 'pending', 'in_progress', 'booked'])
            ->whereDate('starts_at', '>=', $startDate)
            ->whereDate('starts_at', '<=', $endDate);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Get count first to check if limit is hit
        $totalCount = $query->count();

        Log::debug('📅 Loading appointments for conflict check', [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'date_range' => "{$startDate} to {$endDate}",
            'days' => $daysToCheck,
            'total_appointments' => $totalCount,
        ]);

        // Warn if limit is reached (conflict detection may be incomplete)
        if ($totalCount > $maxAppointments) {
            Log::warning('⚠️ Appointment query limit reached - conflict detection may be incomplete', [
                'company_id' => $companyId,
                'total_found' => $totalCount,
                'limit_applied' => $maxAppointments,
            ]);
        }

        return $query->limit($maxAppointments)->get(['id', 'starts_at', 'ends_at']);
    }

    /**
     * Check if a suggested slot conflicts with existing appointments
     *
     * 🔧 FIX 2025-11-26: Overlap detection using standard interval math
     * Two ranges overlap if: start1 < end2 AND start2 < end1
     *
     * @param Carbon $slotStart Suggested slot start time
     * @param int $duration Service duration in minutes
     * @param \Illuminate\Support\Collection $existingAppointments
     * @return bool True if conflict exists
     */
    protected function hasConflict(Carbon $slotStart, int $duration, $existingAppointments): bool
    {
        $slotEnd = $slotStart->copy()->addMinutes($duration);
        $slotDate = $slotStart->format('Y-m-d');

        foreach ($existingAppointments as $appt) {
            $apptStart = Carbon::parse($appt->starts_at);
            $apptEnd = Carbon::parse($appt->ends_at);

            // 🔧 FIX 2025-11-26: Only check appointments on the SAME day as the slot
            // This prevents false positives from multi-day appointment queries
            if ($apptStart->format('Y-m-d') !== $slotDate) {
                continue;
            }

            // Overlap check: start1 < end2 AND start2 < end1
            if ($slotStart < $apptEnd && $apptStart < $slotEnd) {
                Log::debug('🔍 Conflict detected in hasConflict', [
                    'slot_date' => $slotDate,
                    'slot' => $slotStart->format('H:i') . '-' . $slotEnd->format('H:i'),
                    'appointment_id' => $appt->id,
                    'appointment' => $apptStart->format('Y-m-d H:i') . '-' . $apptEnd->format('H:i'),
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Format a user-friendly error message in German
     *
     * @param array $validationResult Result from validateBookingNotice()
     * @param array|null $alternatives Optional alternative times
     * @return string Formatted German error message
     */
    public function formatErrorMessage(array $validationResult, ?array $alternatives = null): string
    {
        $minimumNotice = $validationResult['minimum_notice_minutes'];

        // Format minimum notice as human-readable string
        if ($minimumNotice >= 60) {
            $hours = floor($minimumNotice / 60);
            $minutes = $minimumNotice % 60;

            if ($minutes === 0) {
                $noticeStr = $hours === 1 ? '1 Stunde' : "{$hours} Stunden";
            } else {
                $noticeStr = "{$hours} Stunden und {$minutes} Minuten";
            }
        } else {
            $noticeStr = "{$minimumNotice} Minuten";
        }

        // Base message
        $message = "Dieser Termin liegt leider zu kurzfristig. " .
                   "Termine können frühestens {$noticeStr} im Voraus gebucht werden.";

        // Add alternative if provided
        if ($alternatives && count($alternatives) > 0) {
            $firstAlt = $alternatives[0];
            $message .= " Der nächste verfügbare Termin ist {$firstAlt['formatted_de']}.";
        }

        return $message;
    }
}
